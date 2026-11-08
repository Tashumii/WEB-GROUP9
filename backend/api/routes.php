<?php
// Central route protection for Sales Tracker.
// No output. Include from pages/APIs before any output.
// Access levels:
//  public          – index, login, register, logout (handled separately)
//  auth            – any signed-in user
//  business        – signed-in + must_change != 1 + has active business
//  owner           – business + role = owner
// This file centralises the matrix so frontend and APIs stay in sync.

if (!function_exists('route_access_for')) {
    function route_access_for(string $scriptName): string {
        $scriptName = basename($scriptName);
        $map = [
            // public landing
            'index.php' => 'public',
            'login.php' => 'public',
            'register.php' => 'public',
            'logout.php' => 'public',
            // auth only (no business required yet / must-change flow)
            'CreateBusiness.php' => 'auth',
            // must_change_password users are forced to Settings via header.php,
            // but keep them here as business so Settings is reachable
            'Settings.php' => 'business',
            // owner-only pages (team / business management)
            // NOTE: Settings is shared, but JS gates owner blocks; keep as business to avoid lockout
            // business pages (owner/admin/staff)
            'Dashboard.php' => 'business',
            'RecordSales.php' => 'business',
            'Products.php' => 'business',
            'Services.php' => 'business',
            'Sales.php' => 'business',
            'Analytics.php' => 'business',
        ];
        return $map[$scriptName] ?? 'business';
    }
}

if (!function_exists('route_require_page')) {
    function route_require_page(): void {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $level = route_access_for($script);
        if ($level === 'public') return;
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        if ($level === 'auth') {
            if (!empty($_SESSION['must_change_password']) && basename($script) !== 'Settings.php') {
                header('Location: Settings.php');
                exit;
            }
            return;
        }
        // business level
        if (!empty($_SESSION['must_change_password']) && basename($script) !== 'Settings.php') {
            header('Location: Settings.php');
            exit;
        }
        if (empty($_SESSION['business_id']) && basename($script) !== 'Settings.php') {
            header('Location: CreateBusiness.php');
            exit;
        }
    }
}

if (!function_exists('route_api_level')) {
    function route_api_level(string $scriptName): string {
        return match (basename($scriptName)) {
            // auth resolves per-action (see route_api_requireArgs) because
            // login/register must stay public while logout/switch stay auth-only
            'auth.php' => 'auth',
            'sessions.php' => 'auth',
            'businesses.php' => 'auth',
            'users.php' => 'owner',
            'products.php', 'services.php', 'sales.php', 'recordsales.php', 'analytics.php' => 'business',
            default => 'business',
        };
    }
}

if (!function_exists('route_api_require')) {
    function route_api_require(PDO $pdo, string $action = ''): void {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $level = route_api_level($script);
        // Public sub-actions: anyone (even logged-out) may call these.
        // GET auth.php is the session-status probe (returns authenticated:false).
        if ($level === 'auth' && basename($script) === 'auth.php') {
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            if ($method === 'GET') {
                return;
            }
            if (in_array($action, ['login', 'register'], true)) {
                return;
            }
            if (getCurrentUserId() === null) jsonError('Authentication required.', 401);
            return;
        }
        if ($level === 'auth') {
            if (getCurrentUserId() === null) jsonError('Authentication required.', 401);
            return;
        }
        if ($level === 'owner') {
            $businessId = getActiveBusinessId($pdo);
            requireRole($pdo, $businessId, ['owner']);
            return;
        }
        // business: enforces auth + active business + role lookup
        getActiveBusinessId($pdo);
    }
}

if (!function_exists('route_can')) {
    function route_can(string $role, string $action): bool {
        $caps = [
            'owner' => ['view', 'create', 'edit', 'delete', 'record', 'manage_team', 'switch_business'],
            'admin' => ['view', 'create', 'edit', 'delete', 'record', 'switch_business'],
            'staff' => ['view', 'create', 'record', 'switch_business'],
        ];
        return in_array($action, ($caps[$role] ?? []), true);
    }
}
