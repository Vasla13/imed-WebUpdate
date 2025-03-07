<?php
require_once 'config.php';
$title = "Historie der Versionen - Update Verwaltung";
$header = "Historie der Versionen";
$breadcrumb = '<a href="willkommen.php">Startseite</a> > Historie';
require_once 'db.php';

$sql = "SELECT * FROM VERSIONS ORDER BY RELEASE_DATE DESC";
$result = $conn->query($sql);
ob_start();
?>
<div class="changelog">
  <h2>Historie der Versionen</h2>
  <div class="timeline">
    <?php
    if($result && $result->num_rows > 0) {
      while($version = $result->fetch_assoc()){
        ?>
        <div class="timeline-item">
          <div class="timeline-icon"><i class="fa-solid fa-code"></i></div>
          <div class="timeline-content">
            <h3>Version <?= htmlspecialchars($version['VERSION']) ?></h3>
            <span class="timeline-date"><?= htmlspecialchars($version['RELEASE_DATE']) ?></span>
            <p><?= htmlspecialchars($version['COMMENT']) ?></p>
            <a href="<?= htmlspecialchars($version['DATEIEN']) ?>" download class="btn">
              <i class="fa-solid fa-download"></i> Herunterladen
            </a>
          </div>
        </div>
        <?php
      }
    } else {
      echo "<p>Keine Version gefunden.</p>";
    }
    ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
