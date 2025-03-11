<?php
require_once 'config.php';
$title = "Registrierung - Dorner";
$header = "Konto erstellen";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db.php';
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Der Benutzername "admin" ist reserviert
    if (strtolower($username) === "admin") {
        $error = "Der Benutzername 'admin' ist reserviert.";
    } else {
        // Passwort hashen
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $role = 'user';
        $stmt = $conn->prepare("INSERT INTO USERS (USERNAME, PASSWORD, ROLE) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed, $role);
        if($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Fehler bei der Kontoerstellung.";
        }
    }
}
ob_start();
?>
<div class="form-container">
  <h2>Registrierung</h2>
  <?php if($error): ?>
    <p class="error"><?= $error ?></p>
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
