<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title><?php echo isset($title) ? $title : 'Dorner - Update Verwaltung'; ?></title>
  <link rel="stylesheet" href="style.css">
  <!-- Google Fonts: Raleway & Orbitron -->
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;500;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
</head>
<body>
  <div class="layout">
    <!-- Seitenleiste -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <h2 class="logo-text">Dorner<span>IT</span></h2>
      </div>
      <nav class="sidebar-nav">
        <ul class="sidebar-menu">
          <li><a href="willkommen.php"><i class="fas fa-home"></i><span>Startseite</span></a></li>
          <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li><a href="admin.php"><i class="fas fa-user-shield"></i><span>Admin</span></a></li>
          <?php endif; ?>
          <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
            <li><a href="user.php"><i class="fas fa-user"></i><span>User</span></a></li>
          <?php endif; ?>
        </ul>
      </nav>
      <div class="sidebar-footer">
        <?php if(isset($_SESSION['username']) && !empty($_SESSION['username'])): ?>
          <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Hauptinhalt -->
    <main class="main-content">
      <?php if(isset($breadcrumb)): ?>
        <div class="breadcrumb">
          <?php echo $breadcrumb; ?>
        </div>
      <?php endif; ?>
      <header class="main-header">
        <h1><?php echo isset($header) ? $header : 'Dorner - Update Verwaltung'; ?></h1>
      </header>
      
      <!-- Aktionsbereich für Admin und User -->
      <?php if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'user'])): ?>
      <div class="action-buttons" style="text-align: left; margin: 20px 0;">
          <a href="#" id="openModalBtn" class="btn" style="margin-right: 10px;">
            <i class="fas fa-plus"></i> Version hinzufügen
          </a>
      </div>
      <?php endif; ?>
      
      <section class="content">
        <?php echo $content; ?>
      </section>
      <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> Dorner - Update Verwaltung</p>
      </footer>
    </main>
  </div>
</body>
</html>
