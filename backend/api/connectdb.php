<?php
// Compatibility entry point. All shared setup lives in bootstrap.php.

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';
