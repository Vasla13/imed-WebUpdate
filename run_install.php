<?php
session_start();
require_once 'config.php';
require_once 'db.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'user'])) {
    header("Location: login.php");
    exit();
}

$backPage = ($_SESSION['user_role'] === 'admin') ? 'admin.php' : 'user.php';
$backPageText = ($_SESSION['user_role'] === 'admin') ? 'Administratorseite' : 'Benutzerseite';

$version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
$schritt = isset($_GET['step']) ? (int)$_GET['step'] : 0;

if ($version_id <= 0) {
    die("Keine Version ausgewählt.");
}

$stmt = $conn->prepare("SELECT DATEIPFAD FROM VERSIONEN WHERE ID = ?");
$stmt->bind_param("i", $version_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    die("Version nicht in der Datenbank gefunden (ID = $version_id).");
}
$row = $result->fetch_assoc();
$web_archiv = $row['DATEIPFAD'];

if (($schritt === 1 || $schritt === 2) && !file_exists($web_archiv)) {
    die("Die Datei existiert nicht auf dem Server: " . htmlspecialchars($web_archiv));
}

$script_path = "/imed/prog/imed-WebUpdate/lib/install_imed_web.sh";
if (!file_exists($script_path) || !is_executable($script_path)) {
    log_error("Installationsskript nicht gefunden oder nicht ausführbar: " . htmlspecialchars($script_path));
    die("Installationsskript nicht gefunden oder nicht ausführbar.");
}

header('Content-Type: text/html; charset=utf-8');
@ini_set('output_buffering','off');
@ini_set('zlib.output_compression', 0);
set_time_limit(0);

echo "<!DOCTYPE html>\n<html lang='de'>\n<head>\n  <meta charset='UTF-8'>\n";
if ($schritt === 1) {
    echo "  <meta http-equiv='refresh' content='5;url={$backPage}'>\n";
}
echo "  <title>Installation von Imed-Web - Schritt $schritt</title>\n";
echo "  <link rel='stylesheet' href='style.css'>\n";
echo "</head>\n<body>\n<div class='install-container'>\n";
echo "<h2>Installation der Version #" . htmlspecialchars($version_id) . " - Schritt $schritt</h2>\n";

$logFile = __DIR__ . '/logs/install_' . date('Ymd_His') . '.log';
$output = "";
$return_code = 1;

if ($schritt === 1 || $schritt === 2) {
    $command = sprintf('sh %s %s %d 2>&1', escapeshellarg($script_path), escapeshellarg($web_archiv), $schritt);
    $descriptorspec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        while (($line = fgets($pipes[1])) !== false) {
            $output .= $line;
        }
        while (($line = fgets($pipes[2])) !== false) {
            $output .= $line;
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $return_code = proc_close($process);
    } else {
        log_error("Fehler: Prozessstart des Installationsskripts nicht möglich.");
        $output = "Fehler: Installationsprozess konnte nicht gestartet werden.";
    }
    
    file_put_contents($logFile, $output, FILE_APPEND);
    
    if ($return_code === 0) {
        echo "<p>Schritt $schritt erfolgreich ausgeführt.</p>";
        $newStatus = $schritt;
        $updateStmt = $conn->prepare("UPDATE VERSIONEN SET INSTALLATIONSSTATUS = ? WHERE ID = ?");
        $updateStmt->bind_param("ii", $newStatus, $version_id);
        $updateStmt->execute();
        
        if ($schritt === 1) {
            echo "<p>Die Installation wurde erfolgreich gestartet. Sie werden in 5 Sekunden weitergeleitet.</p>";
            echo "<p><a href='{$backPage}' class='btn'>Jetzt zur {$backPageText} zurückkehren</a></p>";
            echo "</div></body></html>";
            exit();
        }
    } else {
        log_error("Installationsfehler in Schritt $schritt, Rückgabecode: $return_code. Log: " . $logFile);
        echo "<p>Es ist ein Fehler während des Installationsprozesses aufgetreten. Bitte überprüfen Sie die Protokolldatei.</p>";
    }
} elseif ($schritt === 3) {
    $updateStmt = $conn->prepare("UPDATE VERSIONEN SET INSTALLATIONSSTATUS = 3 WHERE ID = ?");
    $updateStmt->bind_param("i", $version_id);
    $updateStmt->execute();
    $cmd = "find /imed/prog/new -maxdepth 1 -type d -name 'imed-Web_*' | sort | head -n 1";
    $extractedDir = trim(shell_exec($cmd));
    if ($extractedDir) {
         $baseName = basename($extractedDir);
         $server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
         $siteLink = "http://{$server_ip}/{$baseName}/imed-Info/framework.php";
    } else {
         $siteLink = "#";
    }
    echo "<div class='install-success'>\n";
    echo "<h2>Die Installation ist abgeschlossen.</h2>\n";
    echo "<p>Sie können nun auf die Webseite zugreifen:</p>\n";
    echo "<a href='$siteLink' class='btn' target='_blank'><i class='fas fa-globe'></i> Zur Webseite</a>\n";
    echo "</div>\n";
} else {
    echo "<p>Unbekannter Schritt.</p>";
}

echo "<p><a href='{$backPage}' class='btn'>Zurück zur {$backPageText}</a></p>\n";
echo "</div>\n</body>\n</html>";
?>
