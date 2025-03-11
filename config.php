<?php
// config.php: Configuration centrale et gestion des sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
