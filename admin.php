<?php
require_once 'config.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$title = "Admin - Dorner";
$header = "Administrationsbereich";
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
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()):
          // Récupération du statut (0 si non défini)
          $status = isset($row['installation_status']) ? (int)$row['installation_status'] : 0;

          // Déterminer le bouton/nextStep
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
          
          // Générer le lien si status = 3 avec l'IP dynamique du serveur
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

<!-- Fenêtre modale pour "Version Hinzufügen" -->
<div id="myModal" class="modal">
  <div class="modal-box">
    <button class="close-modal" aria-label="Close">&times;</button>
    <h2>Version Hinzufügen</h2>
    <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" class="version-form">
      <div id="uploadDropZone" class="upload-dropzone">
        <p>Datei hierher ziehen oder klicken</p>
        <input type="file" name="file" id="file" class="dropzone-input">
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
  // Ouverture/Fermeture de la modale
  const modal = document.getElementById("myModal");
  const openBtn = document.getElementById("openModalBtn");
  const closeBtn = document.querySelector(".close-modal");

  if (openBtn) {
    openBtn.addEventListener("click", () => modal.classList.add("active"));
  }
  if (closeBtn) {
    closeBtn.addEventListener("click", () => modal.classList.remove("active"));
  }
  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.classList.remove("active");
    }
  });

  // Drag & Drop pour l'upload
  const dropZone = document.getElementById('uploadDropZone');
  const fileInput = document.getElementById('file');

  if (dropZone && fileInput) {
    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', (e) => {
      e.preventDefault();
      dropZone.classList.remove('dragover');
    });
    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.classList.remove('dragover');
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        dropZone.querySelector('p').textContent = e.dataTransfer.files[0].name;
      }
    });

    fileInput.addEventListener('change', () => {
      if (fileInput.files.length) {
        dropZone.querySelector('p').textContent = fileInput.files[0].name;
      }
    });
  }
</script>

<?php
$content = ob_get_clean();
include 'base.php';
?>
