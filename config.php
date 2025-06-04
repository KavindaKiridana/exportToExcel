<?php
// Database configuration file
// This file contains database connection settings

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3307'); // Uncomment if using a specific port

// Create database connection using MySQLi
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function to sanitize input data to prevent SQL injection
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate password requirements
function validatePassword($password) {
    // Password must be at least 6 characters long
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long.";
    }
    
    // Password must contain at least one letter
    if (!preg_match("/[a-zA-Z]/", $password)) {
        return "Password must contain at least one letter.";
    }
    
    // Password must contain at least one number
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least one number.";
    }
    
    return true; // Password is valid
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>