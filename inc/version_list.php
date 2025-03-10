<?php
// inc/version_list.php
// Funktion zur Darstellung der Versionen in einer HTML-Tabelle
function renderVersionList($result, $isAdmin) {
    ?>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Version</th>
            <th>Veröffentlichungsdatum</th>
            <th>Datei</th>
            <th>Kommentar</th>
            <th>Status</th>
            <th>Link</th>
            <?php if($isAdmin): ?>
            <th>Aktionen</th>
            <?php else: ?>
            <th>Aktion</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()):
              // Standardisierte Spaltennamen: DATEIPFAD, INSTALLATIONSSTATUS, VEROEFFENTLICHUNGSDATUM, KOMMENTAR
              $status = isset($row['INSTALLATIONSSTATUS']) ? (int)$row['INSTALLATIONSSTATUS'] : 0;
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
                  $filePath = $row['DATEIPFAD'];
                  $pattern = '/imedWeb_([0-9.]+)_p[0-9]+_gh/i';
                  if (preg_match($pattern, $filePath, $matches)) {
                      $extractedFolder = "imed-Web_" . $matches[1] . "_gh";
                      $server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
                      $siteLink = "http://{$server_ip}/{$extractedFolder}/imed-Info/framework.php";
                  } else {
                      $siteLink = "#";
                  }
              }
          ?>
          <tr>
            <td><?= htmlspecialchars($row['VERSION']); ?></td>
            <td><?= htmlspecialchars($row['VEROEFFENTLICHUNGSDATUM']); ?></td>
            <td>
              <a href="<?= htmlspecialchars($row['DATEIPFAD']); ?>" download class="download-btn">
                <i class="fas fa-download"></i> Herunterladen
              </a>
            </td>
            <td><?= htmlspecialchars($row['KOMMENTAR']); ?></td>
            <td><?= htmlspecialchars($row['INSTALLATIONSSTATUS']); ?></td>
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
                <?php if ($isAdmin): ?>
                  <a href="edit_version.php?id=<?= $row['ID']; ?>" class="btn">
                    <i class="fas fa-edit"></i> Bearbeiten
                  </a>
                  <a href="delete_version.php?id=<?= $row['ID']; ?>" class="btn"
                     onclick="return confirm('Möchten Sie diese Version wirklich löschen?')">
                     <i class="fas fa-trash-alt"></i> Löschen
                  </a>
                <?php endif; ?>
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
}
?>
