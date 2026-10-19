-- Run this file once in phpMyAdmin or the MySQL command line.
CREATE DATABASE IF NOT EXISTS php_learning
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE php_learning;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- A UNIQUE constraint also prevents duplicate emails during simultaneous requests.
    UNIQUE KEY unique_user_email (email)
) ENGINE=InnoDB;
