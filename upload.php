<?php
require_once 'config.php';
require_once 'db.php';

// Fonction pour convertir des notations comme "5G" en bytes
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

// Vérifier la taille POST
$postMaxSize = return_bytes(ini_get('post_max_size'));
if (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > $postMaxSize) {
    die("Fehler: Die gesamte Datenmenge überschreitet das post_max_size-Limit (" . ini_get('post_max_size') . ").");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Unberechtigter Zugriff.");
}

// Autoriser admin et user
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin','user'])) {
    die("Nur Administratoren und registrierte Benutzer dürfen Dateien hochladen.");
}

// Déterminer le mode d'upload
$upload_mode = $_POST['upload_mode'] ?? 'local';

$targetFile = ""; // Variable qui contiendra le chemin final vers le fichier

$allowed_extensions = ['zip', 'tar', 'gz', 'tgz', 'rar'];

if ($upload_mode === 'internet') {
    // Mode Internet : télécharger le fichier depuis l'URL
    $file_url = trim($_POST['file_url'] ?? '');
    if (empty($file_url) || !filter_var($file_url, FILTER_VALIDATE_URL)) {
        die("Fehler: Ungültige oder fehlende URL.");
    }
    $contents = @file_get_contents($file_url);
    if ($contents === false) {
        die("Fehler: Datei konnte nicht von der URL heruntergeladen werden.");
    }
    // Extraire l'extension à partir de l'URL
    $url_path = parse_url($file_url, PHP_URL_PATH);
    $file_extension = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        die("Fehler: Ungültiger Dateityp.");
    }
    $originalFileName = basename($url_path);
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $newFileName = time() . "_" . $originalFileName;
    $targetFile = $targetDir . $newFileName;
    $bytes_written = file_put_contents($targetFile, $contents);
    if ($bytes_written === false) {
        die("Fehler: Datei konnte nicht gespeichert werden.");
    }
} else {
    // Mode Local : vérifier et déplacer le fichier uploadé
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {

        // Limite 5GB
        $maxSize = 5368709120; // 5 GB
        if ($_FILES['file']['size'] > $maxSize) {
            die("Fehler: Die Datei ist größer als 5 GB.");
        }

        $originalFileName = basename($_FILES['file']['name']);
        $file_extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            die("Fehler: Ungültiger Dateityp.");
        }

        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $newFileName = time() . "_" . $originalFileName;
        $targetFile = $targetDir . $newFileName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            die("Fehler beim Verschieben der Datei.");
        }
    } else {
        // Gérer les erreurs d'upload
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

// Champs du formulaire
$version = $_POST['version'] ?? '';
$release_date = $_POST['release_date'] ?? '';
$comment = $_POST['comment'] ?? '';

// Insertion dans la base de données
$stmt = $conn->prepare("INSERT INTO VERSIONS (VERSION, RELEASE_DATE, DATEIEN, COMMENT) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("Fehler bei der Vorbereitung: " . $conn->error);
}
$stmt->bind_param("ssss", $version, $release_date, $targetFile, $comment);
if ($stmt->execute()) {
    // Redirection vers la page précédente (admin.php ou user.php)
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'user.php';
    header("Location: " . $redirectUrl);
    exit();
} else {
    die("Datenbankfehler: " . $stmt->error);
}
?>
