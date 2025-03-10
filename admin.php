<?php
require_once 'config.php';

// Überprüfen, ob der Benutzer ein Admin ist
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$title = "Admin - Dorner";
$header = "Administrationsbereich";
require_once 'db.php';

// Alle Versionen abrufen
$sql = "SELECT * FROM VERSIONS";
$result = $conn->query($sql);

ob_start();
?>
<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <!-- Die ID-Spalte ist ausgeblendet -->
        <th>Version</th>
        <th>Veröffentlichungsdatum</th>
        <th>Datei</th>
        <th>Kommentar</th>
        <th>Status</th>
        <th>Link</th>
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()):
          // Status abrufen (0, falls nicht definiert)
          $status = isset($row['installation_status']) ? (int)$row['installation_status'] : 0;

          // Button/nextStep basierend auf dem Status bestimmen
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
          
          // Link generieren, falls Status = 3, mit dynamischer Server-IP
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
        <!-- ID-Spalte weggelassen -->
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
            <a href="edit_version.php?id=<?= $row['ID']; ?>" class="btn">
              <i class="fas fa-edit"></i> Bearbeiten
            </a>
            <a href="delete_version.php?id=<?= $row['ID']; ?>" class="btn"
               onclick="return confirm('Möchten Sie diese Version wirklich löschen?')">
               <i class="fas fa-trash-alt"></i> Löschen
            </a>
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

<!-- Modales Fenster für "Version hinzufügen" -->
<div id="myModal" class="modal">
  <div class="modal-box">
    <!-- Schließen-Button -->
    <button class="close-modal" aria-label="Close">&times;</button>
    <h2>Version hinzufügen</h2>
    <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" class="version-form">
      <!-- Auswahl des Upload-Modus -->
      <div class="form-group">
        <label>Upload-Modus:</label>
        <div class="toggle-container">
          <input type="radio" id="local" name="upload_mode" value="local" checked>
          <label for="local">Local</label>
          <input type="radio" id="internet" name="upload_mode" value="internet">
          <label for="internet">Internet</label>
        </div>
      </div>
      <!-- Bereich für lokalen Upload -->
      <div id="localUpload" class="form-group">
        <label for="file">Datei:</label>
        <div id="uploadDropZone" class="upload-dropzone">
          <p>Datei hierher ziehen oder klicken</p>
          <input type="file" name="file" id="file" class="dropzone-input">
        </div>
      </div>
      <!-- Bereich für Internet-Upload (URL) -->
      <div id="internetUpload" class="form-group" style="display: none;">
        <label for="file_url">Datei URL:</label>
        <input type="text" name="file_url" id="file_url" placeholder="https://example.com/file.zip">
      </div>
      <div class="form-group">
        <label for="version">Version:</label>
        <input type="text" name="version" id="version" required>
      </div>
      <div class="form-group">
        <label for="release_date">Veröffentlichungsdatum:</label>
        <input type="date" name="release_date" id="release_date" required>
      </div>
      <div class="form-group">
        <label for="comment">Kommentar:</label>
        <textarea name="comment" id="comment" rows="4"></textarea>
      </div>
      <div class="form-actions">
        <input type="submit" value="Hochladen" class="btn">
      </div>
    </form>
  </div>
</div>

<script>
  // Umschaltung zwischen Local und Internet
  document.getElementById("local").addEventListener("change", function() {
    if (this.checked) {
      document.getElementById("localUpload").style.display = "block";
      document.getElementById("internetUpload").style.display = "none";
    }
  });
  document.getElementById("internet").addEventListener("change", function() {
    if (this.checked) {
      document.getElementById("localUpload").style.display = "none";
      document.getElementById("internetUpload").style.display = "block";
    }
  });

  // Öff
