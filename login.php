<?php
require_once 'config.php';
$title = "Anmeldung - Dorner";
$header = "Anmelden";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db.php';
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM BENUTZER WHERE BENUTZERNAME = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['PASSWORT'])) {
            session_regenerate_id(true);
            if ($user['ROLLE'] === 'admin') {
                $_SESSION['user_role'] = 'admin';
                $_SESSION['username'] = $user['BENUTZERNAME'];
                header("Location: admin.php");
                exit();
            } else {
                $_SESSION['user_role'] = 'user';
                $_SESSION['username'] = $user['BENUTZERNAME'];
                header("Location: user.php");
                exit();
            }
        } else {
            $error = "Ungültige Anmeldedaten.";
        }
    } else {
        $error = "Ungültige Anmeldedaten.";
    }
}
ob_start();
?>
<div class="form-container">
  <h2>Anmeldung</h2>
  <?php if($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form action="" method="post">
    <label for="username">Benutzername:</label>
    <input type="text" name="username" id="username" required>
    <label for="password">Passwort:</label>
    <input type="password" name="password" id="password" required>
    <input type="submit" value="Anmelden" class="btn">
  </form>
  <p>Noch kein Konto? <a href="register.php">Registrieren</a></p>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
