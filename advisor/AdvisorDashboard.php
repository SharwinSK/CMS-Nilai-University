<?php
session_start();
$currentPage = 'dashboard';
include('../db/dbconfig.php'); // Adjust if needed


// Security check
if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

$advisor_id = $_SESSION['Adv_ID'];
$club_id = $_SESSION['Club_ID'];

// Get advisor name
$stmt = $conn->prepare("SELECT Adv_Name FROM advisor WHERE Adv_ID = ?");
$stmt->bind_param('s', $advisor_id);
$stmt->execute();
$result = $stmt->get_result();
$advisor_name = $result->fetch_assoc()['Adv_Name'] ?? 'Unknown Advisor';

// Get carousel posters (Approved by Coordinator + Postmortem not done)
$carousel_query = "
    SELECT DISTINCT e.Ev_ID, e.Ev_Poster 
    FROM events e
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN eventstatus eps ON ep.Status_ID = eps.Status_ID
    WHERE es.Status_Name = 'Approved by Coordinator' 
      AND (ep.Status_ID IS NULL OR eps.Status_Name != 'Postmortem Approved')
";
$carousel_result = $conn->query($carousel_query);

// Get pending proposals for advisor's club
$pending_proposals_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.created_at, s.Stu_Name
    FROM events e
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    WHERE e.Club_ID = ?
      AND es.Status_Name = 'Pending Advisor Review'
";

$stmt = $conn->prepare($pending_proposals_query);
$stmt->bind_param('i', $club_id);
$stmt->execute();
$pending_proposals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get event summary for advisor's club (Status ID: 3, 4, 5 for proposals and 6 for post-events)
$event_summary_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Date, 
           COALESCE(eps.Status_Name, es.Status_Name) as Status_Name, 
           es.Status_Type, s.Stu_Name
    FROM events e
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN eventstatus eps ON ep.Status_ID = eps.Status_ID
    WHERE e.Club_ID = ? 
      AND ((e.Status_ID IN (3, 4, 5) AND (ep.Status_ID IS NULL OR ep.Status_ID != 8))
           OR ep.Status_ID IN (6, 7))
    ORDER BY e.Ev_Date DESC, e.created_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($event_summary_query);
$stmt->bind_param('i', $club_id);
$stmt->execute();
$event_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calendar events: get event name + date for advisor's club
$calendar_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Date, c.Club_Name
    FROM events e
    JOIN club c ON c.Club_ID = e.Club_ID
    WHERE e.Status_ID = 5
      AND NOT EXISTS (
          SELECT 1 FROM eventpostmortem ep
          WHERE ep.Ev_ID = e.Ev_ID
      )
    ORDER BY e.Ev_Date ASC
";

$calendar_result = $conn->query($calendar_query);


$calendar_events = [];
while ($row = $calendar_result->fetch_assoc()) {
    $dateKey = $row['Ev_Date']; // YYYY-MM-DD
    if (!isset($calendar_events[$dateKey])) {
        $calendar_events[$dateKey] = [];
    }
    $calendar_events[$dateKey][] = [
        'name' => $row['Ev_Name'],
        'club' => $row['Club_Name']
    ];
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Advisor Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link href="../assets/css/advisor.css?v=<?= time() ?>" rel="stylesheet" />
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />

</head>

<body>
    <?php include('../components/Advoffcanvas.php'); ?>
    <?php include('../components/Advheader.php'); ?>
    <?php include('../model/LogoutDesign.php'); ?>
    <!-- Main Content -->
    <div class="container-fluid main-content">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Poster Carousel -->
                <div class="dashboard-card carousel-container">
                    <?php include('../components/carousel.php'); ?>
                </div>
                <!-- Graph Panel -->
                <div class="dashboard-card">
                    <h4 class="section-title">
                        <i class="fas fa-chart-bar me-2"></i> Event Summary Chart (still in Development)
                    </h4>
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Year</label>
                                <select class="form-select" id="yearFilter">
                                    <option value="2025">2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Month</label>
                                <select class="form-select" id="monthFilter">
                                    <option value="all">All Months</option>
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7" selected>July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Event Type</label>
                                <select class="form-select" id="eventTypeFilter">
                                    <option value="all">All Types</option>
                                    <option value="academic">Academic</option>
                                    <option value="cultural">Cultural</option>
                                    <option value="sports">Sports</option>
                                    <option value="technology">Technology</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="eventChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Status Panel -->
                <div class="dashboard-card">
                    <div class="notification-panel">
                        <h4 class="section-title">
                            <i class="fas fa-bell me-2"></i>
                            Notification
                        </h4>
                        <?php include('../components/Advnotifypanel.php'); ?>
                    </div>
                </div>

                <!-- Event Summary Panel -->
                <div class="dashboard-card">
                    <div class="event-summary-header">
                        <h4 class="section-title">
                            <i class="fas fa-list-ul me-2"></i>
                            Event Summary
                        </h4>
                        <a href="AdvisorProgressView.php" class="view-btn">
                            <i class="fas fa-eye me-1"></i>View All
                        </a>
                    </div>
                    <div class="event-summary-list">
                        <?php if (empty($event_summary)): ?>
                            <div class="no-events-message">
                                <p class="text-muted text-center">No events to display</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($event_summary as $event): ?>
                                <div class="event-list-item">
                                    <div class="event-info">
                                        <div class="event-name">
                                            <?= htmlspecialchars($event['Ev_Name']) ?>
                                        </div>
                                        <div class="event-meta">
                                            <small class="text-muted">
                                                <?= date('M j, Y', strtotime($event['Ev_Date'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="event-status">
                                        <?php
                                        $status_class = '';
                                        $status_short = '';
                                        switch ($event['Status_Name']) {
                                            case 'Approved by Advisor (Pending Coordinator Review)':
                                                $status_class = 'status-pending';
                                                $status_short = 'Pending Review';
                                                break;
                                            case 'Rejected by Coordinator':
                                                $status_class = 'status-rejected';
                                                $status_short = 'Rejected';
                                                break;
                                            case 'Approved by Coordinator':
                                                $status_class = 'status-approved';
                                                $status_short = 'Approved';
                                                break;
                                            case 'Postmortem Pending Review':
                                                $status_class = 'status-pending';
                                                $status_short = 'PM Pending';
                                                break;
                                            default:
                                                $status_class = 'status-pending';
                                                $status_short = 'Pending';
                                        }
                                        ?>
                                        <span class="status-badge-small <?= $status_class ?>">
                                            <?= $status_short ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="calendar-container">
                    <h4 class="section-title">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Event Calendar
                    </h4>
                    <div class="calendar-header">
                        <div id="prevMonthBtn" class="calendar-nav-btn">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <h5 class="calendar-month-title" id="currentMonth">July 2025</h5>
                        <div id="nextMonthBtn" class="calendar-nav-btn">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>

                    <div class="calendar-wrapper">
                        <div class="calendar-grid">
                            <div class="calendar-day-header">Sun</div>
                            <div class="calendar-day-header">Mon</div>
                            <div class="calendar-day-header">Tue</div>
                            <div class="calendar-day-header">Wed</div>
                            <div class="calendar-day-header">Thu</div>
                            <div class="calendar-day-header">Fri</div>
                            <div class="calendar-day-header">Sat</div>
                        </div>
                        <div id="calendarDays" class="calendar-grid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Event Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Events on this Day</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Events will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>const events = <?php echo json_encode($calendar_events); ?>;</script>
    <script src="../assets/js/advisorjs/advdashboard.js?v=<?= time(); ?>"></script>
</body>

</html>