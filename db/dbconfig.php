<?php
// Database configuration
$host = "localhost";        // Host name
$user = "root";             // MySQL username
$password = "";             // MySQL password
$database = "cms nilai university"; // Database name

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>