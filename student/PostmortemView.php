<?php
session_start();
include('../db/dbconfig.php');
$currentPage = 'postmortem'; // Set current page for active sidebar item

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: ../studentlogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];
$student_name = $_SESSION['Stu_Name'];
// Fetch events approved by the coordinator but without a submitted postmortem
$approved_events_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Date, c.Club_Name 
    FROM events e
    JOIN club c ON e.Club_ID = c.Club_ID
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    WHERE e.Stu_ID = '$stu_id' 
      AND es.Status_Name = 'Approved by Coordinator'
      AND NOT EXISTS (
          SELECT 1 
          FROM eventpostmortem ep
          JOIN eventstatus eps ON ep.Status_ID = eps.Status_ID
          WHERE ep.Ev_ID = e.Ev_ID 
          AND eps.Status_Name IN ('Postmortem Pending Review', 'Postmortem Approved')
      )
";
$approved_events_result = $conn->query($approved_events_query);
$total_approved = $approved_events_result->num_rows;


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Post Event</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/student.css?v=<?= time() ?>" rel="stylesheet" />

</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/header.php'); ?>
    <?php include('../components/offcanvas.php'); ?>
    <!-- Main Content -->
    <div class="container-fluid main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-eye me-2"></i>
                Post Event View
            </h2>
        </div>

        <!-- Events Table -->
        <div class="events-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check me-2 text-purple"></i>
                    Post Event List
                </h5>
                <small class="text-muted">Total: <?= $total_approved ?>
                    event<?= $total_approved != 1 ? 's' : '' ?></small>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
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
                                    <td class="event-id"><?php echo $event['Ev_ID']; ?></td>
                                    <td class="event-name"><?php echo $event['Ev_Name']; ?></td>
                                    <td class="club-name"><?php echo $event['Club_Name']; ?></td>
                                    <td class="event-date"><?php echo date('Y-m-d', strtotime($event['Ev_Date'])); ?></td>
                                    <td>
                                        <a href="../student/postevent/PostEvent_Form.php?Ev_ID=<?= $event['Ev_ID']; ?>&mode=create"
                                            class="btn-create-report">
                                            <i class="fas fa-file-medical-alt"></i>
                                            Create Report
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-events">
                                    <i class="fas fa-info-circle"></i><br />
                                    No approved events available for Post Event creation.
                                </td>
                            </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>