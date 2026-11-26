<?php

namespace App\Libraries;

use App\Exceptions\ApiException;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Session\Session;
use Config\Database;

/** Authentication, membership, persistent-session and request-limit operations. */
class SalesTrackerAuth
{
    private BaseConnection $db;
    private Session $session;
    private IncomingRequest $request;
    private ?int $validatedUserId = null;

    public function __construct(?BaseConnection $db = null, ?Session $session = null, ?IncomingRequest $request = null)
    {
        $this->db = $db ?? Database::connect();
        $this->session = $session ?? service('session');
        $this->request = $request ?? service('request');
        date_default_timezone_set('Asia/Manila');
    }

    public function session(): Session
    {
        return $this->session;
    }

    public function csrfToken(): string
    {
        $token = $this->session->get('csrf_token');
        if (! is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
        }
        return $token;
    }

    public function rotateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        return $token;
    }

    public function csrfTokenIsValid(mixed $token): bool
    {
        $current = $this->session->get('csrf_token');
        return is_string($token) && $token !== '' && is_string($current) && $current !== '' && hash_equals($current, $token);
    }

    public function currentUserId(): ?int
    {
        $userId = (int) ($this->session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $now = time();
        $createdAt = (int) ($this->session->get('created_at') ?? $now);
        $lastActivity = (int) ($this->session->get('last_activity') ?? $now);
        if ($now - $lastActivity > 1800 || $now - $createdAt > 28800) {
            $this->destroySession();
            return null;
        }

        if ($this->validatedUserId === $userId) {
            $this->touchSession();
            return $userId;
        }

        $fingerprint = $this->session->get('auth_fingerprint');
        if (! is_string($fingerprint) || $fingerprint === '') {
            $this->destroySession();
            return null;
        }

        $user = $this->db->query('SELECT password_hash FROM users WHERE id = ? AND is_active = 1 LIMIT 1', [$userId])->getRowArray();
        if (! $user || ! hash_equals($fingerprint, hash('sha256', (string) $user['password_hash']))) {
            $this->destroySession();
            return null;
        }

        if (! $this->validatePersistentSession($userId)) {
            $this->destroySession();
            return null;
        }

        $this->validatedUserId = $userId;
        $this->touchSession();
        return $userId;
    }

    public function requireUser(): int
    {
        $id = $this->currentUserId();
        if ($id === null) {
            throw new ApiException('Authentication required.', 401);
        }
        return $id;
    }

    /** @return list<array{business_id:int,role:string,is_active:int,business_name:string}> */
    public function memberships(int $userId): array
    {
        $rows = $this->db->query(
            "SELECT m.business_id, m.role, m.is_active, b.name AS business_name
             FROM memberships m
             JOIN businesses b ON b.id = m.business_id
             JOIN users u ON u.id = m.user_id
             WHERE m.user_id = ? AND m.is_active = 1 AND u.is_active = 1
             ORDER BY FIELD(m.role, 'owner', 'admin', 'staff'), b.name, b.id",
            [$userId]
        )->getResultArray();

        return array_map(static fn (array $row): array => [
            'business_id' => (int) $row['business_id'],
            'role' => (string) $row['role'],
            'is_active' => (int) $row['is_active'],
            'business_name' => (string) $row['business_name'],
        ], $rows);
    }

    public function membershipRole(int $userId, int $businessId): ?string
    {
        $row = $this->db->query(
            "SELECT m.role FROM memberships m JOIN users u ON u.id = m.user_id
             WHERE m.user_id = ? AND m.business_id = ? AND m.is_active = 1 AND u.is_active = 1 LIMIT 1",
            [$userId, $businessId]
        )->getRowArray();
        return $row ? (string) $row['role'] : null;
    }

    public function activeBusinessId(): int
    {
        $userId = $this->requireUser();
        $businessId = (int) ($this->session->get('business_id') ?? 0);
        $role = $businessId > 0 ? $this->membershipRole($userId, $businessId) : null;

        if ($role === null) {
            $memberships = $this->memberships($userId);
            if ($memberships === []) {
                $this->session->remove(['business_id', 'role']);
                throw new ApiException('No active business is assigned to this account.', 403);
            }
            $businessId = $memberships[0]['business_id'];
            $role = $memberships[0]['role'];
            $this->session->set('business_id', $businessId);
        }

        $this->session->set('role', $role);
        return $businessId;
    }

    public function requireRole(int $businessId, array $roles): string
    {
        $userId = $this->requireUser();
        $role = $this->membershipRole($userId, $businessId);
        if ($role === null || ! in_array($role, $roles, true)) {
            throw new ApiException('You do not have permission to perform this action.', 403);
        }
        return $role;
    }

    public function establishUserSession(array $user, array $memberships): void
    {
        $oldHash = $this->sessionHash();
        $this->session->remove(array_keys($this->session->get()));
        $this->session->regenerate(true);
        $this->revokeSessionHash($oldHash);

        $passwordHash = (string) ($user['password_hash'] ?? '');
        if ($passwordHash === '') {
            throw new ApiException('Authentication failed.', 401);
        }

        $this->session->set([
            'user_id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
            'auth_fingerprint' => hash('sha256', $passwordHash),
            'created_at' => time(),
            'last_activity' => time(),
        ]);

        if ($memberships !== []) {
            $this->session->set([
                'business_id' => (int) $memberships[0]['business_id'],
                'role' => (string) $memberships[0]['role'],
            ]);
        }

        $this->rotateCsrfToken();
        $this->registerPersistentSession((int) $user['id']);
        $this->validatedUserId = (int) $user['id'];
    }

    public function switchBusiness(int $businessId): array
    {
        $userId = $this->requireUser();
        $role = $this->membershipRole($userId, $businessId);
        if ($role === null) {
            throw new ApiException('You do not have access to that business.', 403);
        }

        $previousHash = $this->sessionHash();
        $this->session->set(['business_id' => $businessId, 'role' => $role]);
        $this->session->regenerate(true);
        $this->touchSession();
        $this->rotateCsrfToken();
        $this->registerPersistentSession($userId);
        $this->revokeSessionHash($previousHash);

        foreach ($this->memberships($userId) as $membership) {
            if ($membership['business_id'] === $businessId) {
                return $membership;
            }
        }
        return ['business_id' => $businessId, 'role' => $role, 'business_name' => ''];
    }

    public function logout(): void
    {
        $userId = (int) ($this->session->get('user_id') ?? 0);
        if ($userId > 0) {
            $this->db->query(
                'UPDATE user_sessions SET revoked_at = NOW() WHERE session_hash = ? AND user_id = ? AND revoked_at IS NULL',
                [$this->sessionHash(), $userId]
            );
        }
        $this->destroySession();
    }

    public function revokeUserSessions(int $userId, ?string $exceptHash = null): int
    {
        $sql = 'UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL';
        $params = [$userId];
        if ($exceptHash !== null && $exceptHash !== '') {
            $sql .= ' AND session_hash != ?';
            $params[] = $exceptHash;
        }
        $this->db->query($sql, $params);
        return $this->db->affectedRows();
    }

    public function revokeSessionHash(string $hash): void
    {
        if ($hash !== '') {
            $this->db->query('UPDATE user_sessions SET revoked_at = NOW() WHERE session_hash = ?', [$hash]);
        }
    }

    public function sessionHash(): string
    {
        return hash('sha256', (string) $this->session->session_id);
    }

    public function clientIp(): string
    {
        return $this->request->getIPAddress() ?: '0.0.0.0';
    }

    public function recordAuthAttempt(string $action, string $username, bool $success): void
    {
        $this->db->table('auth_attempts')->insert([
            'action' => $action,
            'username' => substr($username, 0, 100),
            'ip_address' => $this->clientIp(),
            'success' => $success ? 1 : 0,
        ]);

        if (random_int(1, 100) === 1) {
            $this->db->query('DELETE FROM auth_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY)');
        }
    }

    public function isLoginRateLimited(string $username): bool
    {
        return $this->recentFailureCount('login', $username, 900) >= 5
            || $this->recentFailureCount('login', '', 900, $this->clientIp()) >= 20;
    }

    public function isRegisterRateLimited(): bool
    {
        // Count every attempt, not just failures: otherwise a script could keep
        // creating accounts forever as long as each registration succeeds.
        return $this->recentAttemptCount('register', $this->clientIp(), 3600) >= 5;
    }

    public function authenticate(string $username, string $password): array
    {
        $user = $this->db->query(
            'SELECT id, username, email, password_hash, is_active, must_change_password, two_factor_enabled, two_factor_secret, two_factor_recovery_codes, two_factor_last_used FROM users WHERE username = ? LIMIT 1',
            [$username]
        )->getRowArray();
        if (! $user || (int) $user['is_active'] !== 1 || ! password_verify($password, (string) $user['password_hash'])) {
            throw new ApiException('Invalid username or password.', 401);
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $this->db->table('users')->where('id', (int) $user['id'])->update([
                'password_hash' => $user['password_hash'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $memberships = $this->memberships((int) $user['id']);
        $this->db->table('users')->where('id', (int) $user['id'])->update([
            'last_login_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return ['user' => $user, 'memberships' => $memberships];
    }

    public function enforceApiRateLimit(): void
    {
        $key = 'ip:' . $this->clientIp();
        $row = $this->db->query('SELECT window_start, hits FROM rate_limits WHERE limit_key = ? LIMIT 1', [$key])->getRowArray();
        if (! $row) {
            $this->db->query(
                'INSERT INTO rate_limits (limit_key, window_start, hits) VALUES (?, NOW(), 1) ON DUPLICATE KEY UPDATE window_start = NOW(), hits = 1',
                [$key]
            );
            return;
        }

        $windowStart = strtotime((string) $row['window_start']);
        if ($windowStart === false || time() - $windowStart > 60) {
            $this->db->query('UPDATE rate_limits SET window_start = NOW(), hits = 1 WHERE limit_key = ?', [$key]);
            return;
        }
        if ((int) $row['hits'] >= 300) {
            throw new ApiException('Too many requests. Please slow down and try again in a moment.', 429);
        }
        $this->db->query('UPDATE rate_limits SET hits = hits + 1 WHERE limit_key = ?', [$key]);
    }

    public function destroySession(): void
    {
        $this->session->destroy();
        $this->validatedUserId = null;
    }

    public function touchSession(): void
    {
        if ((int) ($this->session->get('created_at') ?? 0) <= 0) {
            $this->session->set('created_at', time());
        }
        $this->session->set('last_activity', time());
    }

    public function registerPersistentSession(int $userId): void
    {
        $hash = $this->sessionHash();
        $expires = date('Y-m-d H:i:s', time() + 28800);
        $this->db->query(
            "INSERT INTO user_sessions (session_hash, user_id, ip_address, user_agent, expires_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), ip_address = VALUES(ip_address),
             user_agent = VALUES(user_agent), expires_at = VALUES(expires_at), revoked_at = NULL",
            [$hash, $userId, $this->clientIp(), substr((string) $this->request->getUserAgent(), 0, 500), $expires]
        );

        $countRow = $this->db->query(
            'SELECT COUNT(*) AS count FROM user_sessions WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW()',
            [$userId]
        )->getRowArray();
        $activeCount = (int) ($countRow['count'] ?? 0);
        if ($activeCount > 10) {
            $this->db->query(
                'UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW() AND session_hash != ? ORDER BY created_at ASC LIMIT ' . (int) ($activeCount - 10),
                [$userId, $hash]
            );
        }
    }

    private function validatePersistentSession(int $userId): bool
    {
        $hash = $this->sessionHash();
        $row = $this->db->query(
            'SELECT id, revoked_at, expires_at FROM user_sessions WHERE session_hash = ? AND user_id = ? LIMIT 1',
            [$hash, $userId]
        )->getRowArray();
        if (! $row) {
            $this->registerPersistentSession($userId);
            return true;
        }
        if (! empty($row['revoked_at']) || strtotime((string) $row['expires_at']) <= time()) {
            return false;
        }
        $lastSeen = (int) ($this->session->get('session_row_seen') ?? 0);
        if (time() - $lastSeen > 300) {
            $this->db->table('user_sessions')->where('id', (int) $row['id'])->update(['last_seen_at' => date('Y-m-d H:i:s')]);
            $this->session->set('session_row_seen', time());
        }
        return true;
    }

    private function recentAttemptCount(string $action, string $ip, int $seconds): int
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS count FROM auth_attempts
             WHERE action = ? AND ip_address = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . (int) $seconds . ' SECOND)',
            [$action, $ip]
        )->getRowArray();
        return (int) ($row['count'] ?? 0);
    }

    private function recentFailureCount(string $action, string $username, int $seconds, ?string $ip = null): int
    {
        $sql = 'SELECT COUNT(*) AS count FROM auth_attempts WHERE action = ? AND success = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . (int) $seconds . ' SECOND)';
        $params = [$action];
        if ($ip !== null) {
            $sql .= ' AND ip_address = ?';
            $params[] = $ip;
        } else {
            $sql .= ' AND username = ? AND ip_address = ?';
            $params[] = $username;
            $params[] = $this->clientIp();
        }
        return (int) ($this->db->query($sql, $params)->getRowArray()['count'] ?? 0);
    }
}
