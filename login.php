<?php
require_once 'config.php';
$title = "Anmeldung - Dorner";
$header = "Anmelden";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db.php';
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM USERS WHERE USERNAME = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($user['ROLE'] === 'admin') {
            if (password_verify($password, $user['PASSWORD'])) {
                $_SESSION['user_role'] = 'admin';
                $_SESSION['username'] = $user['USERNAME'];
                header("Location: admin.php");
                exit();
            } else {
                $error = "Falsches Passwort (Admin).";
            }
        } else {
            if (password_verify($password, $user['PASSWORD'])) {
                $_SESSION['user_role'] = 'user';
                $_SESSION['username'] = $user['USERNAME'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Falsches Passwort.";
            }
        }
    } else {
        $error = "Benutzer nicht gefunden.";
    }
}
ob_start();
?>
<div class="form-container">
  <h2>Anmeldung</h2>
  <?php if($error): ?>
    <p class="error"><?= $error ?></p>
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
