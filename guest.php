<?php
require_once 'config.php';
$_SESSION['user_role'] = 'guest';
$_SESSION['username'] = 'Gast';
header("Location: willkommen.php");
exit();
?>
