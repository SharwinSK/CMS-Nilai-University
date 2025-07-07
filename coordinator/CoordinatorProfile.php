<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$query = $conn->prepare("SELECT Coor_Name, Coor_Email, Coor_PhnNum FROM coordinator WHERE Coor_ID = ?");
$query->bind_param("s", $coordinator_id);
$query->execute();
$result = $query->get_result();

if ($result && $result->num_rows > 0) {
    $coordinator = $result->fetch_assoc();
} else {
    die("Coordinator not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Coor_Name'];
    $email = $_POST['Coor_Email'];
    $phone = $_POST['Coor_Phone'];

    $update_query = $conn->prepare("
        UPDATE coordinator 
        SET Coor_Name = ?, Coor_Email = ?, Coor_PhnNum = ? 
        WHERE Coor_ID = ?
    ");
    $update_query->bind_param("ssss", $name, $email, $phone, $coordinator_id);
    if ($update_query->execute()) {
        $success_message = "Profile updated successfully!";

        $query = $conn->prepare("SELECT Coor_Name, Coor_Email, Coor_PhnNum FROM coordinator WHERE Coor_ID = ?");
        $query->bind_param("s", $coordinator_id);
        $query->execute();
        $result = $query->get_result();
        $coordinator = $result->fetch_assoc();
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Profile</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        body {
            padding: 70px;
        }

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
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="CoordinatorDashboard.php">
                <img src="NU logo.png" alt="Logo">
                Profile
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $coordinator['Coor_Name']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="CoordinatorProfile.php">Profile</a></li>
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
                <li class="nav-item"><a class="nav-link active" href="CoordinatorDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorProfile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorView.php">Proposals & Postmortems</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorProgressView.php">Event Ongoing</a></li>
                <li class="nav-item"><a class="nav-link" href="CoordinatorEventHistory.php">Event History</a></li>

            </ul>
        </div>
    </div>
    <!-- Main Content Area -->
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-center">Coordinator Profile</h5>
                <p class="text-center text-muted">View and update your profile details</p>
                <form action="CoordinatorProfile.php" method="post">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="Coor_Name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="Coor_Name" name="Coor_Name"
                                value="<?php echo $coordinator['Coor_Name']; ?>" placeholder="Enter your name" required>
                        </div>
                        <!-- Coordinator ID (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label for="Coor_ID" class="form-label">Coordinator ID</label>
                            <input type="text" class="form-control" id="Coor_ID" name="Coor_ID"
                                value="<?php echo $coordinator_id; ?>" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="Coor_Email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="Coor_Email" name="Coor_Email"
                                value="<?php echo $coordinator['Coor_Email']; ?>" placeholder="Enter your email"
                                required>
                        </div>
                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <label for="Coor_Phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="Coor_Phone" name="Coor_Phone"
                                value="<?php echo $coordinator['Coor_PhnNum']; ?>" placeholder="Enter your phone number"
                                required>
                        </div>
                    </div>
                    <!-- Update Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-update">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
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