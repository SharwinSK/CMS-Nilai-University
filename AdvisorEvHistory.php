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

$club_id = $_SESSION['Club_ID'];

$completed_events_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Objectives, s.Stu_Name, ep.Rep_RefNum 
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    JOIN student s ON e.Stu_ID = s.Stu_ID
    WHERE ep.Rep_PostStatus = 'Accepted' AND e.Club_ID = ?
    ORDER BY ep.Rep_RefNum ASC
";
$stmt = $conn->prepare($completed_events_query);
$stmt->bind_param('i', $club_id);
$stmt->execute();
$result = $stmt->get_result();
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        .table th {
            background-color: #54C392;
            color: white;
            text-align: center;
        }

        .table td,
        .table tr {
            background-color: #D2FF72;
            border-color: rgb(0, 0, 0);
            text-align: center;

        }

        .btn-primary {
            background-color: #32CD32;
            color: white;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            background-color: #15B392;
            transform: scale(1.05);
        }

        .btn-primary .fas {
            margin-right: 5px;
            font-size: 14px;
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
            <a class="navbar-brand" href="AdvisorDashboard.php">
                <img src="NU logo.png" alt="Logo">
                History
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


    <!-- Main Content -->
    <div class="container">
        <h1 class="text-center">Completed Events</h1>
        <div class="table-responsive mt-4">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Student Name</th>
                        <th>Objectives</th>
                        <th>Reference Number</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Ev_ID']); ?></td>
                                <td><?php echo htmlspecialchars($row['Ev_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Stu_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Ev_Objectives']); ?></td>
                                <td><?php echo htmlspecialchars($row['Rep_RefNum']); ?></td>
                                <td>
                                    <a href="Exportpdf.php?event_id=<?php echo urlencode($row['Ev_ID']); ?>"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-file-pdf"></i> Export PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No completed events found for your club.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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