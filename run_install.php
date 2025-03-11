<?php
session_start();
require_once 'config.php';
require_once 'db.php';

// Überprüfen, ob der Benutzer Admin oder User ist
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'user'])) {
    header("Location: login.php");
    exit();
}

// Rücksprungseite je nach Rolle definieren
$backPage = ($_SESSION['user_role'] === 'admin') ? 'admin.php' : 'user.php';
$backPageText = ($_SESSION['user_role'] === 'admin') ? 'Admin-Seite' : 'User-Seite';

// Parameter abrufen
$version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
$schritt = isset($_GET['step']) ? (int)$_GET['step'] : 0;

if ($version_id <= 0) {
    die("Keine Version ausgewählt.");
}

// Archivpfad und extrahierten Ordner aus der Datenbank abrufen
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

// Existenz der Datei für die Schritte 1 und 2 überprüfen
if (($schritt === 1 || $schritt === 2) && !file_exists($web_archiv)) {
    die("Die Datei existiert nicht auf dem Server: " . htmlspecialchars($web_archiv));
}

// Pfad zum Shell-Skript
$script_path = "lib/install_imed_web.sh"; 
// (ggf. absoluten Pfad anpassen, z.B. "/imed/prog/imed-WebUpdate/lib/install_imed_web.sh")

if (!file_exists($script_path)) {
    die("Installationsskript nicht gefunden: " . htmlspecialchars($script_path));
}

// Direkte Ausgabe vorbereiten
header('Content-Type: text/html; charset=utf-8');
@ini_set('output_buffering','off');
@ini_set('zlib.output_compression', 0);
set_time_limit(0);

echo "<!DOCTYPE html>\n<html lang='de'>\n<head>\n  <meta charset='UTF-8'>\n";
if ($schritt === 1) {
    // Automatisches Refresh (optional)
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
    // SCHRITT 1: Extrahiere in einen gemeinsamen Ordner
    // ============================================
    
    // Beispielsweise /imed/prog/new als Hauptordner verwenden
    $targetContainer = "/imed/prog/new";
    
    // Sicherstellen, dass der Ordner existiert
    if (!is_dir($targetContainer)) {
        mkdir($targetContainer, 0755, true);
    }
    
    // Das Shell-Skript mit Übergabe des Zielordners aufrufen
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
            
            // Nach der Extraktion: Suche nach dem extrahierten Ordner (z. B. im Format imed-Web_6.005.000.000_gh)
            $cmd = "find " . escapeshellarg($targetContainer) . " -maxdepth 1 -type d -name 'imed-Web_*_gh' | sort | head -n 1";
            $extractedSubfolder = trim(shell_exec($cmd));
            if (!$extractedSubfolder) {
                die("FEHLER: Kein extrahierter Ordner gefunden.");
            }
            // Extrahiere den Ordnernamen
            $extractedFolderName = basename($extractedSubfolder);
            
            // Speichere einfach den Ordnernamen (z. B. "imed-Web_6.005.000.000_gh")
            $finalExtractedFolder = $extractedFolderName;
            
            $newStatus = 1; // Extraktion erfolgreich
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
        echo "FEHLER: Prozessstart des Installationsskripts nicht möglich.";
    }

} elseif ($schritt === 2) {
    // ============================================
    // SCHRITT 2: Führe install.sh im extrahierten Ordner aus
    // ============================================
    
    if (empty($current_extracted_folder)) {
       die("Kein extrahierter Ordner in der Datenbank gefunden.");
    }
    
    $targetContainer = "/imed/prog/new/" . $current_extracted_folder; 
    // Beispiel: /imed/prog/new/imed-Web_6.005.000.000_gh
    
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
        echo "FEHLER: Prozessstart des Installationsskripts nicht möglich.";
    }

} elseif ($schritt === 3) {
    // ============================================
    // SCHRITT 3: Status finalisieren
    // ============================================
    
    if (empty($current_extracted_folder)) {
       die("Kein extrahierter Ordner in der Datenbank gefunden.");
    }
    $newStatus = 3;
    $stmtUpdate = $conn->prepare("UPDATE VERSIONS SET installation_status = ? WHERE ID = ?");
    $stmtUpdate->bind_param("ii", $newStatus, $version_id);
    $stmtUpdate->execute();
    
    $server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
    
    // Wichtiger Hinweis: Direkter Link, z. B. "http://IP/imed-Web_6.005.000.000_gh/imed-Info/framework.php"
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
?>
