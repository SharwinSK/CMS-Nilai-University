<?php
session_start();
include('../db/dbconfig.php');

$currentPage = 'profile';
if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}


$stu_id = $_SESSION['Stu_ID'];

// ✅ FIRST: Fetch student details
$query = "SELECT Stu_Name, Stu_ID, Stu_Program, Stu_School, Stu_PSW, Stu_Email FROM student WHERE Stu_ID = '$stu_id'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    die("Student not found.");
}

// ✅ THEN: Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Stu_Name'];
    $email = $_POST['Stu_Email'];
    $new_password = $_POST['Stu_Password'];

    // ✅ KEEP existing program/school values from DB
    $program = $student['Stu_Program'];
    $school = $student['Stu_School'];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $_POST['current_password'];
    }

    $update_query = "
        UPDATE student
        SET Stu_Name = '$name',
            Stu_Email = '$email',
            Stu_PSW = '$hashed_password'
        WHERE Stu_ID = '$stu_id'
    ";

    if ($conn->query($update_query)) {
        $success_message = "Profile updated successfully!";
        $showSuccess = true; // ✅ Add this flag
        $_SESSION['Stu_Name'] = $name;
        $_SESSION['Stu_Email'] = $email;

    } else {
        $error_message = "Failed to update profile. Please try again.";
        $showError = true; // ✅ error flag
    }

    // ✅ REFRESH updated data after update
    $result = $conn->query($query);
    $student = $result->fetch_assoc();
}


?>
<?php include('../components/header.php'); ?>
<?php include('../components/offcanvas.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Profile - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/student.css?v=<?= time() ?>" rel="stylesheet" />
    <style>

    </style>
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>

    <!-- Show success message if set -->
    <?php if (isset($showSuccess) && $showSuccess): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Profile Updated!',
                text: 'Your changes have been saved successfully.',
                confirmButtonColor: '#ac73ff'
            });
        </script>
    <?php endif; ?>
    <!-- Show error message if set -->
    <?php if (isset($showError) && $showError): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: 'Something went wrong while saving your profile. Please try again.',
                confirmButtonColor: '#ff4f0f'
            });
        </script>
    <?php endif; ?>


    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h1 class="page-title">
                <i class="fas fa-user me-2"></i>
                Student Profile
            </h1>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h2 id="profileName"><?= $student['Stu_Name'] ?></h2>
                    <p class="mb-0">Student</p>
                </div>

                <div class="profile-info">
                    <form id="profileForm" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Full Name</label>
                                    <input type="text" class="info-value" id="studentName" name="Stu_Name"
                                        value="<?= $student['Stu_Name'] ?>" disabled />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Student ID</label>
                                    <input type="text" class="info-value" id="studentId" name="Stu_ID"
                                        value="<?= $student['Stu_ID'] ?>" disabled />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">School</label>
                                    <input type="text" class="info-value" id="school" name="Stu_School"
                                        value="<?= $student['Stu_School'] ?>" disabled />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Program</label>
                                    <input type="text" class="info-value" id="program" name="Stu_Program"
                                        value="<?= $student['Stu_Program'] ?>" disabled />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Email Address</label>
                                    <input type="email" class="info-value" id="email" name="Stu_Email"
                                        value="<?= $student['Stu_Email'] ?>" disabled />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Password</label>
                                    <div class="password-toggle">
                                        <input type="password" class="info-value" id="password" name="Stu_Password"
                                            value="" disabled />
                                        <input type="hidden" name="current_password"
                                            value="<?= $student['Stu_PSW'] ?>" />
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Alert Messages -->
                        <div id="alertContainer"></div>
                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-primary me-2" id="editBtn" onclick="toggleEdit()">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </button>
                            <button type="button" class="btn btn-secondary me-2" id="cancelBtn" onclick="cancelEdit()"
                                style="display: none">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="saveBtn" style="display: none">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/studentjs/stuprofile.js?v=<?= time(); ?>"></script>
</body>

</html>