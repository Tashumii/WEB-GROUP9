<?php
require_once __DIR__ . '/connectdb.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $userId = getCurrentUserId();
    if ($userId === null) {
        jsonResponse(['authenticated' => false, 'user' => null]);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, is_active, must_change_password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !(int)$user['is_active']) {
            destroy_app_session();
            jsonResponse(['authenticated' => false, 'user' => null]);
        }

        $memberships = getUserMemberships($pdo, $userId);
        $activeBusinessId = (int)($_SESSION['business_id'] ?? 0);
        $activeRole = null;
        foreach ($memberships as $membership) {
            if ((int)$membership['business_id'] === $activeBusinessId) {
                $activeRole = (string)$membership['role'];
                break;
            }
        }

        if ($activeRole === null && !empty($memberships)) {
            $activeBusinessId = (int)$memberships[0]['business_id'];
            $activeRole = (string)$memberships[0]['role'];
            $_SESSION['business_id'] = $activeBusinessId;
            $_SESSION['role'] = $activeRole;
        } elseif (empty($memberships)) {
            $activeBusinessId = 0;
            unset($_SESSION['business_id'], $_SESSION['role']);
        }

        $businesses = array_map(
            static fn(array $membership): array => [
                'id' => (int)$membership['business_id'],
                'name' => $membership['business_name'],
                'role' => $membership['role'],
            ],
            $memberships
        );

        jsonResponse([
            'authenticated' => true,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'must_change_password' => (int)$user['must_change_password'],
            ],
            'active_business_id' => $activeBusinessId,
            'active_role' => $activeRole,
            'businesses' => $businesses,
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

if ($action === 'login') {
    $username = input_string($body, 'username', 50);
    $password = (string)($body['password'] ?? '');

    if ($username === '' || $password === '') {
        jsonError('Username and password are required.', 400);
    }

    try {
        if (is_login_rate_limited($pdo, $username)) {
            jsonError('Too many login attempts. Please try again in 15 minutes.', 429);
        }

        $result = authenticate_credentials($pdo, $username, $password);
        record_auth_attempt($pdo, 'login', $username, true);
        establish_user_session($pdo, $result['user'], $result['memberships']);

        $memberships = $result['memberships'];
        $businesses = array_map(
            static fn(array $membership): array => [
                'id' => (int)$membership['business_id'],
                'name' => $membership['business_name'],
                'role' => $membership['role'],
            ],
            $memberships
        );

        jsonResponse([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => (int)$result['user']['id'],
                'username' => $result['user']['username'],
                'must_change_password' => (int)($result['user']['must_change_password'] ?? 0),
            ],
            'active_business_id' => (int)($_SESSION['business_id'] ?? 0),
            'active_role' => $_SESSION['role'] ?? null,
            'businesses' => $businesses,
        ]);
    } catch (RuntimeException $e) {
        record_auth_attempt($pdo, 'login', $username, false);
        jsonError($e->getMessage(), 401);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($action === 'logout') {
    logout_user($pdo);
    jsonResponse(['success' => true, 'message' => 'Logged out successfully.']);
}

if ($action === 'switch_business') {
    $businessId = input_int($body, 'business_id');
    if ($businessId <= 0) {
        jsonError('A valid business_id is required.', 400);
    }

    try {
        $membership = switch_user_business($pdo, $businessId);
        jsonResponse([
            'success' => true,
            'active_business_id' => (int)$membership['business_id'],
            'active_role' => $membership['role'],
            'business' => [
                'id' => (int)$membership['business_id'],
                'name' => $membership['business_name'],
            ],
            'csrf_token' => csrf_token(),
        ]);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

if ($action === 'register') {
    $username = input_string($body, 'username', 50);
    $password = (string)($body['password'] ?? '');
    $confirmPassword = (string)($body['confirm_password'] ?? '');

    $usernameError = validate_username($username);
    if ($usernameError !== null) {
        jsonError($usernameError, 400);
    }

    if ($password !== $confirmPassword) {
        jsonError('Passwords do not match.', 400);
    }

    $passwordError = validate_password($password, $username);
    if ($passwordError !== null) {
        jsonError($passwordError, 400);
    }

    try {
        if (is_register_rate_limited($pdo)) {
            jsonError('Too many accounts created from this location. Please try again later.', 429);
        }

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $check->execute([$username]);
        if ($check->fetch()) {
            record_auth_attempt($pdo, 'register', $username, false);
            jsonError('Username already taken.', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $iStmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, is_active)
            VALUES (?, ?, 1)
        ");
        $iStmt->execute([$username, $hash]);
        $newUserId = (int)$pdo->lastInsertId();

        record_auth_attempt($pdo, 'register', $username, true);

        establish_user_session(
            $pdo,
            ['id' => $newUserId, 'username' => $username, 'must_change_password' => 0, 'password_hash' => $hash],
            []
        );

        jsonResponse([
            'success' => true,
            'message' => 'Account created. Create your first business to continue.',
            'user' => [
                'id' => $newUserId,
                'username' => $username,
                'must_change_password' => 0,
            ],
            'active_business_id' => 0,
            'active_role' => null,
            'businesses' => [],
        ], 201);
    } catch (PDOException $e) {
        if ((string)$e->getCode() === '23000') {
            record_auth_attempt($pdo, 'register', $username, false);
            jsonError('Username already taken.', 409);
        }
        jsonDbError($e);
    } catch (Throwable $e) {
        jsonDbError($e);
    }
}

jsonError('Unknown action.', 400);
