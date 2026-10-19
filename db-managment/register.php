<?php
require_once __DIR__ . '/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$name = '';
$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept text fields only. trim() removes accidental surrounding whitespace.
    $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) && is_string($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    // Never trim passwords: spaces may be an intentional part of a password.
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (!csrf_token_is_valid()) {
        $errors[] = 'Your form expired or is invalid. Please try again.';
    }

    // HTML validation is helpful, but these server checks cannot be bypassed by the browser.
    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Please fill in every field.';
    }
    // strlen() counts bytes; some Unicode characters use more than one byte.
    if (strlen($name) > 100) {
        $errors[] = 'Name is too long (maximum 100 bytes).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Please enter a valid email address (maximum 254 bytes).';
    }
    // Bcrypt uses at most 72 bytes. Reject longer passwords instead of silently truncating.
    if (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
        $errors[] = 'Password must be 8 to 72 bytes and cannot contain null characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        require __DIR__ . '/db.php';

        try {
            // query() sends an SQL string directly to MySQL and returns SELECT rows.
            // Input is inserted directly into SQL here, so this is vulnerable to SQL injection.
            $sql = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
            $result = $conn->query($sql);
            $emailExists = $result->num_rows > 0;

            if ($emailExists) {
                $errors[] = 'An account with that email already exists.';
            } else {
                // Hashing is one-way. PHP creates a random salt and stores it in the hash.
                // Store this hash, never the original password.
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                // INSERT creates a new row. query() returns true when it succeeds.
                $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$passwordHash')";
                $conn->query($sql);

                // The session carries this one-time message across the redirect.
                $_SESSION['success_message'] = 'Registration successful! You can now log in.';
                header('Location: login.php');
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            // Two requests can pass the email check together; the UNIQUE key catches this.
            if ($exception->getCode() === 1062) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $errors[] = 'Registration is unavailable. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PHP Learning</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="card">
    <p class="eyebrow">PHP Learning</p>
    <h1>Create an account</h1>
    <p>Register to access your dashboard.</p>

    <?php if (!empty($errors)): ?>
        <div class="message error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="register.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="name">Name</label>
        <!-- htmlspecialchars() displays input as text instead of executable HTML. -->
        <input id="name" name="name" type="text" value="<?= $name ?>" maxlength="100" autocomplete="name" required>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" maxlength="254" autocomplete="email" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required>
        <small id="password-help">Use 8–72 bytes. Most English characters use one byte; other characters may use more.</small>

        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>

        <button type="submit">Create account</button>
    </form>
    <p class="footer">Already registered? <a href="login.php">Log in</a></p>
</main>
</body>
</html>
