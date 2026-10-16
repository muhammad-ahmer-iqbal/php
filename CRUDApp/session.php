<?php
// Run this before any HTML. 30 days expressed in seconds.
$cookieLifetime = 30 * 24 * 60 * 60;

// Keep the server's session data for as long as the browser cookie.
ini_set('session.gc_maxlifetime', (string) $cookieLifetime);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

// PHP creates the session cookie for us; these are its settings.
session_set_cookie_params([
    'lifetime' => $cookieLifetime, // Keep the cookie after the browser closes.
    'path' => '/', // Send it to pages on this website.
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true, // JavaScript cannot read this cookie.
    'samesite' => 'Lax', // Limit when other websites can send it.
]);

// With a session cookie, PHP loads the matching $_SESSION data.
// Without one, PHP creates a new session and sends its ID in a cookie.
session_start();

// Registered users and password hashes remain in the server-side session.
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [];
}

// Our student array replaces the database. It lasts only for this session.
if (!isset($_SESSION['students'])) {
    $_SESSION['students'] = [];
    $_SESSION['next_id'] = 1;
}

// Each form sends this token to help prevent submissions from other websites.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Escape user-entered text before displaying it inside HTML.
function escape($value)
{
    $data = (string) $value;
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Compare the form's hidden token with the one saved in the session.
function validFormToken()
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// Create and edit share these rules. An empty return value means no errors.
function validateStudent($student)
{
    // This pattern accepts between 1 and 100 Unicode characters.
    if (!preg_match('/^.{1,100}$/us', $student['name']) || !preg_match('/^.{1,100}$/us', $student['course'])) {
        return 'Name and course are required and must be at most 100 characters.';
    }

    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL) || strlen($student['email']) > 150) {
        return 'Enter a valid email address of up to 150 characters.';
    }

    return '';
}

// Cookies store preferences in the browser; student records stay in the session.
// Remember the last course so the next Add student form can prefill it.
function rememberCourse($course)
{
    setcookie('crud_last_course', $course, [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 days, in seconds.
        'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/', // This app's folder.
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, // PHP can read this cookie; JavaScript cannot.
        'samesite' => 'Lax',
    ]);
    // The browser sends the new value in $_COOKIE on the next request.
}
