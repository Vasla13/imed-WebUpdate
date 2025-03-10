<?php
require_once 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmtSelect = $conn->prepare("SELECT DATEIPFAD FROM VERSIONEN WHERE ID = ?");
    $stmtSelect->bind_param("i", $id);
    $stmtSelect->execute();
    $resSelect = $stmtSelect->get_result();
    if ($resSelect->num_rows < 1) {
        die("Version nicht gefunden (ID: $id)");
    }
    $row = $resSelect->fetch_assoc();
    $filePath = $row['DATEIPFAD'];

    if (!empty($filePath) && file_exists($filePath)) {
        if (!unlink($filePath)) {
            log_error("Fehler beim Löschen der Datei: " . $filePath);
        }
    }

    $pattern = '/imedWeb_([0-9.]+)_p[0-9]+_gh/i';
    if (preg_match($pattern, $filePath, $matches)) {
        $extractedDirName = "imed-Web_" . $matches[1] . "_gh";
        $extractedDirPath = "/imed/prog/new/" . $extractedDirName;
        if (is_dir($extractedDirPath)) {
            exec("rm -rf " . escapeshellarg($extractedDirPath));
        }
    }

    $stmtDelete = $conn->prepare("DELETE FROM VERSIONEN WHERE ID = ?");
    $stmtDelete->bind_param("i", $id);
    if ($stmtDelete->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        die("Fehler beim Löschen der Version aus der Datenbank.");
    }

} else {
    die("Keine ID angegeben.");
}
?>
