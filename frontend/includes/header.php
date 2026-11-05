<?php
// session init
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// auth guard
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle     = $pageTitle     ?? 'Dashboard';
$pageSubtitle  = $pageSubtitle  ?? '';
$activePage    = $activePage    ?? 'dashboard';
$headerActions = $headerActions ?? '';

$currentUser = htmlspecialchars($_SESSION['username'] ?? 'User');
$currentRole = htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Staff'));
$initials    = strtoupper(substr($currentUser, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $pageTitle ?> | Sales Tracker</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Inter font (same font as the Figma design) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

  <!-- custom css -->
  <link href="css/style.css" rel="stylesheet">
</head>
<body class="dashboard-layout">

  <!-- sidebar layout -->
  <aside class="sidebar">

    <div class="sidebar-logo">
      <span class="logo-sales">Sales</span>
      <span class="logo-tracker">Tracker</span>
    </div>

    <nav class="sidebar-nav">

      <a href="Dashboard.php" class="nav-item-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <!-- Grid icon -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7"/>
          <rect x="14" y="3" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/>
        </svg>
        <span>Dashboard</span>
      </a>

      <a href="RecordSales.php" class="nav-item-link <?= $activePage === 'record' ? 'active' : '' ?>">
        <!-- Shopping cart icon -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/>
          <circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <span>Record Sale</span>
      </a>

      <a href="Products.php" class="nav-item-link <?= $activePage === 'products' ? 'active' : '' ?>">
        <!-- Box icon -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
        <span>Products &amp; Services</span>
      </a>

      <a href="Sales.php" class="nav-item-link <?= $activePage === 'sales' ? 'active' : '' ?>">
        <!-- Clock icon -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        <span>Sales History</span>
      </a>

      <a href="Analytics.php" class="nav-item-link <?= $activePage === 'analytics' ? 'active' : '' ?>">
        <!-- Bar chart icon -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
        <span>Analytics</span>
      </a>

      <a href="Settings.php" class="nav-item-link <?= $activePage === 'settings' ? 'active' : '' ?>">
        <!-- Settings icon (three sliders) -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="4" y1="21" x2="4" y2="14"/>
          <line x1="4" y1="10" x2="4" y2="3"/>
          <line x1="1" y1="14" x2="7" y2="14"/>
          <line x1="12" y1="21" x2="12" y2="12"/>
          <line x1="12" y1="8" x2="12" y2="3"/>
          <line x1="9" y1="8" x2="15" y2="8"/>
          <line x1="20" y1="21" x2="20" y2="16"/>
          <line x1="20" y1="12" x2="20" y2="3"/>
          <line x1="17" y1="16" x2="23" y2="16"/>
        </svg>
        <span>Settings</span>
      </a>

    </nav>

    <!-- user info -->
    <div class="sidebar-user d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <div class="avatar avatar-blue">
          <?= $initials ?>
        </div>
        <div class="sidebar-user-text">
          <span class="sidebar-user-name"><?= $currentUser ?></span>
          <span class="sidebar-user-role"><?= $currentRole ?></span>
        </div>
      </div>
      <a href="logout.php" class="text-white opacity-75 text-decoration-none" title="Log out">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
      </a>
    </div>

  </aside>

  <!-- ===================== MAIN AREA ===================== -->
  <div class="main">

    <!-- Top bar -->
    <header class="topbar">
      <div>
        <h1 class="topbar-title"><?= $pageTitle ?></h1>
        <?php if ($pageSubtitle !== ''): ?>
          <p class="topbar-subtitle"><?= $pageSubtitle ?></p>
        <?php endif; ?>
      </div>

      <?php if ($headerActions !== ''): ?>
        <div class="topbar-actions"><?= $headerActions ?></div>
      <?php endif; ?>
    </header>

    <!-- Page content starts here (closed in includes/footer.php) -->
    <main class="content">