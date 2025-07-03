<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$query = $conn->prepare("SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?");
$query->bind_param("s", $coordinator_id);
$query->execute();
$result = $query->get_result();
$coordinator_name = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['Coor_Name'] : "Coordinator";

$carousel_query = "
SELECT e.Ev_ID, e.Ev_Name, e.Ev_Details, e.Ev_Poster 
FROM events e
JOIN eventstatus es ON e.Status_ID = es.Status_ID
LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID 
    AND ep.Status_ID = (SELECT Status_ID FROM eventstatus WHERE Status_Name = 'Postmortem Approved')
WHERE es.Status_Name = 'Approved by Coordinator' 
  AND ep.Ev_ID IS NULL


";
$carousel_result = $conn->query($carousel_query);

$proposals_query = "
    SELECT e.Ev_ID, e.Ev_Name, s.Stu_Name
FROM events e
JOIN student s ON e.Stu_ID = s.Stu_ID
JOIN eventstatus es ON e.Status_ID = es.Status_ID
WHERE es.Status_Name = 'Approved by Advisor (Pending Coordinator Review)'
";

$proposals_result = $conn->query($proposals_query);

$postmortems_query = "
    SELECT ep.Rep_ID, e.Ev_Name, s.Stu_Name 
FROM eventpostmortem ep
JOIN events e ON ep.Ev_ID = e.Ev_ID
JOIN student s ON e.Stu_ID = s.Stu_ID
JOIN eventstatus es ON ep.Status_ID = es.Status_ID
WHERE es.Status_Name = 'Postmortem Pending Review'

";
$postmortems_result = $conn->query($postmortems_query);
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COORDINATOR CMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styleMain.css">
    <style>
        body {
            padding-top: 70px;
        }


        .card-header {
            background-color: #15B392;
            /* Green header */
            color: black;
            font-weight: bold;
        }

        .card-body {
            padding: 20px;
        }



        .table-bordered th,
        .table-bordered td,
        .table-bordered tr {
            background-color: #D2FF72;
            border-color: black;
            /* Light gray border */
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }

        .table-bordered th {
            background-color: #28a745;
            /* Green header */
            color: white;
            font-weight: bold;
        }


        /* Buttons */
        .btn-primary {
            background-color: #32CD32;
            /* Blue */
            color: white;
            border-radius: 5px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-primary:hover {
            background-color: #15B392;
            /* Darker blue */
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .graph-container {
            border: 1px solid #ccc;
            /* Light gray border */
            border-radius: 8px;
            /* Rounded corners */
            padding: 15px;
            /* Add padding around the graph */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
            background-color: #fff;
            /* White background */
        }

        canvas {
            width: 100%;
            /* Make canvas responsive */
            max-width: 400px;
            /* Limit the graph width */
            height: 250px;
            /* Set fixed height */
            margin: 0 auto;
            /* Center the canvas */
        }

        h5 {
            font-size: 1rem;
            /* Smaller heading size */
            text-align: center;
            /* Center align titles */
            margin-bottom: 10px;
            /* Space between title and graph */
            color: #333;
            /* Darker text color for contrast */
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="CoordinatorDashboard.php">
                <img src="NU logo.png" alt="Logo">
                COORDINATOR CMS
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $coordinator_name; ?>
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


    <!-- Carousel Section -->
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

            <!-- Proposals Section -->
            <div class="card mt-4">
                <div class="card-header">Proposals to Review</div>
                <div class="card-body">
                    <?php if ($proposals_result->num_rows > 0): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Student Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($proposal = $proposals_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($proposal['Ev_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($proposal['Stu_Name']); ?></td>
                                        <td><a href="CoordinatorDecision.php?type=proposal&id=<?php echo $proposal['Ev_ID']; ?>"
                                                class="btn btn-primary">Review</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No proposals pending review.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Postmortems Section -->
            <div class="card mt-4">
                <div class="card-header">Postmortems to Review</div>
                <div class="card-body">
                    <?php if ($postmortems_result->num_rows > 0): ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Student Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($postmortem = $postmortems_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($postmortem['Ev_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($postmortem['Stu_Name']); ?></td>
                                        <td><a href="CoordinatorDecision.php?type=postmortem&id=<?php echo $postmortem['Rep_ID']; ?>"
                                                class="btn btn-primary">Review</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No postmortems pending review.</p>
                    <?php endif; ?>
                </div>
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