<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

// Ensure the student is logged in
if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}

// Fetch student ID from session
$stu_id = $_SESSION['Stu_ID'];

// Fetch events approved by the coordinator but without a submitted postmortem
$approved_events_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Date, c.Club_Name 
    FROM events e
    JOIN club c ON e.Club_ID = c.Club_ID
    JOIN eventcomment ec ON e.Ev_ID = ec.Ev_ID
    JOIN eventstatus es ON ec.Status_ID = es.Status_ID
    LEFT JOIN eventpostmortem ep 
        ON e.Ev_ID = ep.Ev_ID AND ep.Rep_PostStatus IN ('Pending Coordinator Review', 'Accepted')
    WHERE e.Stu_ID = '$stu_id' 
      AND es.Status_Name = 'Approved by Coordinator' 
      AND ep.Ev_ID IS NULL
";

$approved_events_result = $conn->query($approved_events_query);
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Postmortem Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        .table thead th {
            background-color: #54C392;
            border-color: rgb(0, 0, 0);
            text-align: center;

        }

        .table tbody td,
        .table tbody tr {
            background-color: #D2FF72;
            text-align: center;
            border-color: rgb(0, 0, 0);
            /* Light gray border */
        }


        .btn-create {
            background-color: #32CD32;
            color: white;
            border-radius: 8px;
            transition: transform 0.2s, background-color 0.3s;
        }

        .btn-create:hover {
            background-color: #15B392;
            transform: scale(1.05);
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
                <img src="NU logo.png" alt="System Logo"> Report List
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?php echo $_SESSION['Stu_Name']; ?>
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
        <h2 class="text-center mb-4">Report List</h2>
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Club Name</th>
                        <th>Event Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($approved_events_result->num_rows > 0): ?>
                        <?php while ($event = $approved_events_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $event['Ev_ID']; ?></td>
                                <td><?php echo $event['Ev_Name']; ?></td>
                                <td><?php echo $event['Club_Name']; ?></td>
                                <td><?php echo date('d M Y', strtotime($event['Ev_Date'])); ?></td>
                                <td>
                                    <a href="Postmortem.php?event_id=<?php echo $event['Ev_ID']; ?>" class="btn btn-create">
                                        Create Report
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No approved events available for postmortem
                                creation.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php
// End time after processing the page
$end_time = microtime(true);
$page_load_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds

echo "<p style='color: green; font-weight: bold; text-align: center;'>
      Page Load Time: " . $page_load_time . " ms
      </p>";
?>

</html>