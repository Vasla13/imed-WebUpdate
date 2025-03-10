<?php
require_once 'config.php';
session_unset();
session_destroy();

$title = "Startseite - Dorner";
$header = "Willkommen bei Dorner";
ob_start();
?>
<div class="home-container">
  <div class="hero-glass">
    <h1>Willkommen bei <span>Dorner</span></h1>
    <p>Ihre Plattform für moderne Update-Verwaltung.</p>
    <div class="options">
      <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Anmelden</a>
      <a href="register.php" class="btn"><i class="fas fa-user-plus"></i> Registrieren</a>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
