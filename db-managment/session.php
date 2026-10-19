<?php
// Sessions store login data on the server between page requests.
// PHP manages its standard session ID cookie automatically; no custom cookie code is needed.
ini_set('session.use_strict_mode', '1'); // Reject session IDs PHP did not create.
session_start(); // Start or resume the session before sending HTML or redirects.
header('Cache-Control: no-store');

// Store a random token in the session to check that submitted forms belong to it.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token_is_valid()
{
    return isset($_POST['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
