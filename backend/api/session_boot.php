<?php
// Shared session and CSRF bootstrap for pages and API endpoints.
// This file must not output anything.

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

const APP_SESSION_NAME = 'SALESTRACKER_SID';
const APP_IDLE_SECONDS = 1800;
const APP_ABSOLUTE_SECONDS = 28800;
const APP_CSRF_KEY = 'csrf_token';

function app_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    return (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function start_app_session(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)APP_ABSOLUTE_SECONDS);

    session_name(APP_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    @session_start();
}

function app_session_expired(bool $touch = false): bool {
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $now = time();
    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    }
    if (empty($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
    }

    $idle = $now - (int)$_SESSION['last_activity'];
    $age = $now - (int)$_SESSION['created_at'];

    if ($idle > APP_IDLE_SECONDS || $age > APP_ABSOLUTE_SECONDS) {
        return true;
    }

    if ($touch) {
        $_SESSION['last_activity'] = $now;
    }

    return false;
}

function touch_app_session(): void {
    $now = time();
    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    }
    $_SESSION['last_activity'] = $now;
}

function destroy_app_session(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function csrf_token(): string {
    if (empty($_SESSION[APP_CSRF_KEY])) {
        $_SESSION[APP_CSRF_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[APP_CSRF_KEY];
}

function rotate_csrf_token(): string {
    $_SESSION[APP_CSRF_KEY] = bin2hex(random_bytes(32));
    return $_SESSION[APP_CSRF_KEY];
}

function csrf_token_is_valid($token): bool {
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION[APP_CSRF_KEY])
        && hash_equals($_SESSION[APP_CSRF_KEY], $token);
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function app_client_user_agent(): string {
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown browser'), 0, 500);
}

function app_send_security_headers(): void {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Cross-Origin-Resource-Policy: same-origin');

    if (app_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
