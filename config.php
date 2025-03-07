<?php
// config.php: Zentrale Konfiguration und Session-Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
