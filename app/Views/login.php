<?php $errorMessage = $errorMessage ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Login | Sales Tracker</title>
    <!-- bootstrap cdn -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <!-- custom css -->
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
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
    <meta name="api-base-url" content="<?= esc(site_url('api')) ?>">
</head>
<body>

<div class="login-box">

    <!-- brand header -->
    <div class="text-center mb-4">
        <a href="<?= site_url('/') ?>" class="brand d-inline-flex align-items-center gap-2 mb-2">
            <span class="brand-icon"><i class="fa-solid fa-bag-shopping"></i></span>
            <span>Sales Tracker</span>
        </a>
        <p class="text-muted" style="font-size: 0.85rem;">Sign in to access your business dashboard</p>
    </div>

    <!-- login card -->
    <div class="ui-card shadow-sm p-4">
        <!-- alert box -->
        <div id="alertBox" class="alert alert-danger py-2 px-3 <?= empty($errorMessage) ? 'd-none' : '' ?>" role="alert" style="font-size:0.82rem;">
            <?= esc($errorMessage) ?>
        </div>

        <!-- login form -->
        <form method="POST" action="<?= site_url('login') ?>" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= esc($csrfToken) ?>">
            <div class="mb-3">
                <label for="username" class="field-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" id="username" class="form-control ui-input border-start-0" 
                           placeholder="Enter your username" maxlength="50" autocomplete="username" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="field-label mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control ui-input border-start-0 border-end-0" 
                           placeholder="••••••••" maxlength="72" autocomplete="current-password" required>
                    <button type="button" class="input-group-text bg-white border-start-0" id="togglePassword" aria-label="Show password" aria-pressed="false">
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
            New to Sales Tracker? <a href="<?= site_url('register') ?>" class="text-dark fw-semibold">Create an account</a>
        </p>

        <hr class="my-3">
    </div>

    <!-- footer link -->
    <div class="text-center mt-3">
        <a href="<?= site_url('/') ?>" class="text-muted" style="font-size:0.82rem;">
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
        toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
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
        fetch('<?= site_url('api/auth') ?>', {
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
                window.location.href = mustChange ? '<?= site_url('settings') ?>' : (!hasBusiness ? '<?= site_url('create-business') ?>' : '<?= site_url('dashboard') ?>');
            } else {
                alertBox.textContent = data.error || 'Invalid credentials.';
                alertBox.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign in';
            }
        })
        .catch(function () {
            // Fall back to a normal form POST when the API call itself fails.
            document.getElementById('loginForm').submit();
        });
    });
</script>

<script src="<?= base_url('js/api.js') ?>"></script>
</body>
</html>
