<?php
require_once __DIR__ . '/includes/session.php';

$logoutToken = (string)($_POST['csrf_token'] ?? '');
if ($logoutToken === '' || !csrf_token_is_valid($logoutToken)) {
    http_response_code(403);
    exit('Invalid session check. Please go back and try again.');
}

if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../backend/api/connectdb.php';
    logout_user($pdo);
} else {
    destroy_app_session();
}

header('Location: login.php');
exit;
