<?php
// db.php – Connexion à la base de données MySQL
$host = 'localhost';
$dbname = 'UPDATEVERWALTUNG';
$username = 'imed';
$password = 'imed';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindungsfehler: " . $conn->connect_error);
}
?>
