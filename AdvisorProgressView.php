<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

$advisor_id = $_SESSION['Adv_ID'];
$club_id = $_SESSION['Club_ID'];

$stmt = $conn->prepare("SELECT Adv_Name FROM advisor WHERE Adv_ID = ?");
$stmt->bind_param('s', $advisor_id);
$stmt->execute();
$result = $stmt->get_result();
$advisor_name = $result->fetch_assoc()['Adv_Name'];

$query = "
    SELECT 
        e.Ev_ID, e.Ev_Name, s.Stu_Name, e.Ev_Status, p.Rep_PostStatus, e.Ev_Objectives, 
        e.Ev_Intro, e.Ev_Details, e.Ev_Pax, e.Ev_Date, e.Ev_Venue, 
        e.Ev_StartTime, e.Ev_EndTime, pic.PIC_Name
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN eventpostmortem p ON e.Ev_ID = p.Ev_ID
    LEFT JOIN personincharge pic ON e.Ev_ID = pic.Ev_ID
    WHERE e.Club_ID = ? 
      AND (
          (e.Ev_Status IN ('Approved by Advisor', 'Pending Coordinator Review', 'Approved by Coordinator') 
          AND (p.Rep_PostStatus IS NULL OR p.Rep_PostStatus != 'Accepted'))
      )
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $club_id);
$stmt->execute();
$proposals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$start_time = microtime(true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        .btn-view {
            background-color: #32CD32;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease-in-out, transform 0.3s ease-in-out;
        }

        .btn-view:hover {
            background-color: #15B392;
            transform: scale(1.05);
            color: white;
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table th {
            background-color: #54C392;
            /* Green header */
            color: white;
            text-align: center;

        }

        .table td,
        .table tr {
            background-color: #D2FF72;
            text-align: center;
            border-color: rgb(0, 0, 0);
        }

        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: #15B392;
            color: white;
            font-weight: bold;
        }

        .modal-body p {
            margin-bottom: 10px;
            color: #640D5F;
            font-size: 16px;
        }

        .modal-body span {
            font-weight: bold;
            color: black;
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
                Progress
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $advisor_name; ?>
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
    <div class="container mt-4">
        <h1 class="text-center mb-4">Event Progress</h1>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Report Status</th>
                        <th>Actions</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($proposals) > 0): ?>
                        <?php foreach ($proposals as $proposal): ?>
                            <tr>
                                <td><?php echo $proposal['Ev_ID']; ?></td>
                                <td><?php echo $proposal['Ev_Name']; ?></td>
                                <td><?php echo $proposal['Stu_Name']; ?></td>
                                <td>
                                    <?php
                                    if ($proposal['Ev_Status'] === 'Approved by Coordinator') {
                                        echo '<span class="badge bg-success">Approved</span>';
                                    } else {
                                        echo '<span class="badge bg-warning">In Progress</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($proposal['Rep_PostStatus'] === 'Pending Coordinator Review') {
                                        echo '<span class="badge bg-warning">Pending Report Review</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">N/A</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm btn-view" data-bs-toggle="modal"
                                        data-bs-target="#eventDetailsModal"
                                        data-event-name="<?php echo $proposal['Ev_Name']; ?>"
                                        data-event-objectives="<?php echo $proposal['Ev_Objectives']; ?>"
                                        data-event-intro="<?php echo $proposal['Ev_Intro']; ?>"
                                        data-event-details="<?php echo $proposal['Ev_Details']; ?>"
                                        data-event-pax="<?php echo $proposal['Ev_Pax']; ?>"
                                        data-event-venue="<?php echo $proposal['Ev_Venue']; ?>"
                                        data-event-date="<?php echo $proposal['Ev_Date']; ?>"
                                        data-event-starttime="<?php echo $proposal['Ev_StartTime']; ?>"
                                        data-event-endtime="<?php echo $proposal['Ev_EndTime']; ?>"
                                        data-pic-name="<?php echo $proposal['PIC_Name']; ?>">
                                        View
                                    </button>
                                </td>
                                <td>
                                    <a href="generate_pdf.php?id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-warning btn-sm">Export</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No events to display</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Event Name:</strong> <span id="modalEventName"></span></p>
                    <p><strong>Objectives:</strong> <span id="modalEventObjectives"></span></p>
                    <p><strong>Introduction:</strong> <span id="modalEventIntro"></span></p>
                    <p><strong>Details:</strong> <span id="modalEventDetails"></span></p>
                    <p><strong>Estimated Pax:</strong> <span id="modalEventPax"></span></p>
                    <p><strong>Venue:</strong> <span id="modalEventVenue"></span></p>
                    <p><strong>Date:</strong> <span id="modalEventDate"></span></p>
                    <p><strong>Start Time:</strong> <span id="modalEventStartTime"></span></p>
                    <p><strong>End Time:</strong> <span id="modalEventEndTime"></span></p>
                    <p><strong>Person in Charge:</strong> <span id="modalPICName"></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const eventModal = document.getElementById("eventDetailsModal");

            eventModal.addEventListener("show.bs.modal", (event) => {
                const button = event.relatedTarget;

                // Retrieve data attributes
                document.getElementById("modalEventName").textContent = button.getAttribute("data-event-name");
                document.getElementById("modalEventObjectives").textContent = button.getAttribute("data-event-objectives");
                document.getElementById("modalEventIntro").textContent = button.getAttribute("data-event-intro");
                document.getElementById("modalEventDetails").textContent = button.getAttribute("data-event-details");
                document.getElementById("modalEventPax").textContent = button.getAttribute("data-event-pax");
                document.getElementById("modalEventVenue").textContent = button.getAttribute("data-event-venue");
                document.getElementById("modalEventDate").textContent = button.getAttribute("data-event-date");
                document.getElementById("modalEventStartTime").textContent = button.getAttribute("data-event-starttime");
                document.getElementById("modalEventEndTime").textContent = button.getAttribute("data-event-endtime");
                document.getElementById("modalPICName").textContent = button.getAttribute("data-pic-name");
            });
        });
    </script>
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