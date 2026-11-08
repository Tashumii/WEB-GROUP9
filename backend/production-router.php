<?php
// Router for PHP built-in server in production (php -S ignores .htaccess).
// Usage: php -S 127.0.0.1:8080 -t . backend/production-router.php
// Denies sensitive files that Apache blocks via .htaccess.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = urldecode((string)$path);
$denied = ['.sql', '.env', 'PLAN.md'];
if (str_contains($path, '/.git/') || str_contains($path, '/.env') || str_contains($path, '/.agents/') || str_contains($path, '/.claude/') || str_contains($path, '/agent/') || str_contains($path, '/.taste-skill/') || str_contains($path, '/.playwright/')) {
    http_response_code(404);
    return true;
}
foreach (['database.sql', '.env', 'PLAN.md'] as $name) {
    if (str_ends_with($path, $name)) {
        http_response_code(404);
        return true;
    }
}
return false;
