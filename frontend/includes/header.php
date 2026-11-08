<?php
// session init
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../../backend/api/routes.php';

// auth guard — protected routes (see backend/api/routes.php)
route_require_page();

$pageTitle     = $pageTitle     ?? 'Dashboard';
$pageSubtitle  = $pageSubtitle  ?? '';
$activePage    = $activePage    ?? 'dashboard';
$headerActions = $headerActions ?? '';

$currentUserRaw = (string)($_SESSION['username'] ?? 'User');
$currentUser = htmlspecialchars($currentUserRaw);
$currentRole = htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Staff'));
$currentRoleKey = $_SESSION['role'] ?? 'staff';
$initials    = htmlspecialchars(strtoupper(substr($currentUserRaw, 0, 2)));
?>
<!DOCTYPE html>
<html lang="en" style="color-scheme: light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Sales Tracker dashboard — manage products, sales, and reports.">
  <meta name="theme-color" content="#ffffff">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
  <title><?= htmlspecialchars($pageTitle) ?> | Sales Tracker</title>

  <!-- Bootstrap 5 + Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">

  <!-- Inter font (same font as the Figma design) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

  <!-- custom css -->
  <link href="css/style.css" rel="stylesheet">
  <script>
  (function(){try{var mq991=window.matchMedia('(max-width:991.98px)');var mq1199=window.matchMedia('(max-width:1199.98px)');if(!mq991.matches){var stored=null;try{stored=localStorage.getItem('st-sidebar-collapsed');}catch(e){} var auto=mq1199.matches; if(auto||stored==='1'){document.documentElement.classList.add('sidebar-collapsed');}}}catch(e){}})();
  </script>
</head>
<body class="dashboard-layout">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <script src="js/script.js"></script>
  <script>
    window.APP_USER = <?= json_encode([
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'username' => $_SESSION['username'] ?? '',
        'role' => $currentRoleKey,
        'businessId' => (int)($_SESSION['business_id'] ?? 0),
        'mustChangePassword' => (int)($_SESSION['must_change_password'] ?? 0)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>

    <!-- sidebar layout -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
  <aside class="sidebar" id="appSidebar">

    <div class="sidebar-logo">
      <span class="logo-sales">Sales</span>
      <span class="logo-tracker">Tracker</span>
      <span class="logo-monogram" aria-hidden="true">ST</span>
      <button type="button" class="sidebar-collapse-btn" id="sidebarCollapse" aria-label="Collapse sidebar" aria-controls="appSidebar" aria-expanded="false" data-tooltip="Collapse sidebar" title="Collapse sidebar">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
      </button>
    </div>

    <nav class="sidebar-nav" aria-label="Dashboard navigation">

      <a href="Dashboard.php" class="nav-item-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" data-tooltip="Dashboard" aria-label="Dashboard" <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
        <span>Dashboard</span>
      </a>

      <a href="RecordSales.php" class="nav-item-link <?= $activePage === 'record' ? 'active' : '' ?>" data-tooltip="Record Sale" aria-label="Record Sale" <?= $activePage === 'record' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
        <span>Record Sale</span>
      </a>

      <a href="Products.php" class="nav-item-link <?= $activePage === 'products' ? 'active' : '' ?>" data-tooltip="Products &amp; Services" aria-label="Products and Services" <?= $activePage === 'products' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
        <span>Products &amp; Services</span>
      </a>

      <a href="Sales.php" class="nav-item-link <?= $activePage === 'sales' ? 'active' : '' ?>" data-tooltip="Sales History" aria-label="Sales History" <?= $activePage === 'sales' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
        <span>Sales History</span>
      </a>

      <a href="Analytics.php" class="nav-item-link <?= $activePage === 'analytics' ? 'active' : '' ?>" data-tooltip="Analytics" aria-label="Analytics" <?= $activePage === 'analytics' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
        <span>Analytics</span>
      </a>

      <a href="Settings.php" class="nav-item-link <?= $activePage === 'settings' ? 'active' : '' ?>" data-tooltip="Settings" aria-label="Settings" <?= $activePage === 'settings' ? 'aria-current="page"' : '' ?>>
        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
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
      <form method="post" action="logout.php" class="m-0">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button type="submit" class="btn btn-link text-white opacity-75 text-decoration-none p-1" title="Log out" aria-label="Log out" data-tooltip="Log out">
          <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
        </button>
      </form>
    </div>
    <div class="sidebar-shortcut-hint" aria-hidden="true"><kbd>Ctrl</kbd>+<kbd>K</kbd> jump &middot; <kbd>/</kbd> search</div>

  </aside>

  <!-- ===================== MAIN AREA ===================== -->
  <div class="main">

    <!-- Top bar -->
    <header class="topbar">
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-dark btn-sm d-lg-none sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation" aria-controls="appSidebar" aria-expanded="false">
          <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div>
          <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
          <?php if ($pageSubtitle !== ''): ?>
            <p class="topbar-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="topbar-actions d-flex align-items-center gap-2">
        <a href="RecordSales.php" class="btn btn-pill active text-decoration-none" id="topbarRecordBtn"><i class="fa-solid fa-circle-plus me-1" aria-hidden="true"></i>Record sale</a>
        <span id="businessName" class="text-muted d-none" style="font-size:.8rem;"></span>
        <select id="businessSwitcher" class="form-select form-select-sm ui-input d-none" style="width:auto;" aria-label="Active business"></select>
        <?= $headerActions ?>
      </div>
    </header>

    <!-- Page content starts here (closed in includes/footer.php) -->
    <main class="content" id="main-content">
    <div id="toastRegion" role="status" aria-live="polite" aria-atomic="false"></div>
    <div id="jumpPalette" hidden role="dialog" aria-modal="true" aria-label="Jump to page or action">
      <div class="jump-box">
        <input type="text" id="jumpInput" class="jump-input" role="combobox" aria-expanded="true" aria-controls="jumpList" aria-autocomplete="list" placeholder="Jump to page or action..." autocomplete="off">
        <ul class="jump-list" id="jumpList" role="listbox" aria-label="Pages and actions">
          <li class="jump-item" role="option" data-href="Dashboard.php" data-label="Dashboard" data-search="dashboard home overview"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span>Dashboard</span></li>
          <li class="jump-item" role="option" data-href="RecordSales.php" data-label="Record Sale" data-search="record sale new transaction cart"><i class="fa-solid fa-cart-plus" aria-hidden="true"></i><span>Record Sale</span></li>
          <li class="jump-item" role="option" data-href="Products.php" data-label="Products & Services" data-search="products services catalog inventory"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Products &amp; Services</span></li>
          <li class="jump-item" role="option" data-href="Products.php" data-label="Add product" data-search="add product new item create catalog"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i><span>Add product</span></li>
          <li class="jump-item" role="option" data-href="Sales.php" data-label="Sales History" data-search="sales history transactions receipts"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Sales History</span></li>
          <li class="jump-item" role="option" data-href="Analytics.php" data-label="Analytics" data-search="analytics reports charts revenue"><i class="fa-solid fa-chart-column" aria-hidden="true"></i><span>Analytics</span></li>
          <li class="jump-item" role="option" data-href="Settings.php" data-label="Settings" data-search="settings team password business"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Settings</span></li>
        </ul>
      </div>
    </div>

<script>
    (function () {
        var toggle = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');
        var sidebar = document.getElementById('appSidebar');

        function setSidebar(open, returnFocus) {
            document.body.classList.toggle('sidebar-open', open);
            if (backdrop) backdrop.hidden = !open;
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!open && returnFocus && toggle && window.matchMedia('(max-width:991.98px)').matches) {
                toggle.focus();
            }
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                setSidebar(!document.body.classList.contains('sidebar-open'), false);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () { setSidebar(false, false); });
        }
        if (sidebar) {
            sidebar.querySelectorAll('.nav-item-link').forEach(function (link) {
                link.addEventListener('click', function () { setSidebar(false, false); });
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                setSidebar(false, true);
            }
        });
    })();
</script>
<script>
(function () {
    var select = document.getElementById('businessSwitcher');
    var label = document.getElementById('businessName');

    function roleLabel(role) {
        return role ? role.charAt(0).toUpperCase() + role.slice(1) : '';
    }

    Api.get('auth.php').then(function (data) {
        if (!data.authenticated) return;

        var list = data.businesses || [];
        var activeId = Number(data.active_business_id);
        var active = null;

        list.forEach(function (b) {
            if (Number(b.id) === activeId) active = b;
        });

        if (label && active) {
            label.textContent = active.name + (active.role ? ' (' + roleLabel(active.role) + ')' : '');
            label.classList.remove('d-none');
        }

        if (list.length < 2 || !select) return;

        list.forEach(function (b) {
            var option = document.createElement('option');
            option.value = String(b.id);
            option.textContent = b.name + ' — ' + roleLabel(b.role);
            select.appendChild(option);
        });

        select.value = String(activeId);
        select.classList.remove('d-none');

        select.addEventListener('change', function () {
            Api.post('auth.php', { action: 'switch_business', business_id: Number(select.value) })
                .then(function () { window.location.reload(); })
                .catch(function (err) {
                    if (typeof showToast === 'function') { showToast(err.message, true); } else { window.alert(err.message); }
                    select.value = String(activeId);
                });
        });
    }).catch(function () {});
})();
</script>