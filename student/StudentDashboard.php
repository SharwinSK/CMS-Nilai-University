<?php
session_start();
include('../db/dbconfig.php'); // Adjust path if needed


if (!isset($_SESSION['Stu_ID'])) {
    header("Location: ../studentlogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];

$carousel_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Poster 
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE e.Stu_ID = '$stu_id'
      AND e.Status_ID = 5
      AND ep.Status_ID IS NULL
";


$carousel_result = $conn->query($carousel_query);
$first = true;

// Pending Proposals
$pending_proposals_query = "
    SELECT COUNT(*) AS total_pending 
    FROM events e
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    WHERE e.Stu_ID = '$stu_id' 
      AND es.Status_Type = 'Proposal'
      AND e.Status_ID BETWEEN 1 AND 4
";

$pending_proposals_result = $conn->query($pending_proposals_query);
$pending_proposals = $pending_proposals_result->fetch_assoc()['total_pending'] ?? 0;

// Pending Post Event
$pending_post_query = "
    SELECT COUNT(*) AS total_post_pending
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    JOIN eventstatus es ON ep.Status_ID = es.Status_ID
    WHERE e.Stu_ID = '$stu_id' AND es.Status_Type = 'Postmortem'
          AND es.Status_Name != 'Postmortem Approved'
";
$pending_post_result = $conn->query($pending_post_query);
$pending_post = $pending_post_result->fetch_assoc()['total_post_pending'] ?? 0;

// Completed Events
$completed_query = "
    SELECT COUNT(*) AS total_completed
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    JOIN eventstatus es ON ep.Status_ID = es.Status_ID
    WHERE e.Stu_ID = '$stu_id' AND es.Status_Name = 'Postmortem Approved'
";
$completed_result = $conn->query($completed_query);
$total_completed = $completed_result->fetch_assoc()['total_completed'] ?? 0;


$notification_query = "
    SELECT 
        e.Ev_ID, 
        e.Ev_Name, 
        es.Status_Name, 
        es.Status_ID,
        CASE 
            WHEN ep.Rep_ID IS NULL THEN 'Proposal'
            ELSE 'Postmortem'
        END AS Type
    FROM events e
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE e.Stu_ID = '$stu_id'
      AND (
        (ep.Rep_ID IS NULL AND es.Status_ID BETWEEN 1 AND 5)
        OR (ep.Status_ID BETWEEN 6 AND 7)
      )
    ORDER BY e.Updated_At DESC
";

$notification_result = $conn->query($notification_query);

$calendar_event_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Date 
    FROM events e
    WHERE e.Stu_ID = '$stu_id' AND e.Status_ID = 5
";
$calendar_event_result = $conn->query($calendar_event_query);

$events_by_date = [];
while ($row = $calendar_event_result->fetch_assoc()) {
    $date = $row['Ev_Date'];
    $events_by_date[$date][] = $row['Ev_Name'];
}
?>

<?php include('../components/header.php'); ?>
<?php include('../components/offcanvas.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/student.css" rel="stylesheet" />

</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>

    <!-- Main Content -->
    <div class="container-fluid p-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $pending_proposals; ?></div>
                    <div class="stats-label">Total Pending Proposal</div>
                    <i class="fas fa-file-alt position-absolute" style="
                top: 20px;
                right: 20px;
                font-size: 2rem;
                color: var(--primary-purple);
                opacity: 0.3;
              "></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $pending_post; ?></div>
                    <div class="stats-label">Total Pending Post Event</div>
                    <i class="fas fa-calendar-check position-absolute" style="
                top: 20px;
                right: 20px;
                font-size: 2rem;
                color: var(--primary-purple);
                opacity: 0.3;
              "></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_completed; ?></div>
                    <div class="stats-label">Total Complete Event</div>
                    <i class="fas fa-trophy position-absolute" style="
                top: 20px;
                right: 20px;
                font-size: 2rem;
                color: var(--primary-purple);
                opacity: 0.3;
              "></i>
                </div>
            </div>
        </div>
        <!-- Attach Components -->
        <div class="row">
            <div class="col-lg-8">
                <?php include('../components/carousel.php'); ?> <!-- Event Carousel -->
                <?php include('../components/calendar.php'); ?><!-- Event Calendar -->
            </div>
            <div class="col-lg-4">
                <?php include('../components/notificationpanel.php'); ?> <!-- Notification Panel -->
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="floating-btn" onclick="toggleFloatingMenu()">
        <i class="fas fa-plus" id="floatingIcon"></i>
    </div>

    <!-- Floating Menu -->
    <div class="floating-menu" id="floatingMenu">
        <a href="#" class="floating-menu-item" onclick="createProposal()">
            <i class="fas fa-file-alt"></i>
            <span>Create Proposal</span>
        </a>
        <a href="#" class="floating-menu-item" onclick="createPostEvent()">
            <i class="fas fa-calendar-plus"></i>
            <span>Create Post Event</span>
        </a>
    </div>

    <!-- Event Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content event-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Event details will be populated here, i already done in student.js -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/student.js"></script>
    <script>
        const calendarEvents = <?php echo json_encode($events_by_date); ?>;
        const events = calendarEvents; // Ensure this is defined globally
        document.addEventListener("DOMContentLoaded", () => {
            updateCalendarDisplay(); // Directly call update on load
        });
    </script>



</body>

</html>