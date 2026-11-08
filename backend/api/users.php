<?php
require_once __DIR__ . '/connectdb.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

function users_team_member(PDO $pdo, int $businessId, int $userId): ?array {
    $stmt = $pdo->prepare("
        SELECT m.id AS membership_id, m.user_id, m.role, m.is_active,
               u.username, u.is_active AS user_active, u.last_login_at, u.created_at
        FROM memberships m
        JOIN users u ON u.id = m.user_id
        WHERE m.business_id = ? AND m.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$businessId, $userId]);
    $member = $stmt->fetch();
    return $member === false ? null : $member;
}

function users_valid_team_role(string $role): bool {
    return in_array($role, ['admin', 'staff'], true);
}

if ($method === 'GET') {
    try {
        $businessId = getActiveBusinessId($pdo);
        requireRole($pdo, $businessId, ['owner']);

        $stmt = $pdo->prepare("
            SELECT m.user_id, m.role, m.is_active, m.created_at,
                   u.username, u.is_active AS user_active, u.last_login_at
            FROM memberships m
            JOIN users u ON u.id = m.user_id
            WHERE m.business_id = ? AND m.is_active = 1
            ORDER BY FIELD(m.role, 'owner', 'admin', 'staff'), u.username
        ");
        $stmt->execute([$businessId]);
        $members = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'members' => array_map(
                static fn(array $member): array => [
                    'user_id' => (int)$member['user_id'],
                    'username' => $member['username'],
                    'role' => $member['role'],
                    'is_active' => (int)$member['is_active'],
                    'user_active' => (int)$member['user_active'],
                    'last_login_at' => $member['last_login_at'],
                    'created_at' => $member['created_at'],
                ],
                $members
            ),
        ]);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($method !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body = getJsonBody();
$action = (string)($body['action'] ?? '');

if ($action === 'change_password') {
    $currentPassword = (string)($body['current_password'] ?? '');
    $newPassword = (string)($body['new_password'] ?? '');
    $confirmPassword = (string)($body['confirm_password'] ?? '');
    $userId = getCurrentUserId();

    if ($userId === null) {
        jsonError('Authentication required.', 401);
    }
    if ($currentPassword === '') {
        jsonError('Current password is required.', 400);
    }
    if ($newPassword !== $confirmPassword) {
        jsonError('New passwords do not match.', 400);
    }

    $passwordError = validate_password($newPassword, (string)($_SESSION['username'] ?? ''));
    if ($passwordError !== null) {
        jsonError($passwordError, 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if ($hash === false || !password_verify($currentPassword, (string)$hash)) {
            jsonError('Current password is incorrect.', 400);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare("
            UPDATE users
            SET password_hash = ?, must_change_password = 0, updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$newHash, $userId]);
        $_SESSION['must_change_password'] = 0;
        $_SESSION['auth_fingerprint'] = hash('sha256', $newHash);
        revoke_user_sessions($pdo, $userId, app_session_row_hash());
        $previousHash = app_session_row_hash();
        session_regenerate_id(true);
        sync_persistent_session($pdo, $userId);
        revoke_session_hash($pdo, $previousHash);
        rotate_csrf_token();

        jsonResponse(['success' => true, 'message' => 'Password updated. Your other devices have been signed out.']);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

try {
    $businessId = getActiveBusinessId($pdo);
    $ownerId = getCurrentUserId();
    requireRole($pdo, $businessId, ['owner']);
} catch (Throwable $e) {
    jsonDbError($e);
}

if ($ownerId === null) {
    jsonError('Authentication required.', 401);
}

if ($action === 'create') {
    $username = input_string($body, 'username', 50);
    $password = (string)($body['password'] ?? '');
    $confirmPassword = (string)($body['confirm_password'] ?? '');
    $role = input_string($body, 'role', 20);

    $usernameError = validate_username($username);
    if ($usernameError !== null) {
        jsonError($usernameError, 400);
    }
    if (!users_valid_team_role($role)) {
        jsonError('Role must be admin or staff.', 400);
    }
    if ($password !== $confirmPassword) {
        jsonError('Passwords do not match.', 400);
    }

    $passwordError = validate_password($password, $username);
    if ($passwordError !== null) {
        jsonError($passwordError, 400);
    }

    try {
        $check = $pdo->prepare("SELECT id, password_hash, is_active, username FROM users WHERE username = ? LIMIT 1");
        $check->execute([$username]);
        $existing = $check->fetch();

        if ($existing) {
            if (!(int)$existing['is_active'] || !password_verify($password, (string)$existing['password_hash'])) {
                jsonError('That account could not be added. Check the password or contact the administrator.', 409);
            }

            $existingMembership = users_team_member($pdo, $businessId, (int)$existing['id']);
            if ($existingMembership && (int)$existingMembership['is_active'] === 1) {
                jsonError('That account is already part of this business.', 409);
            }

            if ($existingMembership) {
                $update = $pdo->prepare("
                    UPDATE memberships
                    SET role = ?, is_active = 1, created_by = ?
                    WHERE id = ?
                ");
                $update->execute([$role, $ownerId, (int)$existingMembership['membership_id']]);
            } else {
                $insert = $pdo->prepare("
                    INSERT INTO memberships (user_id, business_id, role, created_by)
                    VALUES (?, ?, ?, ?)
                ");
                $insert->execute([(int)$existing['id'], $businessId, $role, $ownerId]);
            }

            jsonResponse([
                'success' => true,
                'message' => 'Existing account added to this business.',
                'user_id' => (int)$existing['id'],
            ]);
        }

        $pdo->beginTransaction();

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userInsert = $pdo->prepare("
            INSERT INTO users (username, password_hash, is_active)
            VALUES (?, ?, 1)
        ");
        $userInsert->execute([$username, $hash]);
        $newUserId = (int)$pdo->lastInsertId();

        $memberInsert = $pdo->prepare("
            INSERT INTO memberships (user_id, business_id, role, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $memberInsert->execute([$newUserId, $businessId, $role, $ownerId]);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => 'Team member created.',
            'user_id' => $newUserId,
        ], 201);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ((string)$e->getCode() === '23000') {
            jsonError('Username already taken.', 409);
        }
        jsonDbError($e);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonDbError($e);
    }
}

if ($action === 'update_role') {
    $targetUserId = input_int($body, 'user_id');
    $role = input_string($body, 'role', 20);

    if ($targetUserId <= 0 || !users_valid_team_role($role)) {
        jsonError('A valid user and role are required.', 400);
    }

    try {
        $member = users_team_member($pdo, $businessId, $targetUserId);
        if (!$member || (int)$member['is_active'] !== 1) {
            jsonError('Team member not found.', 404);
        }
        if ($member['role'] === 'owner') {
            jsonError('The business owner role cannot be changed.', 403);
        }

        $update = $pdo->prepare("UPDATE memberships SET role = ? WHERE id = ?");
        $update->execute([$role, (int)$member['membership_id']]);

        jsonResponse(['success' => true, 'message' => 'Role updated.']);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($action === 'remove') {
    $targetUserId = input_int($body, 'user_id');

    if ($targetUserId <= 0) {
        jsonError('A valid user_id is required.', 400);
    }
    if ($targetUserId === $ownerId) {
        jsonError('You cannot remove yourself from the business.', 400);
    }

    try {
        $member = users_team_member($pdo, $businessId, $targetUserId);
        if (!$member || (int)$member['is_active'] !== 1) {
            jsonError('Team member not found.', 404);
        }
        if ($member['role'] === 'owner') {
            jsonError('The business owner cannot be removed.', 403);
        }

        $update = $pdo->prepare("UPDATE memberships SET is_active = 0 WHERE id = ?");
        $update->execute([(int)$member['membership_id']]);

        $remaining = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE user_id = ? AND is_active = 1");
        $remaining->execute([$targetUserId]);
        if ((int)$remaining->fetchColumn() === 0) {
            revoke_user_sessions($pdo, $targetUserId);
        }

        jsonResponse(['success' => true, 'message' => 'Team member removed.']);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($action === 'reset_password') {
    $targetUserId = input_int($body, 'user_id');
    $newPassword = (string)($body['new_password'] ?? '');
    $confirmPassword = (string)($body['confirm_password'] ?? '');

    if ($targetUserId <= 0) {
        jsonError('A valid user_id is required.', 400);
    }
    if ($newPassword !== $confirmPassword) {
        jsonError('Passwords do not match.', 400);
    }

    try {
        $member = users_team_member($pdo, $businessId, $targetUserId);
        if (!$member || (int)$member['is_active'] !== 1) {
            jsonError('Team member not found.', 404);
        }
        if ($member['role'] === 'owner') {
            jsonError('Owner passwords cannot be reset from team management.', 403);
        }

        $memberPasswordError = validate_password($newPassword, (string)($member['username'] ?? ''));
        if ($memberPasswordError !== null) {
            jsonError($memberPasswordError, 400);
        }

        $membershipCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM memberships
            WHERE user_id = ? AND is_active = 1
        ");
        $membershipCount->execute([$targetUserId]);
        if ((int)$membershipCount->fetchColumn() > 1) {
            jsonError('This account belongs to multiple businesses. The member must change their own password.', 409);
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare("
            UPDATE users
            SET password_hash = ?, must_change_password = 1, updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$hash, $targetUserId]);
        revoke_user_sessions($pdo, $targetUserId);

        jsonResponse(['success' => true, 'message' => 'Password reset. The member must change it after signing in.']);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

jsonError('Unknown action.', 400);
