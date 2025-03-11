<?php
// db.php – Verbindung zur MySQL-Datenbank herstellen
$host = 'localhost';
$dbname = 'UPDATEVERWALTUNG';
$username = 'imed';
$password = 'imed';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindungsfehler: " . $conn->connect_error);
}
?>
