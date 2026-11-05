<?php
// start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// redirect login
header('Location: login.php');
exit;

