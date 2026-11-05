<?php
// start session
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// cors headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Business-ID');

// preflight request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// default config
$host = 'localhost';
$port = '3306';
$user = 'root';
$password = '';
$database = 'SalesDB';

// env parser
$envPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
    dirname(__DIR__, 2) . '/backend/.env'
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                if ($key === 'DB_HOST') $host = $val;
                if ($key === 'DB_PORT') $port = $val;
                if ($key === 'DB_NAME') $database = $val;
                if ($key === 'DB_USER') $user = $val;
                if ($key === 'DB_PASS') $password = $val;
            }
        }
        break;
    }
}

// connect pdo
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    // db fallback
    try {
        $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $rootPdo = new PDO($rootDsn, $user, $password, $options);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO($dsn, $user, $password, $options);
    } catch (PDOException $ex) {
        if (basename($_SERVER['PHP_SELF']) !== 'connectdb.php') {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $ex->getMessage()
            ]);
            exit;
        }
        die("Connection failed: " . $ex->getMessage());
    }
}

// compatibility variables
$conn = $pdo;
$connn = $pdo;

// send json
function jsonResponse($data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// send error
function jsonError(string $message, int $status = 400, $details = null) {
    $response = ['success' => false, 'error' => $message];
    if ($details !== null) {
        $response['details'] = $details;
    }
    jsonResponse($response, $status);
}

// parse body
function getJsonBody() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST;
}

// active business
function getActiveBusinessId(PDO $db): int {
    // header id
    if (!empty($_SERVER['HTTP_X_BUSINESS_ID'])) {
        return (int)$_SERVER['HTTP_X_BUSINESS_ID'];
    }
    // query id
    if (!empty($_GET['business_id'])) {
        return (int)$_GET['business_id'];
    }
    // session id
    if (!empty($_SESSION['business_id'])) {
        return (int)$_SESSION['business_id'];
    }
    // default lookup
    try {
        $stmt = $db->query("SELECT id FROM businesses ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        if ($row && !empty($row['id'])) {
            return (int)$row['id'];
        }
    } catch (Exception $e) {
        // ignore error
    }
    return 1;
}

// current user
function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
