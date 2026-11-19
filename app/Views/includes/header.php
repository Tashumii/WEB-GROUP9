<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$activePage = $activePage ?? 'dashboard';
$currentUsername = (string) ($currentUsername ?? 'User');
$currentRoleKey = (string) ($currentRoleKey ?? '');
$businessName = (string) ($businessName ?? '');
$headerActions = $headerActions ?? '';
$session = service('session');
$userId = (int) ($session->get('user_id') ?? 0);
$businessId = (int) ($session->get('business_id') ?? 0);
$mustChangePassword = (int) ($session->get('must_change_password') ?? 0);
$initials = strtoupper(substr($currentUsername, 0, 2));

$navigation = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-table-cells-large', 'route' => 'dashboard'],
    ['key' => 'record', 'label' => 'Record Sale', 'icon' => 'fa-cart-plus', 'route' => 'record-sale'],
    ['key' => 'products', 'label' => 'Products', 'icon' => 'fa-boxes-stacked', 'route' => 'products'],
    ['key' => 'services', 'label' => 'Services', 'icon' => 'fa-print', 'route' => 'services'],
    ['key' => 'sales', 'label' => 'Sales History', 'icon' => 'fa-clock-rotate-left', 'route' => 'sales'],
    ['key' => 'analytics', 'label' => 'Analytics', 'icon' => 'fa-chart-column', 'route' => 'analytics'],
    ['key' => 'settings', 'label' => 'Settings', 'icon' => 'fa-sliders', 'route' => 'settings'],
];
?>
<!doctype html>
<html lang="en" style="color-scheme: light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sales Tracker dashboard — manage products, sales, and reports.">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="<?= esc($csrfToken ?? '') ?>">
    <meta name="api-base-url" content="<?= esc(site_url('api')) ?>">
    <meta name="login-url" content="<?= esc(site_url('login')) ?>">
    <title><?= esc($pageTitle) ?> | Sales Tracker</title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">

    <script>
        (function () {
            try {
                var mobile = window.matchMedia('(max-width: 991.98px)').matches;
                var compact = window.matchMedia('(max-width: 1199.98px)').matches;
                var stored = localStorage.getItem('st-sidebar-collapsed');
                if (!mobile && (compact || stored === '1')) {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (error) {}
        })();
    </script>
    <script src="<?= base_url('js/api.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body class="dashboard-layout">
<a class="skip-link" href="#main-content">Skip to main content</a>
<script src="<?= base_url('js/script.js') ?>"></script>
<script>
    window.APP_USER = <?= json_encode([
        'id' => $userId,
        'username' => $currentUsername,
        'role' => $currentRoleKey,
        'businessId' => $businessId,
        'mustChangePassword' => $mustChangePassword,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-logo">
        <span class="logo-sales">Sales</span>
        <span class="logo-tracker">Tracker</span>
        <span class="logo-monogram" aria-hidden="true">ST</span>
        <button type="button" class="sidebar-collapse-btn" id="sidebarCollapse" aria-label="Collapse sidebar" aria-controls="appSidebar" aria-expanded="true" data-tooltip="Collapse sidebar" title="Collapse sidebar">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Dashboard navigation">
        <?php foreach ($navigation as $item): ?>
            <?php $isActive = $activePage === $item['key']; ?>
            <a href="<?= site_url($item['route']) ?>" class="nav-item-link <?= $isActive ? 'active' : '' ?>" data-tooltip="<?= esc($item['label']) ?>" aria-label="<?= esc($item['label']) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid <?= esc($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= esc($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <div class="avatar avatar-blue"><?= esc($initials) ?></div>
            <div class="sidebar-user-text">
                <span class="sidebar-user-name"><?= esc($currentUsername) ?></span>
                <span class="sidebar-user-role"><?= esc(ucfirst($currentRoleKey !== '' ? $currentRoleKey : 'Staff')) ?></span>
            </div>
        </div>
        <form method="post" action="<?= site_url('logout') ?>" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= esc($csrfToken ?? '') ?>">
            <button type="submit" class="btn btn-link text-white opacity-75 text-decoration-none p-1" title="Log out" aria-label="Log out" data-tooltip="Log out">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
            </button>
        </form>
    </div>
    <div class="sidebar-shortcut-hint" aria-hidden="true"><kbd>Ctrl</kbd>+<kbd>K</kbd> jump &middot; <kbd>/</kbd> search</div>
</aside>

<div class="main">
    <header class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-dark btn-sm d-lg-none sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation" aria-controls="appSidebar" aria-expanded="false">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <div>
                <h1 class="topbar-title"><?= esc($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ''): ?><p class="topbar-subtitle"><?= esc($pageSubtitle) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="topbar-actions d-flex align-items-center gap-2">
            <a href="<?= site_url('record-sale') ?>" class="btn btn-pill active text-decoration-none" id="topbarRecordBtn"><i class="fa-solid fa-circle-plus me-1" aria-hidden="true"></i>Record sale</a>
            <span id="businessName" class="text-muted d-none" style="font-size:.8rem;"><?= esc($businessName) ?></span>
            <select id="businessSwitcher" class="form-select form-select-sm ui-input d-none" style="width:auto;" aria-label="Active business"></select>
            <?= $headerActions ?>
        </div>
    </header>

    <main class="content" id="main-content">
        <div id="toastRegion" role="status" aria-live="polite" aria-atomic="false"></div>
        <div id="jumpPalette" hidden role="dialog" aria-modal="true" aria-label="Jump to page or action">
            <div class="jump-box">
                <input type="text" id="jumpInput" class="jump-input" role="combobox" aria-expanded="true" aria-controls="jumpList" aria-autocomplete="list" placeholder="Jump to page or action..." autocomplete="off">
                <ul class="jump-list" id="jumpList" role="listbox" aria-label="Pages and actions">
                    <li class="jump-item" role="option" data-href="<?= site_url('dashboard') ?>" data-label="Dashboard" data-search="dashboard home overview"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span>Dashboard</span></li>
                    <li class="jump-item" role="option" data-href="<?= site_url('record-sale') ?>" data-label="Record Sale" data-search="record sale new transaction cart"><i class="fa-solid fa-cart-plus" aria-hidden="true"></i><span>Record Sale</span></li>
                    <li class="jump-item" role="option" data-href="<?= site_url('products') ?>" data-label="Products" data-search="products catalog inventory add product"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Products</span></li>
                    <li class="jump-item" role="option" data-href="<?= site_url('services') ?>" data-label="Services" data-search="services printing add service"><i class="fa-solid fa-print" aria-hidden="true"></i><span>Services</span></li>
                    <li class="jump-item" role="option" data-href="<?= site_url('sales') ?>" data-label="Sales History" data-search="sales history transactions receipts"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Sales History</span></li>
                    <li class="jump-item" role="option" data-href="<?= site_url('analytics') ?>" data-label="Analytics" data-search="analytics reports charts revenue"><i class="fa-solid fa-chart-column" aria-hidden="true"></i><span>Analytics</span></li>
                    <li class="jump-item" role="option" data-href="<?= site_url('settings') ?>" data-label="Settings" data-search="settings team password business"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Settings</span></li>
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
            if (!open && returnFocus && toggle && window.matchMedia('(max-width: 991.98px)').matches) toggle.focus();
        }

        if (toggle) toggle.addEventListener('click', function () {
            setSidebar(!document.body.classList.contains('sidebar-open'), false);
        });
        if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false, false); });
        if (sidebar) sidebar.querySelectorAll('.nav-item-link').forEach(function (link) {
            link.addEventListener('click', function () { setSidebar(false, false); });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) setSidebar(false, true);
        });
    })();

    (function () {
        var select = document.getElementById('businessSwitcher');
        var label = document.getElementById('businessName');

        function roleLabel(role) {
            return role ? role.charAt(0).toUpperCase() + role.slice(1) : '';
        }

        Api.get('auth').then(function (data) {
            if (!data.authenticated) return;
            var businesses = data.businesses || [];
            var activeId = Number(data.active_business_id);
            var active = businesses.find(function (business) { return Number(business.id) === activeId; });

            if (businesses.length < 2 || !select) {
                if (label && active) {
                    label.textContent = active.name + (active.role ? ' (' + roleLabel(active.role) + ')' : '');
                    label.classList.remove('d-none');
                }
                return;
            }
            if (label) label.classList.add('d-none');

            businesses.forEach(function (business) {
                var option = document.createElement('option');
                option.value = String(business.id);
                option.textContent = business.name + ' — ' + roleLabel(business.role);
                select.appendChild(option);
            });
            select.value = String(activeId);
            select.classList.remove('d-none');
            select.addEventListener('change', function () {
                Api.post('auth', { action: 'switch_business', business_id: Number(select.value) })
                    .then(function () { window.location.reload(); })
                    .catch(function (error) {
                        if (typeof showToast === 'function') showToast(error.message, true);
                        else window.alert(error.message);
                        select.value = String(activeId);
                    });
            });
        }).catch(function () {});
    })();
</script>
