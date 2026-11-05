<?php
// load db
require_once __DIR__ . '/connectdb.php';

// request method
$method = $_SERVER['REQUEST_METHOD'];

// get user
if ($method === 'GET') {
    $userId = getCurrentUserId();
    if (!$userId) {
        jsonResponse([
            'authenticated' => false,
            'user' => null
        ]);
    }

    // fetch user
    try {
        $stmt = $pdo->prepare("SELECT id, username, role, business_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            jsonResponse(['authenticated' => false, 'user' => null]);
        }

        // fetch businesses
        $businesses = [];
        if ($user['role'] === 'admin') {
            $bStmt = $pdo->query("SELECT id, name FROM businesses ORDER BY id ASC");
            $businesses = $bStmt->fetchAll();
        } elseif ($user['business_id']) {
            $bStmt = $pdo->prepare("SELECT id, name FROM businesses WHERE id = ?");
            $bStmt->execute([$user['business_id']]);
            $businesses = $bStmt->fetchAll();
        }

        jsonResponse([
            'authenticated' => true,
            'user' => $user,
            'active_business_id' => getActiveBusinessId($pdo),
            'businesses' => $businesses
        ]);
    } catch (Exception $e) {
        jsonError($e->getMessage(), 500);
    }
}

// post actions
if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? $_GET['action'] ?? 'login';

    // handle login
    if ($action === 'login') {
        $username = trim($body['username'] ?? '');
        $password = (string)($body['password'] ?? '');

        if ($username === '' || $password === '') {
            jsonError('Username and password are required');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role, business_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            $isMatch = $user && password_verify($password, $user['password_hash']);

            // demo fallback
            if (!$isMatch && $user) {
                if (($username === 'admin' && $password === 'admin123') ||
                    (in_array($username, ['staff_bsu', 'staff_caltex']) && $password === 'staff123')) {
                    $isMatch = true;
                    // update hash
                    try {
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $uStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $uStmt->execute([$newHash, $user['id']]);
                    } catch (Exception $e) {
                        // ignore error
                    }
                }
            }

            if (!$isMatch) {
                jsonError('Invalid username or password', 401);
            }

            // set session
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // assign business
            if ($user['role'] === 'admin') {
                $bStmt = $pdo->prepare("
                    SELECT b.id, b.name 
                    FROM businesses b
                    INNER JOIN admin_businesses ab ON ab.business_id = b.id
                    WHERE ab.user_id = ?
                    ORDER BY b.id ASC
                ");
                $bStmt->execute([$user['id']]);
                $allowed = $bStmt->fetchAll();
                if (empty($allowed)) {
                    $allowed = $pdo->query("SELECT id, name FROM businesses ORDER BY id ASC")->fetchAll();
                }
                $_SESSION['business_id'] = !empty($allowed) ? (int)$allowed[0]['id'] : 1;
            } else {
                $_SESSION['business_id'] = (int)$user['business_id'];
                $bStmt = $pdo->prepare("SELECT id, name FROM businesses WHERE id = ?");
                $bStmt->execute([$user['business_id']]);
                $allowed = $bStmt->fetchAll();
            }

            unset($user['password_hash']);
            jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'active_business_id' => $_SESSION['business_id'],
                'businesses' => $allowed
            ]);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    // handle logout
    if ($action === 'logout') {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        jsonResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    // switch business
    if ($action === 'switch_business') {
        $newBusId = (int)($body['business_id'] ?? 0);
        if ($newBusId <= 0) {
            jsonError('Valid business_id is required');
        }

        $userId = getCurrentUserId();
        $userRole = $_SESSION['role'] ?? 'staff';

        if ($userRole !== 'admin') {
            jsonError('Only admin can switch business scope', 403);
        }

        try {
            $check = $pdo->prepare("SELECT id, name FROM businesses WHERE id = ?");
            $check->execute([$newBusId]);
            $bus = $check->fetch();
            if (!$bus) {
                jsonError('Business not found', 404);
            }

            $_SESSION['business_id'] = $newBusId;
            jsonResponse([
                'success' => true,
                'active_business_id' => $newBusId,
                'business' => $bus
            ]);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    // register user
    if ($action === 'register') {
        $username = trim($body['username'] ?? '');
        $password = (string)($body['password'] ?? '');
        $role = $body['role'] ?? 'staff';
        $businessId = !empty($body['business_id']) ? (int)$body['business_id'] : null;

        if ($username === '' || strlen($password) < 4) {
            jsonError('Username and password (min 4 chars) required');
        }

        if (!in_array($role, ['admin', 'staff'], true)) {
            $role = 'staff';
        }

        try {
            // check unique
            $cStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $cStmt->execute([$username]);
            if ($cStmt->fetch()) {
                jsonError('Username already taken', 409);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $iStmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, business_id) VALUES (?, ?, ?, ?)");
            $iStmt->execute([$username, $hash, $role, $businessId]);
            $newId = (int)$pdo->lastInsertId();

            if ($role === 'admin' && $businessId) {
                $aStmt = $pdo->prepare("INSERT IGNORE INTO admin_businesses (user_id, business_id) VALUES (?, ?)");
                $aStmt->execute([$newId, $businessId]);
            }

            jsonResponse([
                'success' => true,
                'message' => 'User registered',
                'user_id' => $newId
            ], 201);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    jsonError('Unknown action');
}

// invalid method
jsonError('Method not allowed', 405);

