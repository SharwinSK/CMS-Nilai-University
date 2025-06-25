<?php
session_start();
include 'dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $adminID = trim($_POST['Admin_ID']);
    $passwordInput = $_POST['Admin_PSW'];

    if ($adminID !== '' && $passwordInput !== '') {
        /* — fetch admin by numeric ID — */
        $stmt = $conn->prepare("SELECT * FROM admin WHERE Admin_ID = ?");
        $stmt->bind_param("i", $adminID);   // INT, not string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($passwordInput, $user['Admin_PSW'])) {

                // ✅ good login
                $_SESSION['Admin_ID'] = $user['Admin_ID'];
                $_SESSION['user_type'] = 'admin';

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
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <a href="index.php"><img src="NU logo.png" alt="University Logo" class="logo"></a>
        <h1>Admin Login</h1>

        <?php if (isset($error)): ?>
            <p class="error visible"><?= $error ?></p>
        <?php endif; ?>

        <!-- keep file-name case consistent -->
        <form action="adminlogin.php" method="POST">
            <input type="text" name="Admin_ID" placeholder="Admin ID" required>
            <input type="password" name="Admin_PSW" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>