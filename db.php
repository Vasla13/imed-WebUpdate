<?php
require_once 'config.php';
// db.php – Verbindung zur MySQL-Datenbank
$host = 'localhost';
$dbname = 'UPDATEVERWALTUNG';
$username = 'imed';
$password = 'imed';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    log_error("Datenbankverbindungsfehler: " . $conn->connect_error);
    die("Verbindungsfehler zur Datenbank.");
}
?>
