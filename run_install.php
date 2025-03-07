<?php
session_start();
require_once 'config.php';
require_once 'db.php';

// Vérifier l'admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Récupérer les paramètres
$version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
$schritt = isset($_GET['step']) ? (int)$_GET['step'] : 0;

if ($version_id <= 0) {
    die("Keine Version ausgewählt.");
}

// Chemin de l'archive
$stmt = $conn->prepare("SELECT DATEIEN FROM VERSIONS WHERE ID = ?");
$stmt->bind_param("i", $version_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    die("Version nicht in der Datenbank gefunden (ID = $version_id).");
}
$row = $result->fetch_assoc();
$web_archiv = $row['DATEIEN']; // Chemin complet vers l’archive

// Vérifier le fichier pour les étapes 1 et 2
if (($schritt === 1 || $schritt === 2) && !file_exists($web_archiv)) {
    die("Die Datei existiert nicht auf dem Server: " . htmlspecialchars($web_archiv));
}

// Chemin du script shell
$script_path = "/imed/prog/imed-WebUpdate/lib/install_imed_web.sh";
if (!file_exists($script_path)) {
    die("Installationsskript nicht gefunden: " . htmlspecialchars($script_path));
}

// Affichage direct
header('Content-Type: text/html; charset=utf-8');
@ini_set('output_buffering','off');
@ini_set('zlib.output_compression', 0);
set_time_limit(0);

echo "<!DOCTYPE html>\n";
echo "<html lang='de'>\n";
echo "<head>\n";
echo "  <meta charset='UTF-8'>\n";
if ($schritt === 1) {
    // redirection auto en 5s
    echo "  <meta http-equiv='refresh' content='5;url=admin.php'>\n";
}
echo "  <title>Installation von Imed-Web - Schritt $schritt</title>\n";
echo "  <link rel='stylesheet' href='style.css'>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='install-container' style='max-width:1000px; margin: 20px auto;'>\n";
echo "<h2>Installation der Version #" . htmlspecialchars($version_id) . " - Schritt $schritt</h2>\n";
echo "<pre style='background:rgba(255,255,255,0.1); border-radius:6px; padding:15px;'>\n";
ob_flush();
flush();

if ($schritt === 1 || $schritt === 2) {
    // Exécuter le script shell
    $command = sprintf(
        'sh %s %s %d 2>&1',
        escapeshellarg($script_path),
        escapeshellarg($web_archiv),
        $schritt
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
            // MAJ BDD
            $newStatus = $schritt;
            $updateStmt = $conn->prepare("UPDATE VERSIONS SET installation_status = ? WHERE ID = ?");
            $updateStmt->bind_param("ii", $newStatus, $version_id);
            $updateStmt->execute();
            
            if ($schritt === 1) {
                echo "\n\nAutomatische Weiterleitung in 5 Sekunden zur Admin-Seite...";
                echo "</pre>";
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'admin.php';
                        }, 5000);
                      </script>";
                echo "<p><a href='admin.php' class='btn'>Sofort zurückkehren</a></p>";
                echo "</div></body></html>";
                ob_flush();
                flush();
                exit();
            }
        } else {
            echo "Fehler beim Ausführen von Schritt $schritt (Code $return_code).";
        }
    } else {
        echo "Fehler: Prozessstart des Installationsskripts nicht möglich.";
    }
} elseif ($schritt === 3) {
    $updateStmt = $conn->prepare("UPDATE VERSIONS SET installation_status = 3 WHERE ID = ?");
    $updateStmt->bind_param("i", $version_id);
    $updateStmt->execute();
    // Tenter de trouver le dossier extrait
    $cmd = "find /imed/prog/new -maxdepth 1 -type d -name 'imed-Web_*' | sort | head -n 1";
    $extractedDir = trim(shell_exec($cmd));
    if ($extractedDir) {
         $baseName = basename($extractedDir);
         $server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
         $siteLink = "http://{$server_ip}/{$baseName}/imed-Info/framework.php";
    } else {
         $siteLink = "#";
    }
    echo "</pre>\n";
    echo "<div class='install-success' style='text-align: center; margin: 20px;'>";
    echo "<h2>Die Installation ist abgeschlossen.</h2>";
    echo "<p>Sie können nun auf die Webseite zugreifen:</p>";
    echo "<a href='$siteLink' class='btn' target='_blank'><i class='fas fa-globe'></i> Zur Webseite</a>";
    echo "</div>";
} else {
    echo "Unbekannter Schritt.";
}

echo "<p><a href='admin.php' class='btn'>Zurück zur Admin-Seite</a></p>\n";
echo "</div>\n";
echo "</body>\n";
echo "</html>\n";

ob_flush();
flush();
?>
