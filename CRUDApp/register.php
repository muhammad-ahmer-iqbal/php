<?php
require __DIR__ . '/session.php';

$name = $email = $username = '';
$error = $nameError = $emailError = $usernameError = $passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    // Keep the password exactly as entered, including spaces.
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!validFormToken()) {
        $error = 'This form expired. Please try again.';
    }
    if (empty($name)) {
        $nameError = 'Name is required.';
    } elseif (!preg_match('/^.{1,100}$/us', $name)) {
        $nameError = 'Name must be at most 100 characters.';
    }
    if (empty($email)) {
        $emailError = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $emailError = 'Enter a valid email address of up to 150 characters.';
    }
    if (empty($username)) {
        $usernameError = 'Username is required.';
    } elseif (!preg_match('/\A[A-Za-z0-9_]{3,30}\z/', $username)) {
        $usernameError = 'Use 3 to 30 letters, numbers, or underscores.';
    } elseif (isset($_SESSION['users'][$username])) {
        $usernameError = 'This username is already registered in this session.';
    }
    if (empty($password)) {
        $passwordError = 'Password is required.';
    } elseif (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
        $passwordError = 'Use a password of 8 to 72 bytes without null characters.';
    }

    if (empty($error) && empty($nameError) && empty($emailError) && empty($usernameError) && empty($passwordError)) {
        // Store only the hash, never the original password.
        $_SESSION['users'][$username] = [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ];
        $_SESSION['auth_message'] = 'Account created. Log in with your username and password.';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PHP CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header><h1>Create user account</h1><a href="index.php">Back to list</a></header>
    <p>Your account is stored in a server session. A cookie lets this browser remember that session for up to 30 days. If the session expires or you clear cookies, register again.</p>
    <?php if (!empty($error)): ?>
        <p class="notice error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>
    <form class="card student-form" method="post" action="register.php">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <label for="name">Name</label>
        <input id="name" name="name" value="<?= escape($name) ?>" maxlength="100" autocomplete="name" required>
        <?php if (!empty($nameError)): ?>
            <p class="notice error" role="alert"><?= escape($nameError) ?></p>
        <?php endif; ?>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= escape($email) ?>" maxlength="150" autocomplete="email" required>
        <?php if (!empty($emailError)): ?>
            <p class="notice error" role="alert"><?= escape($emailError) ?></p>
        <?php endif; ?>
        <label for="username">Username</label>
        <input id="username" name="username" value="<?= escape($username) ?>" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" title="Use 3 to 30 letters, numbers, or underscores." autocomplete="username" required>
        <?php if (!empty($usernameError)): ?>
            <p class="notice error" role="alert"><?= escape($usernameError) ?></p>
        <?php endif; ?>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password-hint" required>
        <p id="password-hint">Use at least 8 characters, up to 72 bytes. Usernames and passwords are case-sensitive.</p>
        <?php if (!empty($passwordError)): ?>
            <p class="notice error" role="alert"><?= escape($passwordError) ?></p>
        <?php endif; ?>
        <div class="actions form-actions">
            <button type="submit">Register</button>
            <a href="login.php">Log in</a>
        </div>
    </form>
</main>
</body>
</html>
