<?php
require_once 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Récupérer le chemin de l'archive et le dossier extrait depuis la BDD
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

    // Supprimer le fichier d’archive dans le dossier uploads (s'il existe)
    if (!empty($archivePath) && file_exists($archivePath)) {
        unlink($archivePath);
    }
    
    // Supprimer l’archive copiée dans /imed/prog/new (si elle existe)
    $archiveBaseName = basename($archivePath);
    $copiedArchivePath = "/imed/prog/new/" . $archiveBaseName;
    if (file_exists($copiedArchivePath)) {
        unlink($copiedArchivePath);
    }

    // Supprimer le dossier extrait complet basé sur extracted_folder
    if (!empty($extractedFolder)) {
        $extractedDirPath = "/imed/prog/new/" . $extractedFolder;
        if (is_dir($extractedDirPath)) {
            exec("rm -rf " . escapeshellarg($extractedDirPath));
        }
    }

    // Supprimer l'entrée dans la table VERSIONS
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
