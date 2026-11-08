<?php
require_once __DIR__ . '/includes/session.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!empty($_SESSION['must_change_password'])) {
    header('Location: Settings.php');
    exit;
}

if (empty($_SESSION['business_id'])) {
    require_once __DIR__ . '/../backend/api/connectdb.php';
    $existingMemberships = getUserMemberships($pdo, (int)$_SESSION['user_id']);
    if (!empty($existingMemberships)) {
        switch_user_business($pdo, (int)$existingMemberships[0]['business_id']);
        header('Location: Dashboard.php');
        exit;
    }
}

if (!empty($_SESSION['business_id'])) {
    header('Location: Dashboard.php');
    exit;
}

$currentUsername = htmlspecialchars((string)($_SESSION['username'] ?? 'User'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
    <title>Create Business | Sales Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
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
        .setup-box {
            width: 100%;
            max-width: 460px;
        }
    </style>
</head>
<body>

<div class="setup-box">
    <div class="text-center mb-4">
        <a href="index.php" class="brand d-inline-flex align-items-center gap-2 mb-2">
            <span class="brand-icon"><i class="fa-solid fa-bag-shopping"></i></span>
            <span>Sales Tracker</span>
        </a>
        <p class="text-muted" style="font-size: 0.85rem;">Signed in as <?= $currentUsername ?>. Create your first business to continue.</p>
    </div>

    <div class="ui-card shadow-sm p-4">
        <div id="alertBox" class="alert alert-danger py-2 px-3 d-none" style="font-size:0.82rem;"></div>
        <noscript>
            <div class="alert alert-warning py-2 px-3" style="font-size:0.82rem;">
                JavaScript is required to create a business.
            </div>
        </noscript>

        <h1 class="h5 fw-bold mb-1">Create your business</h1>
        <p class="text-muted mb-4" style="font-size:0.82rem;">You become the owner. Add staff later from Settings &rarr; Team.</p>

        <form id="createBusinessForm" novalidate>
            <div class="mb-3">
                <label for="businessName" class="field-label">Business name</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-store"></i></span>
                    <input type="text" name="name" id="businessName" class="form-control ui-input border-start-0"
                           placeholder="e.g. School Supplies near at BSU" maxlength="255" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-pill active w-100 py-2 fw-semibold" id="submitBtn">
                Create business
            </button>
        </form>
    </div>

    <div class="text-center mt-3">
        <form method="post" action="logout.php" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button type="submit" class="btn btn-link text-muted p-0" style="font-size:0.82rem;">
                <i class="fa-solid fa-arrow-right-from-bracket me-1" aria-hidden="true"></i>Log out
            </button>
        </form>
    </div>
</div>

<script>
    function showError(message) {
        var alertBox = document.getElementById('alertBox');
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    document.getElementById('createBusinessForm').addEventListener('submit', function (event) {
        event.preventDefault();

        var input = document.getElementById('businessName');
        var button = document.getElementById('submitBtn');
        var name = input.value.trim();

        document.getElementById('alertBox').classList.add('d-none');

        if (!name) {
            showError('Please enter a business name.');
            return;
        }

        button.disabled = true;
        button.textContent = 'Creating business...';

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');

        fetch('../backend/api/businesses.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfMeta ? csrfMeta.getAttribute('content') : ''
            },
            body: JSON.stringify({ action: 'create', name: name })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                window.location.href = 'Dashboard.php';
            } else {
                showError(data.error || 'Could not create your business.');
                button.disabled = false;
                button.textContent = 'Create business';
            }
        })
        .catch(function () {
            showError('Connection error. Please try again.');
            button.disabled = false;
            button.textContent = 'Create business';
        });
    });
</script>

</body>
</html>
