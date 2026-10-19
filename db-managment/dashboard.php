<?php
require_once __DIR__ . '/session.php';

// Check the session before showing private content, including on direct visits.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PHP Learning</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="card">
    <p class="eyebrow">Your dashboard</p>
    <!-- A saved name is still user input, so escape it before inserting it into HTML. -->
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?>!</h1>
    <p>You are logged in. Your session keeps you signed in between page requests.</p>

    <!-- Logout changes session state, so submit a POST form with a CSRF token. -->
    <form action="logout.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Log out</button>
    </form>
</main>
</body>
</html>
