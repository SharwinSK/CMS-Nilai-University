<?php
include 'dbconfig.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['Stu_Name'];
        $program = $_POST['Stu_Program'];
        $school = $_POST['Stu_School'];
        $studentID = $_POST['Stu_ID'];
        $passwordInput = $_POST['Stu_PSW'];

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
            echo "<script>alert('Student ID already registered! Please use a different ID or Login .'); window.history.back();</script>";
        } else {
            // Hash the password
            $hashedPassword = password_hash($passwordInput, PASSWORD_BCRYPT);

            // Insert the new student record
            $stmt = $conn->prepare("INSERT INTO student (Stu_Name, Stu_Program, Stu_School, Stu_ID, Stu_PSW) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $program, $school, $studentID, $hashedPassword);

            if ($stmt->execute()) {
                echo "<script>alert('Registration successful! You can now login.'); window.location.href='StudentLogin.php';</script>";
            } else {
                echo "<script>alert('Registration failed! Please try again.'); window.history.back();</script>";
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
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <style>
        .tooltip-inner {
            max-width: 200px;
            text-align: left;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <a href="Index.php"><img src="NU logo.png" alt="University Logo" class="logo mb-3"></a>
        <h1 class="mb-4">Student Registration</h1>
        <form action="Register.php" method="POST">

            <div class="mb-3">
                <input type="text" name="Stu_Name" class="form-control" placeholder="Student Name" required>
            </div>

            <div class="mb-3">
                <select name="Stu_School" class="form-select" required>
                    <option value="" disabled selected>Select School</option>
                    <option value="School of Computing">School of Computing</option>
                    <option value="School of Accounting and Finance">School of Accounting and Finance</option>
                    <option value="School of Aircraft Maintenance">School of Aircraft Maintenance</option>
                    <option value="School of Applied Science">School of Applied Science</option>
                    <option value="School of Foundation Studies">School of Foundation Studies</option>
                    <option value="School of Hospitality and Tourism">School of Hospitality and Tourism</option>
                    <option value="School of Humanities and Social Science">School of Humanities and Social Science
                    </option>
                    <option value="School of Management and Marketing">School of Management and Marketing</option>
                    <option value="School of Nursing">School of Nursing</option>
                    <option value="Graduate School of Business">Graduate School of Business</option>
                </select>
            </div>

            <div class="mb-3">
                <select name="Stu_Program" class="form-select" required>
                    <option value="" disabled selected>Select Program</option>
                    <option value="Foundation in Business">Foundation in Business</option>
                    <option value="Foundation in Science">Foundation in Science</option>
                    <option value="DCS">Diploma in Computer Science</option>
                    <option value="DIA">Diploma in Accounting</option>
                    <option value="DAME">Diploma in Aircraft Maintenance Engineering</option>
                    <option value="DIT">Diploma in Information Technology</option>
                    <option value="DHM">Diploma in Hotel Management</option>
                    <option value="DCA">Diploma in Culinary Arts</option>
                    <option value="DBA">Diploma in Business Adminstration</option>
                    <option value="DIN">Diploma in Nursing</option>
                    <option value="BOF">Bachelor of Finance</option>
                    <option value="BAAF">Bachelor of Arts in Accounting & Finance</option>
                    <option value="BBAF">Bachelor of Business Adminstration in Finance</option>
                    <option value="BSB">Bachelor of Science Biotechonology</option>
                    <option value="BCSAI">Bachelor of Computer Science Artificial intelligence</option>
                    <option value="BITC">Bachelor of Information Technology Cybersecurity</option>
                    <option value="BSE">Bachelor of Software Engineering</option>
                    <option value="BCSDS">Bachelor of Computer Science Data Science</option>
                    <option value="BIT">Bachelor of Information Technology</option>
                    <option value="BITIECC">Bachelor of Information Technology Internet Engineering and Cloud Computing
                    </option>
                    <option value="BEM">Bachelor of Event Management</option>
                    <option value="BHMBM">Bachelor of Hospitality Management with Business management</option>
                    <option value="BBAGL">Bachelor of Business Adminstration in Global Logistic</option>
                    <option value="BBADM">Bachelor of Business Adminstration in Digital Marketing</option>
                    <option value="BBAM">Bachelor of Business Adminstration in Marketing</option>
                    <option value="BBAMT">Bachelor of Business Adminstration in Management</option>
                    <option value="BBAIB">Bachelor of Business Adminstration in International Business</option>
                    <option value="BBAHRM">Bachelor of Business Adminstration in Human Resource Management</option>
                    <option value="BBA">Bachelor of Business Adminstration</option>
                    <option value="BSN">Bachelor of Science in Nursing</option>
                </select>
            </div>

            <div class="mb-3">
                <input type="text" name="Stu_ID" class="form-control" placeholder="Student ID" required>
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

    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                html: true
            });
        });

        document.getElementById('password').addEventListener('input', function () {
            const tooltip = bootstrap.Tooltip.getInstance(this);
            const password = this.value;

            const requirements = [
                /.{8,}/.test(password) ? '✔ At least 8 characters' : '✘ At least 8 characters',
                /[A-Z]/.test(password) ? '✔ At least one uppercase letter' : '✘ At least one uppercase letter',
                /\d/.test(password) ? '✔ At least one number' : '✘ At least one number',
            ];

            tooltip.setContent({
                '.tooltip-inner': requirements.join('<br>')
            });
        });

    </script>
</body>

</html>