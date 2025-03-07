<?php
require_once 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 1) Archivpfad aus der Tabelle VERSIONS abrufen
    $stmtSelect = $conn->prepare("SELECT DATEIEN FROM VERSIONS WHERE ID = ?");
    $stmtSelect->bind_param("i", $id);
    $stmtSelect->execute();
    $resSelect = $stmtSelect->get_result();
    if ($resSelect->num_rows < 1) {
        die("Version nicht gefunden (ID: $id)");
    }
    $row = $resSelect->fetch_assoc();
    $archivePath = $row['DATEIEN'];

    // 2) Archiv löschen
    if (!empty($archivePath) && file_exists($archivePath)) {
        unlink($archivePath);
    }

    // 3) Dossier extrait dans /imed/prog/new
    $pattern = '/imedWeb_([0-9.]+)_p[0-9]+_gh/i';
    if (preg_match($pattern, $archivePath, $matches)) {
        $extractedDirName = "imed-Web_" . $matches[1] . "_gh";
        $extractedDirPath = "/imed/prog/new/" . $extractedDirName;
        if (is_dir($extractedDirPath)) {
            exec("rm -rf " . escapeshellarg($extractedDirPath));
        }
    }

    // 4) Supprimer la ligne de la table
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
