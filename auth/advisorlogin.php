<?php
session_start();
include '../db/dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $advisorID = trim($_POST['Adv_ID']);
    $passwordInput = $_POST['Adv_PSW'];

    if (!empty($advisorID) && !empty($passwordInput)) {
        $stmt = $conn->prepare("SELECT * FROM Advisor WHERE Adv_ID = ?");
        $stmt->bind_param("s", $advisorID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($passwordInput, $user['Adv_PSW'])) {
                $_SESSION['Adv_ID'] = $user['Adv_ID'];
                $_SESSION['Club_ID'] = $user['Club_ID'];
                $_SESSION['user_type'] = 'advisor';
                header("Location: ../advisor/AdvisorDashboard.php");
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container">
        <a href="../index.php"><img src="../assets/img/NU logo.png" alt="University Logo" class="logo"></a>
        <h1>Advisor Login</h1>
        <?php if (isset($error)) {
            echo "<p class='error visible'>$error</p>";
        }
        ?>
        <form action="AdvisorLogin.php" method="POST">
            <input type="text" name="Adv_ID" placeholder="Advisor ID" required>
            <input type="password" name="Adv_PSW" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>