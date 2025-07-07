<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

$adv_id = $_SESSION['Adv_ID'];
$query = "SELECT Adv_Name FROM advisor WHERE Adv_ID = '$adv_id'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $Advisor_name = $result->fetch_assoc();
} else {
    $Advisor_name['Adv_Name'] = "Unknown Advisor";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Adv_Name'];
    $email = $_POST['Adv_Email'];

    $update_query = "
        UPDATE advisor
        SET Adv_Name = '$name', 
            Adv_Email = '$email'
        WHERE Adv_ID = '$adv_id'
    ";

    if ($conn->query($update_query)) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}

$query = "
    SELECT Adv_Name, Adv_Email, Club_ID
    FROM advisor
    WHERE Adv_ID = '$adv_id'
";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $advisor = $result->fetch_assoc();

    $club_id = $advisor['Club_ID'];
    $club_query = "SELECT Club_Name FROM club WHERE Club_ID = '$club_id'";
    $club_result = $conn->query($club_query);

    if ($club_result->num_rows > 0) {
        $club_name = $club_result->fetch_assoc()['Club_Name'];
    } else {
        $club_name = "Unknown Club";
    }
} else {
    die("Advisor not found.");
}
$start_time = microtime(true);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        .profile-card {
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
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

    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="AdvisorDashboard.php">
                <img src="NU logo.png" alt="Logo">
                Profile
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $Advisor_name['Adv_Name']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="AdvisorProfile.php">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <!--Side Navigation-->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul class="nav flex-column">
                <li class="nav-item"> <a class="nav-link active" href="AdvisorDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="AdvisorProfile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="AdvisorProgressView.php">Event Progress</a></li>
                <li class="nav-item"><a class="nav-link" href="AdvisorEvHistory.php">Event History</a></li>

            </ul>

        </div>
    </div>  
    <AdvisorProfile.php>
        ,<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=
            F, initial-scale=1.0">
            <title>CMS GA Profile`hh hooi shtg jjowm,muj</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">`
            <meterialize.css" href="styleMain.css">`
        </head>
        <body>
            
        </body>
        </html>
    <!-- Main Content Area -->
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-center">Profile</h5>
                <p class="text-center text-muted">View and update your profile details</p>
                <form action="AdvisorProfile.php" method="post">
                    <div class="row">
                        <!-- Name -->
                        <div class="col-md-6 mb-3">
                            <label for="Adv_Name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="Adv_Name" name="Adv_Name"
                                value="<?php echo $advisor['Adv_Name']; ?>" placeholder="Enter your name" required>
                        </div>
                        <!-- Advisor ID (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label for="Adv_ID" class="form-label">Advisor ID</label>
                            <input type="text" class="form-control" id="Adv_ID" name="Adv_ID"
                                value="<?php echo $adv_id; ?>" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Club Name (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label for="Club_Name" class="form-label">Club</label>
                            <input type="text" class="form-control" id="Club_Name" name="Club_Name"
                                value="<?php echo $club_name; ?>" readonly>
                        </div>
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="Adv_Email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="Adv_Email" name="Adv_Email"
                                value="<?php echo $advisor['Adv_Email']; ?>" placeholder="Enter your email" required>
                        </div>
                    </div>
                    <!-- Update Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
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