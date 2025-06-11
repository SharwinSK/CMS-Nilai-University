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

$proposals_query = "
    SELECT e.Ev_ID, e.Ev_Name, s.Stu_Name, c.Club_Name, e.Updated_At
    FROM events e
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    JOIN eventcomment ec ON e.Ev_ID = ec.Ev_ID
    JOIN eventstatus es ON ec.Status_ID = es.Status_ID
    WHERE es.Status_Name = 'Approved by Advisor (Pending Coordinator Review)'
    ORDER BY e.Updated_At DESC
";

$proposals_result = $conn->query($proposals_query);

$postmortems_query = "
    SELECT ep.Rep_ID, e.Ev_Name, s.Stu_Name, c.Club_Name, ep.Updated_At 
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    WHERE ep.Rep_PostStatus = 'Pending Coordinator Review'
    ORDER BY ep.Updated_At DESC
";

$postmortems_result = $conn->query($postmortems_query);
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Review</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        .table td,
        .table tr {
            vertical-align: middle;
            background-color: #D2FF72;
            border-color: rgb(0, 0, 0);
            text-align: center;
        }

        .table th {
            background-color: #54C392;
            color: white;
            border-color: rgb(0, 0, 0);
        }

        .btn-primary {
            background-color: #32CD32;
            border: none;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            background-color: #15B392;
            transform: scale(1.05);
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
                Event List
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

    <!-- Main Content -->
    <div class="container mt-4">
        <h2 class="text-center">Review Submissions</h2>

        <!-- Proposals Pending Review -->
        <div class="mt-4">
            <h4>Proposals Pending Review</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Student Name</th>
                        <th>Club Name</th>
                        <th>Submission Date</th>
                        <th>Action</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($proposals_result->num_rows > 0): ?>
                        <?php while ($proposal = $proposals_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $proposal['Ev_Name']; ?></td>
                                <td><?php echo $proposal['Stu_Name']; ?></td>
                                <td><?php echo $proposal['Club_Name']; ?></td>
                                <td><?php echo date('d M Y', strtotime($proposal['Updated_At'])); ?></td>
                                <td>
                                    <a href="CoordinatorDecision.php?type=proposal&id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-primary btn-sm">Review</a>
                                </td>
                                <td>
                                    <a href="generate_pdf.php?id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-warning btn-sm">Export</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No proposals pending review.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Postmortems Pending Review -->
        <div class="mt-4">
            <h4>Postmortems Pending Review</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Student Name</th>
                        <th>Club Name</th>
                        <th>Submission Date</th>
                        <th>Action</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($postmortems_result->num_rows > 0): ?>
                        <?php while ($postmortem = $postmortems_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $postmortem['Ev_Name']; ?></td>
                                <td><?php echo $postmortem['Stu_Name']; ?></td>
                                <td><?php echo $postmortem['Club_Name']; ?></td>
                                <td><?php echo date('d M Y', strtotime($postmortem['Updated_At'])); ?></td>
                                <td>
                                    <a href="CoordinatorDecision.php?type=postmortem&id=<?php echo $postmortem['Rep_ID']; ?>"
                                        class="btn btn-primary btn-sm">Review</a>
                                </td>
                                <td>
                                    <a href="reportgeneratepdf.php?id=<?php echo $postmortem['Rep_ID']; ?>"
                                        class="btn btn-primary">Export
                                        to
                                        PDF</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No postmortems pending review.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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