<?php
require_once __DIR__ . '/session.php';

// Use the dashboard's POST button to return to the login page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Please use the logout button on your dashboard.');
}

if (!csrf_token_is_valid()) {
    http_response_code(403);
    exit('Invalid form token. Return to your dashboard, refresh it, and try again.');
}

// Clear login variables and destroy the server-side session without custom cookie handling.
session_unset();
session_destroy();

header('Location: login.php?logged_out=1');
exit;
