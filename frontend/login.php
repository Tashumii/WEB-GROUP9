<?php
// start session
require_once __DIR__ . '/includes/session.php';

// redirect auth
if (!empty($_SESSION['user_id'])) {
    if (!empty($_SESSION['must_change_password'])) {
        header('Location: Settings.php');
    } elseif (empty($_SESSION['business_id'])) {
        header('Location: CreateBusiness.php');
    } else {
        header('Location: Dashboard.php');
    }
    exit;
}

// error message
$errorMessage = '';

// process post
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $loginUsername = trim($_POST['username'] ?? '');
    $loginPassword = (string)($_POST['password'] ?? '');

    if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session expired. Please refresh the page and try again.';
    } elseif ($loginUsername === '' || $loginPassword === '') {
        $errorMessage = 'Please enter both username and password.';
    } else {
        require_once __DIR__ . '/../backend/api/connectdb.php';

        if (is_login_rate_limited($pdo, $loginUsername)) {
            $errorMessage = 'Too many login attempts. Please try again in 15 minutes.';
        } else {
            try {
                $result = authenticate_credentials($pdo, $loginUsername, $loginPassword);
                record_auth_attempt($pdo, 'login', $loginUsername, true);
                establish_user_session($pdo, $result['user'], $result['memberships']);

                if (!empty($_SESSION['must_change_password'])) {
                    $redirect = 'Settings.php';
                } elseif (empty($_SESSION['business_id'])) {
                    $redirect = 'CreateBusiness.php';
                } else {
                    $redirect = 'Dashboard.php';
                }
                header('Location: ' . $redirect);
                exit;
            } catch (RuntimeException $e) {
                record_auth_attempt($pdo, 'login', $loginUsername, false);
                $errorMessage = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Login error: ' . $e->getMessage());
                $errorMessage = 'Connection error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Login | Sales Tracker</title>
    <!-- bootstrap cdn -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
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
            max-width: 440px;
        }
    </style>
</head>
<body>

<div class="login-box">

    <!-- brand header -->
    <div class="text-center mb-4">
        <a href="index.php" class="brand d-inline-flex align-items-center gap-2 mb-2">
            <span class="brand-icon"><i class="fa-solid fa-bag-shopping"></i></span>
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="mb-3">
                <label for="username" class="field-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="username" class="form-control ui-input border-start-0" 
                           placeholder="Enter your username" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="field-label mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control ui-input border-start-0 border-end-0" 
                           placeholder="••••••••" required>
                    <button type="button" class="input-group-text bg-white border-start-0" id="togglePassword" aria-label="Toggle password">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <p class="text-muted mb-3" style="font-size:0.78rem;">
                Forgot your password? Ask your business owner or admin to reset it.
            </p>

            <button type="submit" class="btn btn-pill active w-100 py-2 fw-semibold" id="submitBtn">
                Sign in
            </button>
        </form>

        <p class="text-center text-muted mt-3 mb-0" style="font-size:0.82rem;">
            New to Sales Tracker? <a href="register.php" class="text-dark fw-semibold">Create an account</a>
        </p>

        <hr class="my-3">
    </div>

    <!-- footer link -->
    <div class="text-center mt-3">
        <a href="index.php" class="text-muted" style="font-size:0.82rem;">
            <i class="fa-solid fa-arrow-left me-1"></i>Back to home
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
        toggleIcon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
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
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        fetch('../backend/api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfMeta ? csrfMeta.getAttribute('content') : ''
            },
            body: JSON.stringify({
                action: 'login',
                username: user,
                password: pass
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                var mustChange = data.user && Number(data.user.must_change_password) === 1;
                var hasBusiness = Number(data.active_business_id) > 0;
                window.location.href = mustChange ? 'Settings.php' : (!hasBusiness ? 'CreateBusiness.php' : 'Dashboard.php');
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
