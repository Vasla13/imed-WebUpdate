<?php
// config.php: Zentrale Konfiguration und Sitzungsverwaltung
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
