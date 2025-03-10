<?php
require_once 'config.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Unberechtigter Zugriff.");
}

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','user'])) {
    die("Nur Administratoren und registrierte Benutzer dürfen Dateien hochladen.");
}

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

$postMaxSize = return_bytes(ini_get('post_max_size'));
if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $postMaxSize) {
    die("Fehler: Die gesamte Datenmenge überschreitet das post_max_size-Limit (" . ini_get('post_max_size') . ").");
}

$upload_mode = $_POST['upload_mode'] ?? 'local';

if ($upload_mode === 'internet') {
    $file_url = trim($_POST['file_url'] ?? '');
    if (empty($file_url) || !filter_var($file_url, FILTER_VALIDATE_URL)) {
        die("Fehler: Ungültige oder fehlende URL.");
    }
    $targetFile = $file_url;
} else {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $maxSize = 5368709120; // 5 GB
        if ($_FILES['file']['size'] > $maxSize) {
            die("Fehler: Die Datei ist größer als 5 GB.");
        }
        $allowed_extensions = ['zip', 'tar', 'gz', 'tgz', 'rar'];
        $originalFileName = basename($_FILES['file']['name']);
        $file_extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            die("Fehler: Ungültiger Dateityp.");
        }
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                log_error("Fehler: Upload-Verzeichnis konnte nicht erstellt werden.");
                die("Fehler beim Erstellen des Upload-Verzeichnisses.");
            }
        }
        $cleanName = preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $originalFileName);
        $newFileName = time() . "_" . $cleanName;
        $targetFile = $targetDir . $newFileName;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            log_error("Fehler beim Verschieben der Datei: " . $originalFileName);
            die("Fehler beim Verschieben der Datei.");
        }
    } else {
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
        log_error("Upload-Fehler: " . $error_message);
        die($error_message);
    }
}

$version = $_POST['version'] ?? '';
$release_date = $_POST['release_date'] ?? '';
$comment = $_POST['comment'] ?? '';

$stmt = $conn->prepare("INSERT INTO VERSIONEN (VERSION, VEROEFFENTLICHUNGSDATUM, DATEIPFAD, KOMMENTAR) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    log_error("Fehler bei der Vorbereitung des SQL-Statements: " . $conn->error);
    die("Fehler bei der Vorbereitung.");
}
$stmt->bind_param("ssss", $version, $release_date, $targetFile, $comment);
if ($stmt->execute()) {
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'user.php';
    header("Location: " . $redirectUrl);
    exit();
} else {
    log_error("Datenbankfehler beim Einfügen: " . $stmt->error);
    die("Datenbankfehler.");
}
?>
