<?php
session_start();
include '../db/dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $coordinatorID = trim($_POST['Coor_ID']);
    $passwordInput = $_POST['Coor_PSW'];

    if (!empty($coordinatorID) && !empty($passwordInput)) {
        $stmt = $conn->prepare("SELECT * FROM Coordinator WHERE Coor_ID = ?");
        $stmt->bind_param("s", $coordinatorID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($passwordInput, $user['Coor_PSW'])) {
                $_SESSION['Coor_ID'] = $user['Coor_ID'];
                $_SESSION['user_type'] = 'coordinator';
                header("Location: CoordinatorDashboard.php");
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
    <title>Coordinator Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container">
        <a href="../index.php">
            <img src="../assets/img/NU logo.png" alt="University Logo" class="logo">
        </a>
        <h1>Coordinator Login</h1>
        <?php if (isset($error)) {
            echo "<p class='error visible'>$error</p>";
        }
        ?>
        <form action="CoordinatorLogin.php" method="POST">
            <input type="text" name="Coor_ID" placeholder="Coordinator ID" required>
            <input type="password" name="Coor_PSW" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>