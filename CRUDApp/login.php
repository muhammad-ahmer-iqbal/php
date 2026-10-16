<?php
require __DIR__ . '/session.php';

if (!empty($_SESSION['logged_in_username'])) {
    header('Location: index.php');
    exit;
}

$username = '';
$error = $usernameError = $passwordError = '';
$message = $_SESSION['auth_message'] ?? '';
unset($_SESSION['auth_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!validFormToken()) {
        $error = 'This form expired. Please try again.';
    }
    if (empty($username)) {
        $usernameError = 'Username is required.';
    }
    if (empty($password)) {
        $passwordError = 'Password is required.';
    }

    if (empty($error) && empty($usernameError) && empty($passwordError)) {
        $user = $_SESSION['users'][$username] ?? null;
        if (!$user || strlen($password) > 72 || strpos($password, "\0") !== false || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid username or password.';
        } else {
            // Give the login a new session ID and a fresh 30-day cookie.
            session_regenerate_id(true);
            $_SESSION['logged_in_username'] = $user['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['message'] = 'Logged in successfully.';
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in | PHP CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header><h1>Log in</h1><a href="index.php">Back to list</a></header>
    <p>Use an account registered in this browser. The login cookie lasts up to 30 days after login, even after closing the browser. Logging out or clearing cookies ends automatic login.</p>
    <p>Usernames and passwords are case-sensitive.</p>
    <?php if (!empty($message)): ?>
        <p class="notice success" role="status"><?= escape($message) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="notice error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>
    <form class="card student-form" method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" value="<?= escape($username) ?>" maxlength="30" autocomplete="username" required>
        <?php if (!empty($usernameError)): ?>
            <p class="notice error" role="alert"><?= escape($usernameError) ?></p>
        <?php endif; ?>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" maxlength="72" autocomplete="current-password" required>
        <?php if (!empty($passwordError)): ?>
            <p class="notice error" role="alert"><?= escape($passwordError) ?></p>
        <?php endif; ?>
        <div class="actions form-actions">
            <button type="submit">Log in</button>
            <a href="register.php">Create account</a>
        </div>
    </form>
</main>
</body>
</html>
