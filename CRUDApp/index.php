<?php
require __DIR__ . '/session.php';

// READ: get the students from the session, showing the newest first.
$students = array_reverse($_SESSION['students'], true);

// Show a message once after create, edit, or delete redirects here.
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student list | PHP CRUD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <header>
        <div>
            <p class="eyebrow">PHP CRUD APPLICATION</p>
            <h1>Student list</h1>
        </div>
        <a class="button" href="create.php">Add student</a>
    </header>

    <p>Students are saved only in your current session. No database is needed.</p>
    <?php if ($message): ?>
        <p class="notice success" role="status"><?= escape($message) ?></p>
    <?php endif; ?>

    <div class="card table-wrapper">
        <table>
            <thead>
                <tr><th scope="col">ID</th><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Course</th><th scope="col">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
                <tr><td class="empty" colspan="5">No students yet. Add your first student.</td></tr>
            <?php endif; ?>
            <!-- Each student's ID links to a separate edit or delete page. -->
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= escape($student['id']) ?></td>
                    <td><?= escape($student['name']) ?></td>
                    <td><?= escape($student['email']) ?></td>
                    <td><?= escape($student['course']) ?></td>
                    <td>
                        <div class="actions">
                            <a href="edit.php?id=<?= escape($student['id']) ?>">Edit</a>
                            <a class="button delete" href="delete.php?id=<?= escape($student['id']) ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
