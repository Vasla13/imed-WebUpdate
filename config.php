<?php
// config.php: Zentrale Konfiguration und Sitzungsverwaltung (alles in Deutsch)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fehlerprotokollierung: Erstelle einen Ordner "logs", falls nicht vorhanden
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
function log_error($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . "\n", 3, __DIR__ . '/logs/error.log');
}
?>
