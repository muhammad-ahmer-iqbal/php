<?php
require __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the Log out button to sign out.');
}
if (!validFormToken()) {
    http_response_code(403);
    exit('This form expired. Go back and refresh the page.');
}

// Keep accounts so the user can log in again within this session.
unset($_SESSION['logged_in_username']);
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['auth_message'] = 'You have logged out.';
header('Location: login.php');
exit;
