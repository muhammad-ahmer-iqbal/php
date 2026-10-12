<?php
require __DIR__ . '/session.php';

// On GET, the ID comes from the URL. On POST, it comes from the hidden field.
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

// A student might be missing if the session expired or the ID was changed.
if (!$id || !isset($_SESSION['students'][$id])) {
    $_SESSION['message'] = 'Student not found in this session.';
    header('Location: index.php');
    exit;
}

$student = $_SESSION['students'][$id];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student['name'] = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $student['email'] = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $student['course'] = is_string($_POST['course'] ?? null) ? trim($_POST['course']) : '';
    $error = validFormToken() ? validateStudent($student) : 'This form expired. Please try again.';

    if ($error === '') {
        // UPDATE: replace this student's details while keeping the same ID.
        $_SESSION['students'][$id] = $student;
        rememberCourse($student['course']);
        $_SESSION['message'] = 'Student updated.';
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
    <title>Edit student | PHP CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header><h1>Edit student</h1><a href="index.php">Back to list</a></header>
    <?php if ($error): ?>
        <p class="notice error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>

    <!-- Load the existing values, then POST changes to this page. -->
    <form class="card student-form" method="post" action="edit.php">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="id" value="<?= escape($id) ?>">
        <label for="name">Name</label>
        <input id="name" name="name" value="<?= escape($student['name']) ?>" maxlength="100" autocomplete="name" required>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= escape($student['email']) ?>" maxlength="150" autocomplete="email" required>
        <label for="course">Course</label>
        <input id="course" name="course" value="<?= escape($student['course']) ?>" maxlength="100" required>
        <div class="actions form-actions">
            <button type="submit">Save changes</button>
            <a href="index.php">Cancel</a>
        </div>
    </form>
</main>
</body>
</html>
