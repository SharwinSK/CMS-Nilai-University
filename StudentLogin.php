<?php
session_start();
include 'dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $studentID = trim($_POST['Stu_ID']);
    $passwordInput = $_POST['Stu_PSW'];

    if (!empty($studentID) && !empty($passwordInput)) {
        $stmt = $conn->prepare("SELECT * FROM Student WHERE Stu_ID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($passwordInput, $user['Stu_PSW'])) {
                $_SESSION['Stu_ID'] = $user['Stu_ID'];
                $_SESSION['Stu_Name'] = $user['Stu_Name'];
                $_SESSION['user_type'] = 'student';
                header("Location: StudentDashboard.php");
                exit();
            }
        }
        $error = "Invalid ID or Password. Please try again.";
        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Student Login</title>
</head>

<body>
    <div class="container">
        <a href="index.php">
            <img src="NU logo.png" alt="University Logo" class="logo">
        </a>
        <h1>Student Login</h1>
        <?php if (isset($error)) {
            echo "<p class='error visible'>$error</p>";
        }
        ?>
        <form action="StudentLogin.php" method="POST">
            <input type="text" name="Stu_ID" placeholder="Student ID" required>
            <input type="password" name="Stu_PSW" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="Register.php">Register Here</a></p>
    </div>
</body>

</html>