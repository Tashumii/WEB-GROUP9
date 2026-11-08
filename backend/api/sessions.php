<?php
// Server-side session management: list my active sessions and revoke them.

require_once __DIR__ . '/connectdb.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $userId = getCurrentUserId();
    if ($userId === null) {
        jsonError('Authentication required.', 401);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, ip_address, user_agent, created_at, last_seen_at, expires_at, session_hash
            FROM user_sessions
            WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW()
            ORDER BY last_seen_at DESC, created_at DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $currentHash = app_session_row_hash();
        $sessions = array_map(static function (array $row) use ($currentHash): array {
            return [
                'id' => (int)$row['id'],
                'ip_address' => (string)$row['ip_address'],
                'user_agent' => (string)$row['user_agent'],
                'created_at' => (string)$row['created_at'],
                'last_seen_at' => (string)$row['last_seen_at'],
                'expires_at' => (string)$row['expires_at'],
                'is_current' => hash_equals((string)$row['session_hash'], $currentHash),
            ];
        }, $rows);

        jsonResponse([
            'success' => true,
            'sessions' => $sessions,
        ]);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($method !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$userId = getCurrentUserId();
if ($userId === null) {
    jsonError('Authentication required.', 401);
}

$body = getJsonBody();
$action = (string)($body['action'] ?? '');

if ($action === 'revoke') {
    $sessionId = input_int($body, 'session_id');
    if ($sessionId <= 0) {
        jsonError('A valid session_id is required.', 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, session_hash FROM user_sessions WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$sessionId, $userId]);
        $row = $stmt->fetch();

        if ($row === false) {
            jsonError('Session not found.', 404);
        }

        $isCurrent = hash_equals((string)$row['session_hash'], app_session_row_hash());
        $update = $pdo->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE id = ?");
        $update->execute([(int)$row['id']]);

        if ($isCurrent) {
            destroy_app_session();
            jsonResponse(['success' => true, 'message' => 'Current session signed out.', 'logged_out' => true]);
        }

        jsonResponse(['success' => true, 'message' => 'Session signed out.', 'logged_out' => false]);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($action === 'revoke_all') {
    try {
        $count = revoke_user_sessions($pdo, $userId, app_session_row_hash());
        jsonResponse(['success' => true, 'message' => 'All other sessions signed out.', 'revoked' => $count]);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

jsonError('Unknown action.', 400);
