<?php
session_start();
include('../db/dbconfig.php');

$currentPage = 'profile';

// Enhanced session validation
if (!isset($_SESSION['Stu_ID']) || empty($_SESSION['Stu_ID'])) {
    // Clear any existing session data
    session_unset();
    session_destroy();
    header("Location: StudentLogin.php?error=session_expired");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];

// Validate session integrity - check if student still exists in database
$session_check_query = "SELECT Stu_ID FROM student WHERE Stu_ID = ? LIMIT 1";
$session_stmt = $conn->prepare($session_check_query);
$session_stmt->bind_param("s", $stu_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();

if ($session_result->num_rows === 0) {
    // Student no longer exists, destroy session
    session_unset();
    session_destroy();
    header("Location: StudentLogin.php?error=invalid_session");
    exit();
}
$session_stmt->close();

// Fetch student details with prepared statement
$query = "SELECT Stu_Name, Stu_ID, Stu_Program, Stu_School, Stu_PSW, Stu_Email FROM student WHERE Stu_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $stu_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    die("Student not found.");
}
$stmt->close();

// Handle profile update with better security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Server-side validation (backup for client-side validation)
    $name = trim($_POST['Stu_Name']);
    $email = trim($_POST['Stu_Email']);
    $new_password = $_POST['Stu_Password'];

    $errors = [];

    // Validate name (only alphabetic and spaces)
    if (empty($name)) {
        $errors[] = "Name cannot be empty.";
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $name)) {
        $errors[] = "Name can only contain alphabetic characters and spaces.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long.";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $allowed_domains = ['gmail.com', 'students.nilai.edu.my'];
        $email_domain = substr(strrchr($email, "@"), 1);
        if (!in_array(strtolower($email_domain), $allowed_domains)) {
            $errors[] = "Email must be from gmail.com or students.nilai.edu.my domain.";
        }
    }

    // Validate password (only if provided)
    if (!empty($new_password)) {
        if (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = "Password must contain at least one number.";
        }
    }

    // Check if email already exists for other students
    $email_check_query = "SELECT Stu_ID FROM student WHERE Stu_Email = ? AND Stu_ID != ?";
    $email_stmt = $conn->prepare($email_check_query);
    $email_stmt->bind_param("ss", $email, $stu_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();

    if ($email_result->num_rows > 0) {
        $errors[] = "This email address is already in use by another student.";
    }
    $email_stmt->close();

    if (empty($errors)) {
        // Prepare password (hash new one or keep current)
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            $hashed_password = $_POST['current_password']; // Keep current password
        }

        // Update with prepared statement
        $update_query = "UPDATE student SET Stu_Name = ?, Stu_Email = ?, Stu_PSW = ? WHERE Stu_ID = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssss", $name, $email, $hashed_password, $stu_id);

        if ($update_stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $showSuccess = true;

            // Update session data
            $_SESSION['Stu_Name'] = $name;
            $_SESSION['Stu_Email'] = $email;

            // Log the update (optional)
            error_log("Profile updated for student ID: " . $stu_id);

        } else {
            $error_message = "Failed to update profile. Please try again.";
            $showError = true;
            error_log("Profile update failed for student ID: " . $stu_id . " - " . $conn->error);
        }
        $update_stmt->close();

        // Refresh updated data after successful update
        if (isset($showSuccess) && $showSuccess) {
            $query = "SELECT Stu_Name, Stu_ID, Stu_Program, Stu_School, Stu_PSW, Stu_Email FROM student WHERE Stu_ID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $stu_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            $stmt->close();
        }

    } else {
        // Server-side validation errors
        $error_message = implode("<br>", $errors);
        $showError = true;
    }
}

// Generate CSRF token for form security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/student.css?v=<?= time() ?>" rel="stylesheet" />
    <style>
        /* Enhanced validation styles */
        .is-valid {
            border-color: #28a745 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.8-.77-.7-.77.3-.3 1 .77 2.3-2.3.3.3-2.6 2.6z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .is-invalid {
            border-color: #dc3545 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4M8.2 4.6l-2.4 2.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .validation-error {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .session-status {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1050;
            padding: 0.5rem 1rem;
            background: var(--primary-light);
            border-radius: 5px;
            font-size: 0.875rem;
            color: var(--primary-purple);
        }
    </style>
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/header.php'); ?>
    <?php include('../components/offcanvas.php'); ?>


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
                html: '<?= addslashes($error_message) ?>',
                confirmButtonColor: '#ff4f0f'
            });
        </script>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Title -->
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fa-solid fa-user"></i>
                    Student Profile
                </h2>
                <small class="text-muted">Session ID: <?= htmlspecialchars($stu_id) ?></small>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h2 id="profileName"><?= htmlspecialchars($student['Stu_Name']) ?></h2>
                    <p class="mb-0">Student</p>
                </div>

                <div class="profile-info">
                    <form id="profileForm" method="POST">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Full Name *</label>
                                    <input type="text" class="info-value" id="studentName" name="Stu_Name"
                                        value="<?= htmlspecialchars($student['Stu_Name']) ?>" disabled required />
                                    <small class="form-text text-muted">Only alphabetic characters </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Student ID</label>
                                    <input type="text" class="info-value" id="studentId" name="Stu_ID"
                                        value="<?= htmlspecialchars($student['Stu_ID']) ?>" disabled readonly />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">School</label>
                                    <input type="text" class="info-value" id="school" name="Stu_School"
                                        value="<?= htmlspecialchars($student['Stu_School']) ?>" disabled readonly />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Program</label>
                                    <input type="text" class="info-value" id="program" name="Stu_Program"
                                        value="<?= htmlspecialchars($student['Stu_Program']) ?>" disabled readonly />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Email Address *</label>
                                    <input type="email" class="info-value" id="email" name="Stu_Email"
                                        value="<?= htmlspecialchars($student['Stu_Email']) ?>" disabled required />
                                    <small class="form-text text-muted">Must be @gmail.com or
                                        @students.nilai.edu.my</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group">
                                    <label class="info-label">Password</label>
                                    <div class="password-toggle">
                                        <input type="password" class="info-value" id="password" name="Stu_Password"
                                            value="" disabled autocomplete="new-password" />
                                        <input type="hidden" name="current_password"
                                            value="<?= htmlspecialchars($student['Stu_PSW']) ?>" />
                                        <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Min 8 chars, 1 uppercase, 1 number. Leave empty
                                        to keep current.</small>
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