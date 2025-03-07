<?php
require_once 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$title = "Benutzer - Dorner";
$header = "Versionen Übersicht";
require_once 'db.php';

// Récupérer toutes les versions
$sql = "SELECT * FROM VERSIONS";
$result = $conn->query($sql);

ob_start();
?>
<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Version</th>
        <th>Veröffentlichungsdatum</th>
        <th>Datei</th>
        <th>Kommentar</th>
        <th>Status</th>
        <th>Link</th>
        <th>Aktion</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()):
          $status = isset($row['installation_status']) ? (int)$row['installation_status'] : 0;
          if ($status === 0) {
              $nextStep = 1;
              $btnText = "Extraktion starten";
          } elseif ($status === 1) {
              $nextStep = 2;
              $btnText = "Konfiguration starten";
          } elseif ($status === 2) {
              $nextStep = 3;
              $btnText = "Zur Webseite";
          } else {
              $nextStep = 3;
              $btnText = "Installation abgeschlossen";
          }
          
          $siteLink = "";
          if ($status === 3) {
              $archivePath = $row['DATEIEN'];
              $pattern = '/imedWeb_([0-9.]+)_p[0-9]+_gh/i';
              if (preg_match($pattern, $archivePath, $matches)) {
                  $extractedFolder = "imed-Web_" . $matches[1] . "_gh";
                  $server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
                  $siteLink = "http://{$server_ip}/{$extractedFolder}/imed-Info/framework.php";
              } else {
                  $siteLink = "#";
              }
          }
      ?>
      <tr>
        <td><?= htmlspecialchars($row['ID']); ?></td>
        <td><?= htmlspecialchars($row['VERSION']); ?></td>
        <td><?= htmlspecialchars($row['RELEASE_DATE']); ?></td>
        <td>
          <a href="<?= htmlspecialchars($row['DATEIEN']); ?>" download class="download-btn">
            <i class="fas fa-download"></i> Herunterladen
          </a>
        </td>
        <td><?= htmlspecialchars($row['COMMENT']); ?></td>
        <td><?= htmlspecialchars($row['installation_status']); ?></td>
        <td>
          <?php if ($status === 3 && !empty($siteLink) && $siteLink !== "#"): ?>
            <a href="<?= htmlspecialchars($siteLink); ?>" class="btn" target="_blank" title="Webseite öffnen">
              <i class="fas fa-globe"></i>
            </a>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>
        <td>
          <div class="actions">
            <?php if ($status < 3): ?>
               <a href="run_install.php?version_id=<?= $row['ID']; ?>&step=<?= $nextStep; ?>" class="btn">
                 <i class="fas fa-play"></i> <?= $btnText; ?>
               </a>
            <?php else: ?>
               <button class="btn" disabled>
                 <i class="fas fa-check-circle"></i> <?= $btnText; ?>
               </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
