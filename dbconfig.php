<?php
// Database configuration
$host = "localhost";        // Host name
$user = "root";             // MySQL username
$password = "";             // MySQL password
$database = "cocuricular management system"; // Database name

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Display connection success message (for debugging)
// echo "Connected successfully!";
?>