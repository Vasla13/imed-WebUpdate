<?php
require_once 'config.php';
require_once 'db.php';

// Funktion zur Umrechnung von Angaben wie "5G" in Bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    switch ($last) {
        case 'g': $val *= 1024 * 1024 * 1024; break;
        case 'm': $val *= 1024 * 1024; break;
        case 'k': $val *= 1024; break;
    }
    return $val;
}

// Überprüfen der POST-Datenmenge
$postMaxSize = return_bytes(ini_get('post_max_size'));
if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $postMaxSize) {
    die("Fehler: Die gesamte Datenmenge überschreitet das post_max_size-Limit (" . ini_get('post_max_size') . ").");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Unberechtigter Zugriff.");
}

// Nur Administratoren und registrierte Benutzer dürfen Dateien hochladen
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','user'])) {
    die("Nur Administratoren und registrierte Benutzer dürfen Dateien hochladen.");
}

// Upload-Modus bestimmen
$upload_mode = $_POST['upload_mode'] ?? 'local';

if ($upload_mode === 'internet') {
    // Internet-Modus: URL abrufen und validieren
    $file_url = trim($_POST['file_url'] ?? '');
    if (empty($file_url) || !filter_var($file_url, FILTER_VALIDATE_URL)) {
        die("Fehler: Ungültige oder fehlende URL.");
    }
    // Die URL wird direkt in der Datenbank gespeichert
    $targetFile = $file_url;
} else {
    // Lokaler Modus: Hochgeladene Datei verarbeiten
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {

        // Limit 5GB
        $maxSize = 5368709120; // 5 GB
        if ($_FILES['file']['size'] > $maxSize) {
            die("Fehler: Die Datei ist größer als 5 GB.");
        }

        // Erlaubte Dateiendungen
        $allowed_extensions = ['zip', 'tar', 'gz', 'tgz', 'rar'];
        $originalFileName = basename($_FILES['file']['name']);
        $file_extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            die("Fehler: Ungültiger Dateityp.");
        }

        // Upload-Verzeichnis
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Eindeutiger Dateiname
        $newFileName = time() . "_" . $originalFileName;
        $targetFile = $targetDir . $newFileName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            die("Fehler beim Verschieben der Datei.");
        }
    } else {
        // Fehler beim Upload behandeln
        $error_code = isset($_FILES['file']) ? $_FILES['file']['error'] : "Keine Datei hochgeladen";
        $error_message = "";
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "Die Datei überschreitet das durch php.ini definierte Upload-Limit.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "Die Datei überschreitet das im Formular definierte Limit.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "Die Datei wurde nur teilweise hochgeladen.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "Es wurde keine Datei hochgeladen.";
                break;
            default:
                $error_message = "Fehler beim Upload. Fehlercode: " . $error_code;
        }
        die($error_message);
    }
}

// Formularfelder
$version = $_POST['version'] ?? '';
$release_date = $_POST['release_date'] ?? '';
$comment = $_POST['comment'] ?? '';

// Einfügen in die Datenbank
$stmt = $conn->prepare("INSERT INTO VERSIONS (VERSION, RELEASE_DATE, DATEIEN, COMMENT) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("Fehler bei der Vorbereitung: " . $conn->error);
}
$stmt->bind_param("ssss", $version, $release_date, $targetFile, $comment);
if ($stmt->execute()) {
    // Weiterleitung zur vorherigen Seite (admin.php oder user.php)
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'user.php';
    header("Location: " . $redirectUrl);
    exit();
} else {
    die("Datenbankfehler: " . $stmt->error);
}
?>
