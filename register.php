<?php
require_once 'config.php';
$title = "Registrierung - Dorner";
$header = "Konto erstellen";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db.php';
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Der Name "admin" ist reserviert
    if (strtolower($username) === "admin") {
        $error = "Der Benutzername 'admin' ist reserviert.";
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $rolle = 'user';
        $stmt = $conn->prepare("INSERT INTO BENUTZER (BENUTZERNAME, PASSWORT, ROLLE) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed, $rolle);
        if($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Fehler bei der Kontoerstellung.";
            log_error("Registrierungsfehler: " . $stmt->error);
        }
    }
}
ob_start();
?>
<div class="form-container">
  <h2>Registrierung</h2>
  <?php if($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form action="" method="post">
    <label for="username">Benutzername:</label>
    <input type="text" name="username" id="username" required>
    <label for="password">Passwort:</label>
    <input type="password" name="password" id="password" required>
    <input type="submit" value="Registrieren" class="btn">
  </form>
  <p>Bereits registriert? <a href="login.php">Anmelden</a></p>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
