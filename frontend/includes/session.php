<?php
// Shared session bootstrap for frontend pages.
require_once __DIR__ . '/../../backend/api/session_boot.php';

app_send_security_headers();
start_app_session();

if (app_session_expired()) {
    destroy_app_session();
} elseif (!empty($_SESSION['user_id'])) {
    touch_app_session();
}
