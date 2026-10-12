<?php
require __DIR__ . '/session.php';

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id || !isset($_SESSION['students'][$id])) {
    $_SESSION['message'] = 'Student not found in this session.';
    header('Location: index.php');
    exit;
}

$student = $_SESSION['students'][$id];
$error = '';

// Opening this page does NOT delete anything. Confirming the POST form does.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validFormToken()) {
        $error = 'This form expired. Please try again.';
    } else {
        // DELETE: unset removes only this student from the session array.
        unset($_SESSION['students'][$id]);
        $_SESSION['message'] = 'Student deleted.';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete student | PHP CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header><h1>Delete student</h1><a href="index.php">Back to list</a></header>
    <?php if ($error): ?>
        <p class="notice error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>

    <form class="card student-form" method="post" action="delete.php">
        <p>Delete <strong><?= escape($student['name']) ?></strong> (<?= escape($student['email']) ?>)?</p>
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="id" value="<?= escape($id) ?>">
        <div class="actions form-actions">
            <button class="delete" type="submit">Yes, delete student</button>
            <a href="index.php">Cancel</a>
        </div>
    </form>
</main>
</body>
</html>
