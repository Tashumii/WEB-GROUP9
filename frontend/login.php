<?php
// start session
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// redirect auth
if (!empty($_SESSION['user_id'])) {
    header('Location: Dashboard.php');
    exit;
}

// error message
$errorMessage = '';

// process post
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorMessage = 'Please enter both username and password.';
    } else {
        // load db
        require_once __DIR__ . '/../backend/api/connectdb.php';

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

            if ($isMatch) {
                // set session
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // assign business
                if ($user['role'] === 'admin') {
                    $bStmt = $pdo->prepare("
                        SELECT b.id FROM businesses b
                        INNER JOIN admin_businesses ab ON ab.business_id = b.id
                        WHERE ab.user_id = ?
                        ORDER BY b.id ASC LIMIT 1
                    ");
                    $bStmt->execute([$user['id']]);
                    $row = $bStmt->fetch();
                    $_SESSION['business_id'] = $row ? (int)$row['id'] : 1;
                } else {
                    $_SESSION['business_id'] = (int)$user['business_id'];
                }

                header('Location: Dashboard.php');
                exit;
            } else {
                $errorMessage = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $errorMessage = 'Connection error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Sales Tracker</title>
    <!-- bootstrap cdn -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- custom css -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0ef;
            padding: 1.5rem;
        }
        .login-box {
            width: 100%;
            max-width: 420px;
        }
        .demo-pill {
            cursor: pointer;
            font-size: 0.72rem;
        }
    </style>
</head>
<body>

<div class="login-box">

    <!-- brand header -->
    <div class="text-center mb-4">
        <a href="index.php" class="brand d-inline-flex align-items-center gap-2 mb-2">
            <span class="brand-icon"><i class="bi bi-bag-fill"></i></span>
            <span>Sales Tracker</span>
        </a>
        <p class="text-muted" style="font-size: 0.85rem;">Sign in to access your business dashboard</p>
    </div>

    <!-- login card -->
    <div class="ui-card shadow-sm p-4">
        <!-- alert box -->
        <div id="alertBox" class="alert alert-danger py-2 px-3 <?= empty($errorMessage) ? 'd-none' : '' ?>" style="font-size:0.82rem;">
            <?= htmlspecialchars($errorMessage) ?>
        </div>

        <!-- login form -->
        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label for="username" class="field-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" id="username" class="form-control ui-input border-start-0" 
                           placeholder="e.g. admin or staff_bsu" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="field-label mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control ui-input border-start-0 border-end-0" 
                           placeholder="••••••••" required>
                    <button type="button" class="input-group-text bg-white border-start-0" id="togglePassword" aria-label="Toggle password">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label text-muted" for="rememberMe" style="font-size:0.78rem;">
                        Remember me
                    </label>
                </div>
                <a href="#" class="text-muted" style="font-size:0.78rem;">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-pill active w-100 py-2 fw-semibold" id="submitBtn">
                Sign in
            </button>
        </form>

        <hr class="my-3">

        <!-- demo pills -->
        <div class="text-center">
            <span class="text-muted d-block mb-2" style="font-size:0.75rem;">Quick demo credentials:</span>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-pill btn-sm demo-pill" data-user="admin" data-pass="admin123">
                    Admin
                </button>
                <button type="button" class="btn btn-pill btn-sm demo-pill" data-user="staff_bsu" data-pass="staff123">
                    Staff (BSU)
                </button>
            </div>
        </div>
    </div>

    <!-- footer link -->
    <div class="text-center mt-3">
        <a href="index.php" class="text-muted" style="font-size:0.82rem;">
            <i class="bi bi-arrow-left me-1"></i>Back to home
        </a>
    </div>

</div>

<!-- client script -->
<script>
    // toggle eye
    var toggleBtn = document.getElementById('togglePassword');
    var passwordInput = document.getElementById('password');
    var toggleIcon = document.getElementById('toggleIcon');

    toggleBtn.addEventListener('click', function () {
        var isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // demo fill
    document.querySelectorAll('.demo-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            document.getElementById('username').value = pill.dataset.user;
            document.getElementById('password').value = pill.dataset.pass;
        });
    });

    // ajax submit
    document.getElementById('loginForm').addEventListener('submit', function (e) {
        var user = document.getElementById('username').value.trim();
        var pass = document.getElementById('password').value;
        var alertBox = document.getElementById('alertBox');
        var submitBtn = document.getElementById('submitBtn');

        if (!user || !pass) return;

        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in...';
        alertBox.classList.add('d-none');

        // fetch api
        fetch('../backend/api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'login',
                username: user,
                password: pass
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                window.location.href = 'Dashboard.php';
            } else {
                alertBox.textContent = data.error || 'Invalid credentials.';
                alertBox.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign in';
            }
        })
        .catch(function () {
            // fallback post
            document.getElementById('loginForm').submit();
        });
    });
</script>

</body>
</html>
