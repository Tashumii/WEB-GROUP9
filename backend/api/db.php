<?php
// Database connection only. Include this from bootstrap.php.

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

$dbHost = 'localhost';
$dbPort = '3306';
$dbUser = 'root';
$dbPass = '';
$dbName = 'SalesDB';
$dbAppEnv = 'development';

$dbEnvPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
];

foreach ($dbEnvPaths as $dbEnvPath) {
    if (!file_exists($dbEnvPath)) {
        continue;
    }

    $lines = file($dbEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");

        match ($key) {
            'DB_HOST' => $dbHost = $val,
            'DB_PORT' => $dbPort = $val,
            'DB_NAME' => $dbName = $val,
            'DB_USER' => $dbUser = $val,
            'DB_PASS' => $dbPass = $val,
            'APP_ENV' => $dbAppEnv = $val,
            default => null,
        };
    }
    break;
}

if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
    throw new RuntimeException('Database connection failed');
}
if (!preg_match('/^[A-Za-z0-9._-]+$/', $dbHost)) {
    throw new RuntimeException('Database connection failed');
}

$dbDsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$dbOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass, $dbOptions);
} catch (PDOException $e) {
    // Convenience only for local development; production should use migrations.
    if ($dbAppEnv === 'production') {
        throw new RuntimeException('Database connection failed', 0, $e);
    }

    try {
        $dbRootDsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $dbRootPdo = new PDO($dbRootDsn, $dbUser, $dbPass, $dbOptions);
        $dbRootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO($dbDsn, $dbUser, $dbPass, $dbOptions);
    } catch (PDOException $ex) {
        throw new RuntimeException('Database connection failed', 0, $ex);
    }
}

$pdo->exec("SET time_zone = '+08:00'");
