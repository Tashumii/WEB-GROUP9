<?php
// Shared API bootstrap: session, security, JSON helpers, authorization, and DB.

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/session_boot.php';
require_once __DIR__ . '/routes.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $GLOBALS['app_is_head_request'] = true;
}

start_app_session();

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

app_send_security_headers();

function http_status_text(int $status): string {
    return [
        200 => 'OK',
        201 => 'Created',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        413 => 'Payload Too Large',
        415 => 'Unsupported Media Type',
        419 => 'Authentication Timeout',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
    ][$status] ?? '';
}

function jsonResponse($data, int $status = 200): void {
    $text = http_status_text($status);
    $statusLine = $text === '' ? "HTTP/1.1 {$status}" : "HTTP/1.1 {$status} {$text}";
    header($statusLine, true, $status);
    header('Content-Type: application/json; charset=utf-8');
    if (empty($GLOBALS['app_is_head_request'])) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

function jsonDbError(Throwable $e): void {
    error_log('[SalesTracker] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    jsonError('A database error occurred. Please try again later.', 500);
}

function getJsonBody(): array {
    static $body = null;
    if (is_array($body)) {
        return $body;
    }

    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 1048576) {
        jsonError('Request body is too large.', 413);
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        $body = $_POST;
        return $body;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        jsonError('Invalid JSON body.', 400);
    }

    $body = $decoded;
    return $body;
}

function input_string(array $body, string $key, int $maxLength = 255): string {
    $value = $body[$key] ?? '';
    if (!is_scalar($value)) {
        return '';
    }
    return substr(trim((string)$value), 0, $maxLength);
}

function input_int(array $body, string $key): int {
    $value = filter_var($body[$key] ?? null, FILTER_VALIDATE_INT);
    return $value === false ? 0 : (int)$value;
}

function input_float(array $body, string $key): float {
    $value = filter_var($body[$key] ?? null, FILTER_VALIDATE_FLOAT);
    return $value === false ? 0.0 : (float)$value;
}

function validate_username(string $username): ?string {
    if (strlen($username) < 3 || strlen($username) > 50) {
        return 'Username must be between 3 and 50 characters.';
    }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
        return 'Username may only contain letters, numbers, dots, underscores, or dashes.';
    }
    return null;
}

function validate_password(string $password, string $username = ''): ?string {
    $length = strlen($password);
    if ($length < 8) {
        return 'Password must be at least 8 characters.';
    }
    if ($length > 72) {
        return 'Password must be at most 72 characters.';
    }
    if ($username !== '' && strcasecmp($password, $username) === 0) {
        return 'Password must be different from your username.';
    }
    $common = ['password', '12345678', '123456789', 'qwerty', 'qwerty123', 'letmein', 'admin123', 'iloveyou', 'changeme'];
    if (in_array(strtolower($password), $common, true)) {
        return 'That password is too common. Choose something less guessable.';
    }
    return null;
}

function like_escape(string $value): string {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function isValidDateString(string $date): bool {
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

// Enforce CSRF for every state-changing API request.
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$requestScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isApiRequest = strpos($requestScript, '/backend/api/') !== false;

if ($isApiRequest && in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
    if (!csrf_token_is_valid($csrfToken)) {
        jsonError('Invalid or expired CSRF token. Please refresh the page.', 419);
    }

    if ($requestMethod !== 'DELETE' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
        jsonError('Content-Type must be application/json.', 415);
    }
}

if ($isApiRequest && !empty($_SESSION['must_change_password'])) {
    $allowedScript = basename($requestScript);
    $allowed = $allowedScript === 'auth.php';

    if ($allowedScript === 'users.php') {
        $temporaryBody = getJsonBody();
        $allowed = (($temporaryBody['action'] ?? '') === 'change_password');
    }

    if (!$allowed) {
        jsonError('You must change your password before continuing.', 403);
    }
}

try {
    require __DIR__ . '/db.php';
} catch (Throwable $e) {
    jsonDbError($e);
}

function app_session_row_hash(): string {
    return hash('sha256', (string)session_id());
}

function register_persistent_session(PDO $pdo, int $userId): void {
    $hash = app_session_row_hash();
    $expires = date('Y-m-d H:i:s', time() + APP_ABSOLUTE_SECONDS);

    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (session_hash, user_id, ip_address, user_agent, expires_at)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            ip_address = VALUES(ip_address),
            user_agent = VALUES(user_agent),
            expires_at = VALUES(expires_at),
            revoked_at = NULL
    ");
    $stmt->execute([$hash, $userId, client_ip(), app_client_user_agent(), $expires]);

    $count = $pdo->prepare("SELECT COUNT(*) FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW()");
    $count->execute([$userId]);
    $activeCount = (int)$count->fetchColumn();

    if ($activeCount > 10) {
        $cap = $pdo->prepare("
            UPDATE user_sessions
            SET revoked_at = NOW()
            WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW()
              AND session_hash != ?
            ORDER BY created_at ASC
            LIMIT ?
        ");
        $cap->bindValue(1, $userId, PDO::PARAM_INT);
        $cap->bindValue(2, $hash, PDO::PARAM_STR);
        $cap->bindValue(3, $activeCount - 10, PDO::PARAM_INT);
        $cap->execute();
    }
}

function sync_persistent_session(PDO $pdo, int $userId): void {
    register_persistent_session($pdo, $userId);
}

function validate_persistent_session(PDO $pdo, int $userId): bool {
    $hash = app_session_row_hash();

    $stmt = $pdo->prepare("
        SELECT id, revoked_at, expires_at FROM user_sessions
        WHERE session_hash = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$hash, $userId]);
    $row = $stmt->fetch();

    if ($row === false) {
        register_persistent_session($pdo, $userId);
        return true;
    }

    if (!empty($row['revoked_at'])) {
        return false;
    }

    if (strtotime((string)$row['expires_at']) <= time()) {
        return false;
    }

    $lastSeen = (int)($_SESSION['session_row_seen'] ?? 0);
    if (time() - $lastSeen > 300) {
        $touch = $pdo->prepare("UPDATE user_sessions SET last_seen_at = NOW() WHERE id = ?");
        $touch->execute([(int)$row['id']]);
        $_SESSION['session_row_seen'] = time();
    }

    return true;
}

function revoke_persistent_session(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE session_hash = ? AND user_id = ? AND revoked_at IS NULL");
    $stmt->execute([app_session_row_hash(), $userId]);
}

function revoke_user_sessions(PDO $pdo, int $userId, ?string $exceptHash = null): int {
    $sql = "UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL";
    $params = [$userId];
    if ($exceptHash !== null && $exceptHash !== '') {
        $sql .= " AND session_hash != ?";
        $params[] = $exceptHash;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->rowCount();
}

function revoke_session_hash(PDO $pdo, string $hash): void {
    if ($hash === '') {
        return;
    }
    $stmt = $pdo->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE session_hash = ?");
    $stmt->execute([$hash]);
}

function logout_user(PDO $pdo): void {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        revoke_persistent_session($pdo, $userId);
    }
    destroy_app_session();
}

function enforce_api_rate_limit(PDO $pdo): void {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $key = 'ip:' . client_ip();
    $now = time();

    $stmt = $pdo->prepare("SELECT window_start, hits FROM rate_limits WHERE limit_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    if ($row === false) {
        $insert = $pdo->prepare("INSERT INTO rate_limits (limit_key, window_start, hits) VALUES (?, NOW(), 1) ON DUPLICATE KEY UPDATE window_start = NOW(), hits = 1");
        $insert->execute([$key]);
        return;
    }

    $windowStart = strtotime((string)$row['window_start']);
    if ($windowStart === false || $now - $windowStart > 60) {
        $reset = $pdo->prepare("UPDATE rate_limits SET window_start = NOW(), hits = 1 WHERE limit_key = ?");
        $reset->execute([$key]);
        return;
    }

    if ((int)$row['hits'] >= 300) {
        jsonError('Too many requests. Please slow down and try again in a moment.', 429);
    }

    $bump = $pdo->prepare("UPDATE rate_limits SET hits = hits + 1 WHERE limit_key = ?");
    $bump->execute([$key]);
}

if ($isApiRequest) {
    enforce_api_rate_limit($pdo);
    // Per-action gate inside auth.php must stay public for login/register.
    // Parse body action early so route_api_require can distinguish; safe to read here
    // because getJsonBody caches and auth.php re-uses the same body.
    $routeAction = '';
    try {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'auth.php') {
            $routeAction = (string)(getJsonBody()['action'] ?? '');
        }
    } catch (Throwable $e) {
        $routeAction = '';
    }
    route_api_require($pdo, $routeAction);
}

function getCurrentUserId(): ?int {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $validatedUserId = null;
    $userId = (int)$_SESSION['user_id'];

    if (app_session_expired()) {
        destroy_app_session();
        $validatedUserId = null;
        return null;
    }

    if ($validatedUserId === $userId) {
        touch_app_session();
        return $userId;
    }

    if (empty($_SESSION['auth_fingerprint'])) {
        destroy_app_session();
        return null;
    }

    global $pdo;
    if (!($pdo instanceof PDO)) {
        destroy_app_session();
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
    } catch (Throwable $e) {
        jsonDbError($e);
    }

    if ($hash === false || !hash_equals($_SESSION['auth_fingerprint'], hash('sha256', (string)$hash))) {
        destroy_app_session();
        $validatedUserId = null;
        return null;
    }

    if (!validate_persistent_session($pdo, $userId)) {
        destroy_app_session();
        $validatedUserId = null;
        return null;
    }

    $validatedUserId = $userId;
    touch_app_session();
    return $userId;
}

function membership_role(PDO $pdo, int $userId, int $businessId): ?string {
    $stmt = $pdo->prepare("
        SELECT m.role
        FROM memberships m
        JOIN users u ON u.id = m.user_id
        WHERE m.user_id = ? AND m.business_id = ? AND m.is_active = 1 AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$userId, $businessId]);
    $role = $stmt->fetchColumn();
    return $role === false ? null : (string)$role;
}

function getUserMemberships(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT m.business_id, m.role, m.is_active, b.name AS business_name
        FROM memberships m
        JOIN businesses b ON b.id = m.business_id
        JOIN users u ON u.id = m.user_id
        WHERE m.user_id = ? AND m.is_active = 1 AND u.is_active = 1
        ORDER BY FIELD(m.role, 'owner', 'admin', 'staff'), b.name, b.id
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAllowedBusinessIds(PDO $pdo, ?int $userId = null): array {
    $userId = $userId ?? getCurrentUserId();
    if ($userId === null) {
        return [];
    }

    return array_map(
        static fn(array $membership): int => (int)$membership['business_id'],
        getUserMemberships($pdo, $userId)
    );
}

function getActiveBusinessId(PDO $pdo): int {
    $userId = getCurrentUserId();
    if ($userId === null) {
        jsonError('Authentication required.', 401);
    }

    $businessId = (int)($_SESSION['business_id'] ?? 0);
    if ($businessId <= 0) {
        jsonError('No active business selected.', 403);
    }

    $role = membership_role($pdo, $userId, $businessId);
    if ($role === null) {
        $memberships = getUserMemberships($pdo, $userId);
        if (empty($memberships)) {
            unset($_SESSION['business_id'], $_SESSION['role']);
            jsonError('No active business is assigned to this account.', 403);
        }

        $businessId = (int)$memberships[0]['business_id'];
        $role = (string)$memberships[0]['role'];
        $_SESSION['business_id'] = $businessId;
    }

    $_SESSION['role'] = $role;
    return $businessId;
}

function requireRole(PDO $pdo, int $businessId, array $roles): string {
    $userId = getCurrentUserId();
    if ($userId === null) {
        jsonError('Authentication required.', 401);
    }

    $role = membership_role($pdo, $userId, $businessId);
    if ($role === null || !in_array($role, $roles, true)) {
        jsonError('You do not have permission to perform this action.', 403);
    }

    return $role;
}

function requireAdmin(PDO $pdo): void {
    $businessId = getActiveBusinessId($pdo);
    requireRole($pdo, $businessId, ['owner', 'admin']);
}

function establish_user_session(PDO $pdo, array $user, array $memberships): void {
    $previousHash = session_id() !== '' ? app_session_row_hash() : '';
    $_SESSION = [];
    session_regenerate_id(true);
    revoke_session_hash($pdo, $previousHash);

    if (empty($user['password_hash'])) {
        throw new RuntimeException('Authentication failed.');
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['must_change_password'] = (int)($user['must_change_password'] ?? 0);
    $_SESSION['auth_fingerprint'] = hash('sha256', (string)$user['password_hash']);

    if (!empty($memberships)) {
        $active = $memberships[0];
        $_SESSION['business_id'] = (int)$active['business_id'];
        $_SESSION['role'] = (string)$active['role'];
    }

    touch_app_session();
    rotate_csrf_token();
    register_persistent_session($pdo, (int)$user['id']);
}

function switch_user_business(PDO $pdo, int $businessId): array {
    $userId = getCurrentUserId();
    if ($userId === null) {
        jsonError('Authentication required.', 401);
    }

    $role = membership_role($pdo, $userId, $businessId);
    if ($role === null) {
        jsonError('You do not have access to that business.', 403);
    }

    $previousHash = app_session_row_hash();
    $_SESSION['business_id'] = $businessId;
    $_SESSION['role'] = $role;
    session_regenerate_id(true);
    touch_app_session();
    rotate_csrf_token();
    sync_persistent_session($pdo, $userId);
    revoke_session_hash($pdo, $previousHash);

    foreach (getUserMemberships($pdo, $userId) as $membership) {
        if ((int)$membership['business_id'] === $businessId) {
            return $membership;
        }
    }

    return ['business_id' => $businessId, 'role' => $role, 'is_active' => 1, 'business_name' => ''];
}

function authenticate_credentials(PDO $pdo, string $username, string $password): array {
    $stmt = $pdo->prepare("
        SELECT id, username, password_hash, is_active, must_change_password
        FROM users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !(int)$user['is_active'] || !password_verify($password, (string)$user['password_hash'])) {
        throw new RuntimeException('Invalid username or password.');
    }

    if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$newHash, (int)$user['id']]);
        $user['password_hash'] = $newHash;
    }

    $memberships = getUserMemberships($pdo, (int)$user['id']);

    update_last_login($pdo, (int)$user['id']);
    return ['user' => $user, 'memberships' => $memberships];
}

function update_last_login(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

function recent_failure_count(PDO $pdo, string $action, string $username, int $seconds, ?string $ip = null): int {
    $seconds = (int)$seconds;
    $sql = "SELECT COUNT(*) FROM auth_attempts
            WHERE action = ? AND success = 0
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $params = [$action, $seconds];

    if ($ip !== null) {
        $sql .= " AND ip_address = ?";
        $params[] = $ip;
    } else {
        $sql .= " AND username = ? AND ip_address = ?";
        $params[] = $username;
        $params[] = client_ip();
    }

    $stmt = $pdo->prepare($sql);
    $index = 1;
    foreach ($params as $value) {
        $stmt->bindValue($index++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function is_login_rate_limited(PDO $pdo, string $username): bool {
    $usernameFailures = recent_failure_count($pdo, 'login', $username, 900);
    $ipFailures = recent_failure_count($pdo, 'login', '', 900, client_ip());
    return $usernameFailures >= 5 || $ipFailures >= 20;
}

function is_register_rate_limited(PDO $pdo): bool {
    return recent_failure_count($pdo, 'register', '', 3600, client_ip()) >= 5;
}

function record_auth_attempt(PDO $pdo, string $action, string $username, bool $success): void {
    $stmt = $pdo->prepare("
        INSERT INTO auth_attempts (action, username, ip_address, success)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$action, substr($username, 0, 100), client_ip(), $success ? 1 : 0]);

    if (random_int(1, 100) === 1) {
        $pdo->exec("DELETE FROM auth_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY)");
    }
}
