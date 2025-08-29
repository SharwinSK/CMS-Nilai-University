<?php
include '../db/dbconfig.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['Stu_Name'];
        $program = $_POST['Stu_Program'];
        $school = $_POST['Stu_School'];
        $studentID = $_POST['Stu_ID'];
        $passwordInput = $_POST['Stu_PSW'];
        $email = $_POST['Stu_Email'];


        // Password validation
        if (
            !preg_match('/.{8,}/', $passwordInput) || // At least 8 characters
            !preg_match('/[A-Z]/', $passwordInput) || // At least one uppercase letter
            !preg_match('/\d/', $passwordInput)      // At least one number
        ) {
            echo "<script>alert('Password does not meet the requirements. Please include at least 8 characters, one uppercase letter, and one number.'); window.history.back();</script>";
            exit;
        }

        // Check for duplicate student ID
        $stmt = $conn->prepare("SELECT Stu_ID FROM student WHERE Stu_ID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>alert('Student ID already registered! Please login instead.'); window.location.href='StudentLogin.php';</script>";
            exit;
        } else {
            // Hash the password
            $hashedPassword = password_hash($passwordInput, PASSWORD_BCRYPT);

            // Insert the new student record
            $stmt = $conn->prepare("INSERT INTO student (Stu_Name, Stu_Program, Stu_School, Stu_ID, Stu_Email, Stu_PSW)
                        VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $program, $school, $studentID, $email, $hashedPassword);

            if ($stmt->execute()) {
                echo "<script>alert('Registration successful! You can now login.'); window.location.href='StudentLogin.php';</script>";
            } else {
                echo "<script>alert('Registration failed! Please try again.'); window.location.reload();</script>";
            }
        }

        $stmt->close();
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="../assets/js/register.js" defer></script>
</head>

<body>
    <div class="container mt-5">
        <a href="../Index.php"><img src="../assets/img/NU logo.png" alt="University Logo" class="logo mb-3"></a>
        <h1 class="mb-4">Student Registration</h1>
        <form action="Register.php" method="POST">

            <div class="mb-3">
                <input type="text" name="Stu_Name" class="form-control" placeholder="Student Name" required>
            </div>

            <div class="mb-3">
                <select name="Stu_School" id="schoolSelect" class="form-select" required onchange="updatePrograms()">
                    <option value="" disabled selected>Select School</option>
                    <option value="School of Computing">School of Computing</option>
                    <option value="School of Accounting and Finance">School of Accounting and Finance</option>
                    <option value="School of Aircraft Maintenance">School of Aircraft Maintenance</option>
                    <option value="School of Applied Science">School of Applied Science</option>
                    <option value="School of Foundation Studies">School of Foundation Studies</option>
                    <option value="School of Hospitality and Tourism">School of Hospitality and Tourism</option>
                    <option value="School of Management and Marketing">School of Management and Marketing</option>
                    <option value="School of Nursing">School of Nursing</option>
                </select>
            </div>

            <div class="mb-3">
                <select name="Stu_Program" id="programSelect" class="form-select" required>
                    <option value="" disabled selected>Select Program</option>
                </select>
            </div>


            <div class="mb-3">
                <input type="text" name="Stu_ID" class="form-control" placeholder="Student ID" required>
            </div>

            <div class="mb-3">
                <input type="email" name="Stu_Email" class="form-control" placeholder="Email Address" required>
            </div>


            <div class="mb-3 position-relative">
                <input type="password" name="Stu_PSW" class="form-control" id="password" placeholder="Password" required
                    data-bs-toggle="tooltip" data-bs-placement="right" title="Password must contain: 
                             • At least 8 characters 
                             • At least one uppercase letter 
                             • At least one number">
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p class="mt-3">Already have an account? <a href="StudentLogin.php">Login Here</a></p>
    </div>

</body>

</html>