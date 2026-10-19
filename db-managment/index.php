<?php
require_once __DIR__ . '/session.php';
// Send users to the correct starting page based on their login session.
header('Location: ' . (isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
exit;
