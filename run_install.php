<?php
session_start();
require_once 'config.php';
require_once 'db.php';

// Vérifier que l'utilisateur est admin ou user
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'user'])) {
    header("Location: login.php");
    exit();
}

// Définir la page de retour en fonction du rôle
$backPage = ($_SESSION['user_role'] === 'admin') ? 'admin.php' : 'user.php';
$backPageText = ($_SESSION['user_role'] === 'admin') ? 'Admin-Seite' : 'User-Seite';

// Récupérer les paramètres
$version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
$schritt = isset($_GET['step']) ? (int)$_GET['step'] : 0;

if ($version_id <= 0) {
    die("Keine Version ausgewählt.");
}

// Récupérer le chemin de l'archive et le dossier extrait (si existant) depuis la base
$stmt = $conn->prepare("SELECT DATEIEN, extracted_folder FROM VERSIONS WHERE ID = ?");
$stmt->bind_param("i", $version_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    die("Version nicht in der Datenbank gefunden (ID = $version_id).");
}
$row = $result->fetch_assoc();
$web_archiv = $row['DATEIEN'];
$current_extracted_folder = $row['extracted_folder'];

// Vérifier l'existence du fichier pour les étapes 1 et 2
if (($schritt === 1 || $schritt === 2) && !file_exists($web_archiv)) {
    die("Die Datei existiert nicht auf dem Server: " . htmlspecialchars($web_archiv));
}

// Chemin du script shell
$script_path = "lib/install_imed_web.sh"; 
// (adapté : si besoin, mets le chemin absolu, par ex. "/imed/prog/imed-WebUpdate/lib/install_imed_web.sh")

if (!file_exists($script_path)) {
    die("Installationsskript nicht gefunden: " . htmlspecialchars($script_path));
}

// Préparer l'affichage direct
header('Content-Type: text/html; charset=utf-8');
@ini_set('output_buffering','off');
@ini_set('zlib.output_compression', 0);
set_time_limit(0);

echo "<!DOCTYPE html>\n<html lang='de'>\n<head>\n  <meta charset='UTF-8'>\n";
if ($schritt === 1) {
    // on peut laisser un refresh auto, ou non
    echo "  <meta http-equiv='refresh' content='5;url={$backPage}'>\n";
}
echo "  <title>Installation von Imed-Web - Schritt $schritt</title>\n";
echo "  <link rel='stylesheet' href='style.css'>\n";
echo "  <script>
        setInterval(function() {
            var container = document.querySelector('.install-container');
            if (container) { container.scrollTop = container.scrollHeight; }
        }, 500);
      </script>\n";
echo "</head>\n<body>\n<div class='install-container'>\n";
echo "<h2>Installation der Version #" . htmlspecialchars($version_id) . " - Schritt $schritt</h2>\n<pre>\n";
ob_flush();
flush();

if ($schritt === 1) {
    // ============================================
    // ÉTAPE 1 : On extrait dans UN dossier commun
    // ============================================
    
    // On choisit par exemple /imed/prog/new comme dossier principal
    $targetContainer = "/imed/prog/new";
    
    // On s'assure qu'il existe
    if (!is_dir($targetContainer)) {
        mkdir($targetContainer, 0755, true);
    }
    
    // Appeler le script shell en passant le dossier conteneur
    // On ne construit plus de sous-dossier unique, on stocke tout dans $targetContainer
    $command = sprintf(
        'sh %s %s %d %s 2>&1',
        escapeshellarg($script_path),
        escapeshellarg($web_archiv),
        $schritt,
        escapeshellarg($targetContainer)
    );
    
    $descriptorspec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        while (($line = fgets($pipes[1])) !== false) {
            echo htmlspecialchars($line);
            ob_flush();
            flush();
        }
        while (($line = fgets($pipes[2])) !== false) {
            echo htmlspecialchars($line);
            ob_flush();
            flush();
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $return_code = proc_close($process);
        echo "\n---\n";
        if ($return_code === 0) {
            echo "Schritt $schritt erfolgreich ausgeführt (Code 0).";
            
            // Après extraction, on cherche le dossier extraits (ex : imed-Web_6.005.000.000_gh)
            // ATTENTION : On cherche dans /imed/prog/new
            $cmd = "find " . escapeshellarg($targetContainer) . " -maxdepth 1 -type d -name 'imed-Web_*_gh' | sort | head -n 1";
            $extractedSubfolder = trim(shell_exec($cmd));
            if (!$extractedSubfolder) {
                die("ERREUR: Kein extrahiertes Verzeichnis gefunden.");
            }
            // Extraire le nom du dossier
            $extractedFolderName = basename($extractedSubfolder);
            
            // On enregistre simplement ce nom (ex : "imed-Web_6.005.000.000_gh")
            // => plus de container "install_<ID>_xxxxx"
            $finalExtractedFolder = $extractedFolderName;
            
            $newStatus = 1; // Extraction réussie
            $stmtUpdate = $conn->prepare("UPDATE VERSIONS SET installation_status = ?, extracted_folder = ? WHERE ID = ?");
            $stmtUpdate->bind_param("isi", $newStatus, $finalExtractedFolder, $version_id);
            $stmtUpdate->execute();
            
            echo "\n\nAutomatische Weiterleitung in 5 Sekunden zur {$backPageText}...";
            echo "</pre>";
            echo "<script>
                    setTimeout(function() {
                        window.location.href = '{$backPage}';
                    }, 5000);
                  </script>";
            echo "<p><a href='{$backPage}' class='btn'>Sofort zurückkehren</a></p>";
            echo "</div></body></html>";
            ob_flush();
            flush();
            exit();
        } else {
            echo "Fehler beim Ausführen von Schritt $schritt (Code $return_code).";
        }
    } else {
        echo "Fehler: Prozessstart des Installationsskripts nicht möglich.";
    }

} elseif ($schritt === 2) {
    // ============================================
    // ÉTAPE 2 : on exécute install.sh dans le dossier
    // ============================================
    
    if (empty($current_extracted_folder)) {
       die("Kein extrahiertes Verzeichnis in der Datenbank gefunden.");
    }
    
    $targetContainer = "/imed/prog/new/" . $current_extracted_folder; 
    // => ex : /imed/prog/new/imed-Web_6.005.000.000_gh
    
    $command = sprintf(
        'sh %s %s %d %s 2>&1',
        escapeshellarg($script_path),
        escapeshellarg($web_archiv),
        $schritt,
        escapeshellarg($targetContainer)
    );
    
    $descriptorspec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        while (($line = fgets($pipes[1])) !== false) {
            echo htmlspecialchars($line);
            ob_flush();
            flush();
        }
        while (($line = fgets($pipes[2])) !== false) {
            echo htmlspecialchars($line);
            ob_flush();
            flush();
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $return_code = proc_close($process);
        echo "\n---\n";
        if ($return_code === 0) {
            echo "Schritt $schritt erfolgreich ausgeführt (Code 0).";
            $newStatus = 2;
            $stmtUpdate = $conn->prepare("UPDATE VERSIONS SET installation_status = ? WHERE ID = ?");
            $stmtUpdate->bind_param("ii", $newStatus, $version_id);
            $stmtUpdate->execute();
        } else {
            echo "Fehler beim Ausführen von Schritt $schritt (Code $return_code).";
        }
    } else {
        echo "Fehler: Prozessstart des Installationsskripts nicht möglich.";
    }

} elseif ($schritt === 3) {
    // ============================================
    // ÉTAPE 3 : on finalise le statut
    // ============================================
    
    if (empty($current_extracted_folder)) {
       die("Kein extrahiertes Verzeichnis in der Datenbank gefunden.");
    }
    $newStatus = 3;
    $stmtUpdate = $conn->prepare("UPDATE VERSIONS SET installation_status = ? WHERE ID = ?");
    $stmtUpdate->bind_param("ii", $newStatus, $version_id);
    $stmtUpdate->execute();
    
    $server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
    
    // IMPORTANT : on pointe directement sur "http://IP/imed-Web_6.005.000.000_gh/imed-Info/framework.php"
    // (plus de /install/ ni container)
    $siteLink = "http://{$server_ip}/" . $current_extracted_folder . "/imed-Info/framework.php";
    
    echo "</pre>\n";
    echo "<div class='install-success'>\n";
    echo "<h2>Die Installation ist abgeschlossen.</h2>\n";
    echo "<p>Sie können nun auf die Webseite zugreifen:</p>\n";
    echo "<a href='$siteLink' class='btn' target='_blank'><i class='fas fa-globe'></i> Zur Webseite</a>\n";
    echo "</div>\n";

} else {
    echo "Unbekannter Schritt.";
}

echo "<p><a href='{$backPage}' class='btn'>Zurück zur {$backPageText}</a></p>\n";
echo "</div>\n</body>\n</html>\n";

ob_flush();
flush();
