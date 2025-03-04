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

$carousel_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Details, e.Ev_Poster 
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID AND ep.Rep_PostStatus = 'Accepted'
    WHERE e.Ev_Status = 'Approved by Coordinator' AND ep.Rep_PostStatus IS NULL
";
$carousel_result = $conn->query($carousel_query);

// Total Events Completed
$events_completed_query = "
    SELECT COUNT(*) AS total_completed 
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    WHERE e.Stu_ID = '$stu_id' AND ep.Rep_PostStatus = 'Accepted'
";
$events_completed_result = $conn->query($events_completed_query);
$events_completed = $events_completed_result->fetch_assoc()['total_completed'];

// Proposals Pending
$proposals_pending_query = "
    SELECT COUNT(*) AS total_pending 
    FROM events 
    WHERE Stu_ID = '$stu_id' AND Ev_Status IN (
        'Pending Advisor Review',
        'Sent Back by Advisor',
        'Approved by Advisor',
        'Pending Coordinator Review'
    )
";
$proposals_pending_result = $conn->query($proposals_pending_query);
$proposals_pending = $proposals_pending_result->fetch_assoc()['total_pending'];

// Postmortems Pending
$postmortem_query = "
    SELECT 
        COALESCE(SUM(CASE WHEN Rep_PostStatus = 'Pending Coordinator Review' THEN 1 ELSE 0 END), 0) AS postmortems_pending
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    WHERE e.Stu_ID = '$stu_id'
";

$postmortem_result = $conn->query($postmortem_query);
$postmortem_data = $postmortem_result->fetch_assoc();
$postmortem_pending = $postmortem_data['postmortems_pending'] ?? 0; // Default to 0 if no data


// Completed Events
$completed_events_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Poster 
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE e.Stu_ID = '$stu_id' AND ep.Rep_PostStatus = 'Accepted'
";
$completed_events_result = $conn->query($completed_events_query);
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="StyleMain.css">

    <style>
        body {
            padding-top: 70px;
        }

        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #9BEC00;
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .fab:hover {
            background-color: #54C392;
        }

        .fab-menu {
            position: fixed;
            bottom: 90px;
            right: 20px;
            display: none;
        }

        .fab-menu a {
            display: block;
            margin-bottom: 10px;
        }

        .completed-events {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .completed-event-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }

        .completed-event-card:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .completed-event-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .completed-event-card .card-body {
            background-color: #9BEC00;
            padding: 10px;
        }

        .completed-event-card .btn:hover {
            background-color: #15B392;
        }

        .overview-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .bg-primary .card-body {
            background-color: #06D001;
            color: black;
        }

        .bg-warning .card-body {
            background-color: #06D001;
        }

        .bg-danger .card-body {
            background-color: #06D001;
            color: black;
        }

        .overview-card:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="StudentDashboard.php">
                <img src="NU logo.png" alt="Logo">
                Dashboard
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
                            data-bs-target="#logoutModal">Logout</a>
                    </li>
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
                <li class="nav-item"> <a class="nav-link active" href="StudentDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="StudentProfile.php">User Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="ProposalEvent.php">Create Proposal</a></li>
                <li class="nav-item"><a class="nav-link" href="PostmortemView.php">Create Postmortem</a></li>
                <li class="nav-item"><a class="nav-link" href="ProgressPage.php">Track Progress</a></li>
                <li class="nav-item"><a class="nav-link" href="EventHistory.php">Event History</a></li>
            </ul>
        </div>
    </div>
    <!-- Event Poster -->
    <div id="main-content">
        <div class="container mt-4">
            <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php if ($carousel_result->num_rows > 0): ?>
                        <?php $isActive = true; ?>
                        <?php while ($event = $carousel_result->fetch_assoc()): ?>
                            <div class="carousel-item <?php echo $isActive ? 'active' : ''; ?>">
                                <img src="<?php echo $event['Ev_Poster']; ?>" class="carousel-img">
                            </div>
                            <?php $isActive = false; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="carousel-item active">
                            <img src="PlaceHolder.png" class="carousel-img" alt="No Events">
                        </div>
                    <?php endif; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#eventCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#eventCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>

            <!-- Dashboard overview -->
            <div class="row mt-4">
                <div class="col-md-4  mb-3">
                    <div class="card bg-primary text-white overview-card">
                        <div class="card-body">
                            <h5>Total Events Completed</h5>
                            <h3><?php echo $events_completed; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4  mb-3">
                    <div class="card bg-warning text-dark overview-card">
                        <div class="card-body">
                            <h5>Total Proposals Submit Pending</h5>
                            <h3><?php echo $proposals_pending; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4  mb-3">
                    <div class=" card bg-danger text-white overview-card">
                        <div class="card-body">
                            <h5>Total Postmortems Pending</h5>
                            <h3><?php echo $postmortem_pending; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Floating button for Proposal and Postmortem -->
            <div class="fab" id="fab"> <i class="fas fa-plus"></i></div>
            <div class="fab-menu" id="fabMenu">
                <a href="ProposalEvent.php" class="btn btn-primary">Create Proposal</a>
                <a href="PostmortemView.php" class="btn btn-warning">Create Postmortem</a>
            </div>
            <!-- Display Complete event -->
            <h3 class="mt-5">Completed Events</h3>
            <div class="completed-events">
                <?php while ($event = $completed_events_result->fetch_assoc()): ?>
                    <div class="completed-event-card">
                        <img src="<?php echo $event['Ev_Poster']; ?>" alt="Event Poster">
                        <div class="card-body">
                            <h5><?php echo $event['Ev_Name']; ?></h5>
                            <a href="EventHistory.php" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- JavaScipts -->
    <script>
        const fab = document.getElementById('fab');
        const fabMenu = document.getElementById('fabMenu');
        fab.addEventListener('click', () => {
            fabMenu.style.display = fabMenu.style.display === 'block' ? 'none' : 'block';
        });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php
    $execution_time = microtime(true) - $start_time;
    echo "<!-- Page executed in " . round($execution_time, 4) . " seconds -->";
    ?>
</body>

</html>