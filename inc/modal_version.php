<?php
// inc/modal_version.php
?>
<div id="myModal" class="modal">
  <div class="modal-box">
    <button class="close-modal" aria-label="Schließen">&times;</button>
    <h2>Version hinzufügen</h2>
    <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" class="version-form">
      <div class="form-group">
        <label>Upload-Modus:</label>
        <div class="toggle-container">
          <input type="radio" id="local" name="upload_mode" value="local" checked>
          <label for="local">Lokal</label>
          <input type="radio" id="internet" name="upload_mode" value="internet">
          <label for="internet">Internet</label>
        </div>
      </div>
      <div id="localUpload" class="form-group">
        <label for="file">Datei:</label>
        <div id="uploadDropZone" class="upload-dropzone">
          <p>Datei hierher ziehen oder klicken</p>
          <input type="file" name="file" id="file" class="dropzone-input">
        </div>
      </div>
      <div id="internetUpload" class="form-group" style="display: none;">
        <label for="file_url">Datei URL:</label>
        <input type="text" name="file_url" id="file_url" placeholder="https://example.com/datei.zip">
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
  // Umschaltung zwischen lokal und Internet-Upload
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
  // Öffnen/Schließen des Modals
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
  // Drag & Drop für den lokalen Upload
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
