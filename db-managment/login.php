<?php
require_once __DIR__ . '/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$email = '';
$errors = [];
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']); // Show the registration message only once.

if (isset($_GET['logged_out'])) {
    $successMessage = 'You have been logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) && is_string($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if (!csrf_token_is_valid()) {
        $errors[] = 'Your form expired or is invalid. Please try again.';
    }
    if ($email === '' || $password === '') {
        $errors[] = 'Please enter your email and password.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) > 72 || strpos($password, "\0") !== false) {
        $errors[] = 'Email or password is incorrect.';
    }

    if (empty($errors)) {
        require __DIR__ . '/db.php';

        try {
            // prepare() creates an SQL template; ? is a placeholder for the email.
            $stmt = $conn->prepare('SELECT id, name, password FROM users WHERE email = ? LIMIT 1');
            // bind_param() supplies the email separately from SQL. "s" means a string.
            $stmt->bind_param('s', $email);
            // execute() runs the statement using the bound email value.
            $stmt->execute();
            // get_result() returns the SELECT rows (requires PHP's mysqlnd driver).
            $result = $stmt->get_result();
            $user = $result->fetch_assoc(); // Read one row as an array, or null if none exists.
            $stmt->close(); // Release the statement after reading its result.

            // password_verify() compares the entered password with the stored hash.
            // It reads the salt and hashing settings from the hash; no decryption is needed.
            if ($user && password_verify($password, $user['password'])) {
                // Replace the session ID after login to prevent session fixation.
                session_regenerate_id(true);
                // These values remain available on later pages; never save the password here.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: dashboard.php');
                exit;
            } else {
                // Use the same message for an unknown email and a wrong password.
                $errors[] = 'Email or password is incorrect.';
            }
        } catch (mysqli_sql_exception $exception) {
            $errors[] = 'Login is unavailable. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PHP Learning</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="card">
    <p class="eyebrow">PHP Learning</p>
    <h1>Welcome back</h1>
    <p>Log in to your account.</p>

    <?php if ($successMessage !== ''): ?>
        <p class="message success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" maxlength="254" autocomplete="email" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>

        <button type="submit">Log in</button>
    </form>
    <p class="footer">Need an account? <a href="register.php">Register</a></p>
</main>
</body>
</html>
