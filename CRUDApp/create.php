<?php
require __DIR__ . '/session.php';

// Cookies are user-controlled: check the type and escape the displayed value.
$lastCourse = $_COOKIE['crud_last_course'] ?? '';
$student = ['name' => '', 'email' => '', 'course' => is_string($lastCourse) ? $lastCourse : ''];
$error = '';
$nameError = '';
$emailError = '';
$courseError = '';

// GET opens the form. POST processes the submitted form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student['name'] = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $student['email'] = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $student['course'] = is_string($_POST['course'] ?? null) ? trim($_POST['course']) : '';
    $error = validFormToken() ? '' : 'This form expired. Please try again.';

    if (empty($student['name'])) {
        $nameError = 'Name is required.';
    } elseif (!preg_match('/^.{1,100}$/us', $student['name'])) {
        $nameError = 'Name must be at most 100 characters.';
    }

    if (empty($student['email'])) {
        $emailError = 'Email is required.';
    } elseif (!filter_var($student['email'], FILTER_VALIDATE_EMAIL) || strlen($student['email']) > 150) {
        $emailError = 'Enter a valid email address of up to 150 characters.';
    }

    if (empty($student['course'])) {
        $courseError = 'Course is required.';
    } elseif (!preg_match('/^.{1,100}$/us', $student['course'])) {
        $courseError = 'Course must be at most 100 characters.';
    } elseif (substr($student['course'], -3) !== 'apt') {
        $courseError = 'Course name must end with "apt".';
    }

    if (empty($error) && empty($nameError) && empty($emailError) && empty($courseError)) {
        // CREATE: use a new ID as the key in the session's student array.
        $id = $_SESSION['next_id'];
        $student['id'] = $id;
        $_SESSION['students'][$id] = $student;
        $_SESSION['next_id']++; // The next student gets a different ID.

        rememberCourse($student['course']);
        $_SESSION['message'] = 'Student added.';

        // Redirect so refreshing the list does not add this student again.
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
    <title>Add student | PHP CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header><h1>Add student</h1><a href="index.php">Back to list</a></header>
    <p>Your last saved course is remembered in a cookie for 30 days.</p>
    <?php if (!empty($error)): ?>
        <p class="notice error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>

    <!-- Values remain filled if validation fails. -->
    <form class="card student-form" method="post" action="<?= escape($_SERVER['PHP_SELF']) ?>">
        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">
        <label for="name">Name</label>
        <input id="name" name="name" value="<?= escape($student['name']) ?>" maxlength="100" autocomplete="name" required<?= !empty($nameError) ? ' aria-invalid="true" aria-describedby="name-error"' : '' ?>>
        <?php if (!empty($nameError)): ?>
            <p id="name-error" class="notice error" role="alert"><?= escape($nameError) ?></p>
        <?php endif; ?>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= escape($student['email']) ?>" maxlength="150" autocomplete="email" required<?= !empty($emailError) ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
        <?php if (!empty($emailError)): ?>
            <p id="email-error" class="notice error" role="alert"><?= escape($emailError) ?></p>
        <?php endif; ?>
        <label for="course">Course</label>
        <input id="course" name="course" value="<?= escape($student['course']) ?>" maxlength="100" pattern=".*apt\s*" title="Course name must end with lowercase apt." required aria-describedby="course-hint<?= !empty($courseError) ? ' course-error' : '' ?>"<?= !empty($courseError) ? ' aria-invalid="true"' : '' ?>>
        <p id="course-hint">Course name must end with lowercase <strong>apt</strong>, for example PHP apt.</p>
        <?php if (!empty($courseError)): ?>
            <p id="course-error" class="notice error" role="alert"><?= escape($courseError) ?></p>
        <?php endif; ?>
        <div class="actions form-actions">
            <button type="submit">Add student</button>
            <a href="index.php">Cancel</a>
        </div>
    </form>
</main>
</body>
</html>
