<?php
require_once 'config.php';
$title = "Versionenliste - Dorner";
$header = "Verfügbare Versionen";
require_once 'db.php';

// Filtrer
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Construire la requête
$sql = "SELECT * FROM VERSIONS WHERE 1=1";
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $sql .= " AND (VERSION LIKE ? OR COMMENT LIKE ?)";
    $likeTerm = '%' . $searchTerm . '%';
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= 'ss';
}
if (!empty($dateStart)) {
    $sql .= " AND RELEASE_DATE >= ?";
    $params[] = $dateStart;
    $types .= 's';
}
if (!empty($dateEnd)) {
    $sql .= " AND RELEASE_DATE <= ?";
    $params[] = $dateEnd;
    $types .= 's';
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

ob_start();
?>
<div class="table-responsive">
  <!-- Barre de filtre -->
  <form method="get" action="index.php" class="fancy-filter-bar underline-neon">
    <div class="filter-item">
      <label for="search" class="filter-icon"><i class="fas fa-search"></i></label>
      <input type="text" name="search" id="search" placeholder="Suchen..." value="<?= htmlspecialchars($searchTerm) ?>">
    </div>
    <div class="filter-item">
      <label for="date_start" class="filter-icon"><i class="fas fa-calendar-alt"></i></label>
      <input type="date" name="date_start" id="date_start" placeholder="Start..." value="<?= htmlspecialchars($dateStart) ?>">
    </div>
    <div class="filter-item">
      <label for="date_end" class="filter-icon"><i class="fas fa-calendar-alt"></i></label>
      <input type="date" name="date_end" id="date_end" placeholder="Ende..." value="<?= htmlspecialchars($dateEnd) ?>">
    </div>
    <button type="submit" class="btn fancy-filter-btn">
      <i class="fas fa-filter"></i> Filtern
    </button>
  </form>

  <!-- Table des versions -->
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Version</th>
        <th>Veröffentlichungsdatum</th>
        <th>Datei</th>
        <th>Kommentar</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
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
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5">Keine Version verfügbar.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
include 'base.php';
?>
