<?php
require_once 'config.php';
session_destroy();
header("Location: willkommen.php");
exit();
?>
