<?php
session_start();
include 'dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $advisorID = trim($_POST['Admin_ID']);
    $passwordInput = $_POST['Admin_PSW'];

    if (!empty($advisorID) && !empty($passwordInput)) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE Admin_ID = ?");
        $stmt->bind_param("s", $advisorID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($passwordInput, $user['Admin_PSW'])) {
                $_SESSION['Admin_ID'] = $user['Admin_ID'];

                header("Location: admin/dashboard.php");
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
    <title>Advisor Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <a href="index.php"><img src="NU logo.png" alt="University Logo" class="logo"></a>
        <h1>Admin Login</h1>
        <?php if (isset($error)) {
            echo "<p class='error visible'>$error</p>";
        }
        ?>
        <form action="AdminLogin.php" method="POST">
            <input type="text" name="Admin_ID" placeholder="Admin ID" required>
            <input type="password" name="Admin_PSW" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>