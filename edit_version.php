<?php
require_once 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}
$title = "Version bearbeiten - Update Verwaltung";
$header = "Version bearbeiten";
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM VERSIONEN WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
       $versionData = $result->fetch_assoc();
    } else {
       die("Version nicht gefunden.");
    }
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $version = $_POST['version'];
    $release_date = $_POST['release_date'];
    $comment = $_POST['comment'];
    $stmt = $conn->prepare("UPDATE VERSIONEN SET VERSION = ?, VEROEFFENTLICHUNGSDATUM = ?, KOMMENTAR = ? WHERE ID = ?");
    $stmt->bind_param("sssi", $version, $release_date, $comment, $id);
    if ($stmt->execute()) {
       header("Location: admin.php");
       exit();
    } else {
       $error = "Fehler beim Aktualisieren.";
       log_error("Update-Fehler: " . $stmt->error);
    }
}
ob_start();
?>
<div class="form-container">
  <h2>Version bearbeiten</h2>
  <?php if ($error) { echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; } ?>
  <form method="post" action="">
    <label for="version">Version:</label>
    <input type="text" name="version" id="version" value="<?= htmlspecialchars($versionData['VERSION']); ?>" required>
    <label for="release_date">Veröffentlichungsdatum:</label>
    <input type="date" name="release_date" id="release_date" value="<?= htmlspecialchars($versionData['VEROEFFENTLICHUNGSDATUM']); ?>" required>
    <label for="comment">Kommentar:</label>
    <textarea name="comment" id="comment" rows="4"><?= htmlspecialchars($versionData['KOMMENTAR']); ?></textarea>
    <input type="submit" value="Aktualisieren" class="btn">
  </form>
  <a href="admin.php" class="btn">Zurück</a>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
