<?php
// Change these values to match your local MySQL installation.
$servername = 'localhost';
$username = 'root';
$dbPassword = ''; // Keep this separate from the password submitted on the login form.
$dbname = 'php_learning';

try {
    // new mysqli() creates a connection object and selects the database.
    // Use this object's methods, such as $conn->query(), for database operations.
    $conn = new mysqli($servername, $username, $dbPassword, $dbname);

    // Use Unicode text, including emoji, in database queries.
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    // Modern PHP throws an exception if connection fails; catch it without exposing details.
    http_response_code(503);
    die('Database connection failed. Please check your database settings.');
}
