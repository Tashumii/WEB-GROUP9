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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Create Account | Sales Tracker</title>
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
        .register-box {
            width: 100%;
            max-width: 480px;
        }
    </style>
</head>
<body>

<div class="register-box">

    <!-- brand header -->
    <div class="text-center mb-4">
        <a href="index.php" class="brand d-inline-flex align-items-center gap-2 mb-2">
            <span class="brand-icon"><i class="fa-solid fa-bag-shopping"></i></span>
            <span>Sales Tracker</span>
        </a>
        <p class="text-muted" style="font-size: 0.85rem;">Create your account, then set up your first business</p>
    </div>

    <!-- register card -->
    <div class="ui-card shadow-sm p-4">
        <!-- alert box -->
        <div id="alertBox" class="alert alert-danger py-2 px-3 d-none" style="font-size:0.82rem;"></div>
        <noscript>
            <div class="alert alert-warning py-2 px-3" style="font-size:0.82rem;">
                JavaScript is required to create an account.
            </div>
        </noscript>

        <!-- register form -->
        <form method="POST" action="register.php" id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="mb-3">
                <label for="username" class="field-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="username" class="form-control ui-input border-start-0"
                           placeholder="letters, numbers, dot, underscore or dash" maxlength="50" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="field-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control ui-input border-start-0 border-end-0"
                           placeholder="At least 8 characters" minlength="8" maxlength="72" required>
                    <button type="button" class="input-group-text bg-white border-start-0 toggle-password" data-target="password" aria-label="Toggle password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirmPassword" class="field-label">Confirm password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control ui-input border-start-0 border-end-0"
                           placeholder="Re-enter your password" minlength="8" maxlength="72" required>
                    <button type="button" class="input-group-text bg-white border-start-0 toggle-password" data-target="confirmPassword" aria-label="Toggle password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-pill active w-100 py-2 fw-semibold" id="submitBtn">
                Create account
            </button>
        </form>
    </div>

    <!-- footer link -->
    <div class="text-center mt-3">
        <a href="login.php" class="text-muted" style="font-size:0.82rem;">
            Already have an account? <span class="text-dark fw-semibold">Sign in</span>
        </a>
    </div>

</div>

<!-- client script -->
<script>
    // toggle eye
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            var icon = btn.querySelector('i');
            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    });

    // show error
    function showError(message) {
        var alertBox = document.getElementById('alertBox');
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    var registerForm = document.getElementById('registerForm');
    var submitBtn = document.getElementById('submitBtn');

    // ajax submit
    registerForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirmPassword').value;

        document.getElementById('alertBox').classList.add('d-none');

        if (!username || !password || !confirmPassword) {
            showError('Please fill in all fields.');
            return;
        }

        if (!/^[A-Za-z0-9_.-]{3,50}$/.test(username)) {
            showError('Username must be 3-50 characters using letters, numbers, dot, underscore or dash.');
            return;
        }

        if (password.length < 8) {
            showError('Password must be at least 8 characters.');
            return;
        }

        if (password !== confirmPassword) {
            showError('Passwords do not match.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating account...';

        // fetch api
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        fetch('../backend/api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfMeta ? csrfMeta.getAttribute('content') : ''
            },
            body: JSON.stringify({
                action: 'register',
                username: username,
                password: password,
                confirm_password: confirmPassword
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                window.location.href = 'CreateBusiness.php';
            } else {
                showError(data.error || 'Could not create your account.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create account';
            }
        })
        .catch(function () {
            showError('Connection error. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create account';
        });
    });
</script>

</body>
</html>
