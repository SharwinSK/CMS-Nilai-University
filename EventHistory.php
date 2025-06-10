<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];
$student_name = $_SESSION['Stu_Name'];

// Get filters from GET (if any)
$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_club = $_GET['club'] ?? '';


$completed_events_query = "
    SELECT e.Ev_ID, e.Ev_Name, ep.Rep_RefNum, ep.Rep_PostStatus, e.Ev_Date, c.Club_Name
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    WHERE e.Stu_ID = '$stu_id' AND ep.Rep_PostStatus = 'Accepted'
";


if (!empty($filter_year)) {
    $completed_events_query .= " AND YEAR(e.Ev_Date) = '$filter_year'";
}
if (!empty($filter_month)) {
    $completed_events_query .= " AND MONTH(e.Ev_Date) = '$filter_month'";
}
if (!empty($filter_club)) {
    $completed_events_query .= " AND c.Club_Name = '$filter_club'";
}
if (!empty($filter_type)) {
    $completed_events_query .= " AND e.Ev_Type = ?";

}

$completed_events_result = $conn->query($completed_events_query);
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
        .table thead th {
            background-color: #54C392;
            text-align: center;
        }

        .table tbody td,
        .table tbody tr {
            background-color: #D2FF72;
            border-color: rgb(0, 0, 0);
            text-align: center;
        }

        .table-responsive {
            margin-bottom: 30px;
        }

        .btn-export {
            background-color: #32CD32;
            color: white;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-export:hover {
            transform: scale(1.05);
            background-color: #15B392;
        }

        .empty-message {
            text-align: center;
            font-style: italic;
            color: #6c757d;
        }

        /* Export Button with Icon */
        .fas {
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
            <a class="navbar-brand" href="StudentDashboard.php">
                <img src="NU logo.png" alt="Logo">
                History
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $student_name; ?>
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


    <!-- Main Content -->
    <div class="container mt-4">
        <h1 class="text-center">Event History</h1>

        <?php include('FilteringModal.php'); ?>




        <?php if ($completed_events_result->num_rows > 0): ?>
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Club Name</th>
                        <th>Date</th>
                        <th>Reference Number</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $completed_events_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $event['Ev_ID'] ?></td>
                            <td><?= $event['Ev_Name'] ?></td>
                            <td><?= $event['Club_Name'] ?></td>
                            <td><?= date('d M Y', strtotime($event['Ev_Date'])) ?></td>
                            <td><?= $event['Rep_RefNum'] ?></td>
                            <td>
                                <a href="Exportpdf.php?event_id=<?= $event['Ev_ID'] ?>" class="btn btn-export">
                                    <i class="fas fa-file-pdf"></i>Export to PDF
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    <?php else: ?>
        <p class="empty-message">No completed events found.</p>
    <?php endif; ?>
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