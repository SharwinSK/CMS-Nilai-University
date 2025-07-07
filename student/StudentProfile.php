<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}
$stu_id = $_SESSION['Stu_ID'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Stu_Name'];
    $program = $_POST['Stu_Program'];
    $school = $_POST['Stu_School'];
    $new_password = $_POST['Stu_Password'];
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $_POST['current_password'];
    }
    $update_query = "
        UPDATE student
        SET Stu_Name = '$name', 
            Stu_Program = '$program', 
            Stu_School = '$school', 
            Stu_PSW = '$hashed_password'
        WHERE Stu_ID = '$stu_id'
    ";

    if ($conn->query($update_query)) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}
$query = "
    SELECT Stu_Name, Stu_ID, Stu_Program, Stu_School, Stu_PSW
    FROM student
    WHERE Stu_ID = '$stu_id'
";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    die("Student not found.");
}

$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STUDENT CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        .form-label {
            font-weight: bold;
            color: #6c63ff;
        }

        .form-control {
            border: 1px solid #6c63ff;
            border-radius: 8px;
        }

        .btn-update {
            background-color: #6c63ff;
            color: white;
            border-radius: 8px;
            padding: 10px;
            font-size: 16px;
        }

        .btn-update:hover {
            background-color: #574b90;
        }

        .card {
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="StudentDashboard.php">
                <img src="NU logo.png" alt="Logo">

            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $student['Stu_Name']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="StudentProfile.php">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul class="nav flex-column">
                <li class="nav-item"> <a class="nav-link active" href="StudentDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="StudentProfile.php">User Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="ProposalEvent.php">Create Proposal</a></li>
                <li class="nav-item"><a class="nav-link" href="PostmortemView.php">Create Postmortem</a></li>
                <li class="nav-item"><a class="nav-link" href="ProgressPage.php">Track Progress</a></li>
                <li class="nav-item"><a class="nav-link" href="EventHistory.php">Event History</a></li>
            </ul>
        </div>
    </div>

    <!-- Profile Details-->
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-center">Student Profile</h5>
                <p class="text-center text-muted">View and update your profile details</p>
                <form action="StudentProfile.php" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="Stu_Name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="Stu_Name" name="Stu_Name"
                                value="<?php echo $student['Stu_Name']; ?>" placeholder="Enter your name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Stu_ID" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="Stu_ID" name="Stu_ID"
                                value="<?php echo $student['Stu_ID']; ?>" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="Stu_Program" class="form-label">Program</label>
                            <input type="text" class="form-control" id="Stu_Program" name="Stu_Program"
                                value="<?php echo $student['Stu_Program']; ?>" placeholder="Enter your program">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Stu_School" class="form-label">School</label>
                            <input type="text" class="form-control" id="Stu_School" name="Stu_School"
                                value="<?php echo $student['Stu_School']; ?>" placeholder="Enter your school">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="Stu_Password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="Stu_Password" name="Stu_Password"
                            placeholder="Enter new password (leave blank to keep current)">
                        <input type="hidden" name="current_password" value="<?php echo $student['Stu_PSW']; ?>">
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // End time after processing the page
    $end_time = microtime(true);
    $page_load_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
    
    echo "<p style='color: green; font-weight: bold; text-align: center;'>
      Page Load Time: " . $page_load_time . " ms
      </p>";
    ?>
</body>

</html>