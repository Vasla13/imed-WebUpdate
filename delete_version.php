<?php
require_once 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Den Archivpfad und den extrahierten Ordner aus der Datenbank abrufen
    $stmtSelect = $conn->prepare("SELECT DATEIEN, extracted_folder FROM VERSIONS WHERE ID = ?");
    $stmtSelect->bind_param("i", $id);
    $stmtSelect->execute();
    $resSelect = $stmtSelect->get_result();
    if ($resSelect->num_rows < 1) {
        die("Version nicht gefunden (ID: $id)");
    }
    $row = $resSelect->fetch_assoc();
    $archivePath = $row['DATEIEN'];
    $extractedFolder = $row['extracted_folder'];

    // Lösche die Archivdatei im Ordner uploads (falls vorhanden)
    if (!empty($archivePath) && file_exists($archivePath)) {
        unlink($archivePath);
    }
    
    // Lösche das in /imed/prog/new kopierte Archiv (falls vorhanden)
    $archiveBaseName = basename($archivePath);
    $copiedArchivePath = "/imed/prog/new/" . $archiveBaseName;
    if (file_exists($copiedArchivePath)) {
        unlink($copiedArchivePath);
    }

    // Lösche den kompletten extrahierten Ordner basierend auf extracted_folder
    if (!empty($extractedFolder)) {
        $extractedDirPath = "/imed/prog/new/" . $extractedFolder;
        if (is_dir($extractedDirPath)) {
            exec("rm -rf " . escapeshellarg($extractedDirPath));
        }
    }

    // Lösche den Eintrag in der Tabelle VERSIONS
    $stmtDelete = $conn->prepare("DELETE FROM VERSIONS WHERE ID = ?");
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
