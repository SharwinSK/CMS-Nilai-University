<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'dashboard';
// Check if user is logged in and is an admin
if (!isset($_SESSION['Admin_ID']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/adminlogin.php");
    exit();
}

// Get admin details for navbar
$admin_query = "SELECT Admin_Name FROM admin WHERE Admin_ID = ?";
$admin_stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($admin_stmt, "i", $_SESSION['Admin_ID']);
mysqli_stmt_execute($admin_stmt);
$admin_result = mysqli_stmt_get_result($admin_stmt);
$admin_data = mysqli_fetch_assoc($admin_result);
$admin_name = $admin_data['Admin_Name'] ?? 'Admin';
mysqli_stmt_close($admin_stmt);

// Fetch events with their posters (exclude events that have postmortem with status 6 or higher)
$poster_query = "SELECT e.Ev_Name, e.Ev_Poster, c.Club_Name 
                FROM events e 
                JOIN club c ON e.Club_ID = c.Club_ID 
                LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
                WHERE e.Ev_Poster IS NOT NULL AND e.Ev_Poster != '' 
                AND (ep.Status_ID IS NULL OR ep.Status_ID < 6)
                ORDER BY e.created_at DESC 
                LIMIT 5";
$poster_result = mysqli_query($conn, $poster_query);

// Fetch latest 10 students data
$student_query = "SELECT Stu_ID, Stu_Name, Stu_Program FROM student ORDER BY Stu_ID DESC LIMIT 10";
$student_result = mysqli_query($conn, $student_query);

// Fetch events for calendar and main dashboard (exclude approved proposals - status 5, and exclude events with postmortem status 6+)
$events_query = "SELECT e.Ev_ID, e.Ev_Name, e.Ev_Date, e.Stu_ID, s.Stu_Name, c.Club_Name, es.Status_Name,
                        CASE 
                            WHEN es.Status_ID = 5 THEN 'Approved'
                            WHEN es.Status_ID = 1 THEN 'Pending'
                            WHEN es.Status_ID IN (2,4) THEN 'Rejected'
                            WHEN es.Status_ID = 8 THEN 'Completed'
                            ELSE 'Unknown'
                        END as Status_Display
                 FROM events e 
                 LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
                 LEFT JOIN club c ON e.Club_ID = c.Club_ID
                 LEFT JOIN eventstatus es ON e.Status_ID = es.Status_ID
                 LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
                 WHERE e.Status_ID != 5 
                 AND (ep.Status_ID IS NULL OR ep.Status_ID < 6)
                 ORDER BY e.Ev_Date DESC";
$events_result = mysqli_query($conn, $events_query);

// Fetch post-event reports (exclude approved reports - status 8)
$postevent_query = "SELECT e.Ev_ID, e.Ev_Name, ep.Rep_ID, ep.created_at as submitted_date, es.Status_Name,
                           CASE 
                               WHEN es.Status_ID = 6 THEN 'Pending'
                               WHEN es.Status_ID = 7 THEN 'Rejected'
                               WHEN es.Status_ID = 8 THEN 'Approved'
                               ELSE 'Unknown'
                           END as Status_Display
                    FROM eventpostmortem ep
                    JOIN events e ON ep.Ev_ID = e.Ev_ID
                    LEFT JOIN eventstatus es ON ep.Status_ID = es.Status_ID
                    WHERE ep.Status_ID != 8
                    ORDER BY ep.created_at DESC";
$postevent_result = mysqli_query($conn, $postevent_query);

// Fetch advisors data with event completion count - ordered by events done
$advisor_query = "SELECT a.Adv_Name, c.Club_Name, a.Adv_Email,
                  COUNT(CASE WHEN ep.Status_ID = 8 THEN 1 END) as events_done
                 FROM advisor a 
                 JOIN club c ON a.Club_ID = c.Club_ID 
                 LEFT JOIN events e ON c.Club_ID = e.Club_ID
                 LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
                 GROUP BY a.Adv_ID, a.Adv_Name, c.Club_Name, a.Adv_Email
                 ORDER BY events_done DESC, a.Adv_Name 
                 LIMIT 10";
$advisor_result = mysqli_query($conn, $advisor_query);

// Process events for calendar
$calendar_events = [];
$main_events = [];
$post_events = [];
while ($event = mysqli_fetch_assoc($events_result)) {
    // For calendar
    $date_key = $event['Ev_Date'];
    if (!isset($calendar_events[$date_key])) {
        $calendar_events[$date_key] = [];
    }
    $calendar_events[$date_key][] = [
        'name' => $event['Ev_Name'],
        'club' => $event['Club_Name'],
        'student' => $event['Stu_Name'],
        'student_id' => $event['Stu_ID']
    ];

    // For main events table
    $main_events[] = $event;
}

// Process post-event reports
while ($post_event = mysqli_fetch_assoc($postevent_result)) {
    $post_events[] = $post_event;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />
    <link href="../assets/css/admin/dashboard.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>

    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>



    <!-- Poster Modal -->
    <div class="modal fade" id="posterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="posterModalTitle">Event Poster</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="posterModalImage" src="" alt="Event Poster" class="img-fluid"
                        style="max-width: 100%; height: auto;">
                    <div class="mt-3">
                        <h6 id="posterModalEventName"></h6>
                        <p id="posterModalClubName" class="text-muted"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade event-modal" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Event Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Event details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-4">
                <!-- Poster Section -->
                <div class="poster-carousel mb-3">
                    <h5 class="section-title">
                        <i class="fas fa-image me-2"></i>Ongoing Events
                    </h5>
                    <?php if (mysqli_num_rows($poster_result) > 0): ?>
                        <?php
                        // Convert result to array for reuse
                        $poster_items = [];
                        mysqli_data_seek($poster_result, 0);
                        while ($row = mysqli_fetch_assoc($poster_result)) {
                            $poster_items[] = $row;
                        }
                        ?>
                        <div id="posterCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                            <div class="carousel-indicators">
                                <?php foreach ($poster_items as $i => $_): ?>
                                    <button type="button" data-bs-target="#posterCarousel" data-bs-slide-to="<?= $i ?>"
                                        class="<?= $i === 0 ? 'active' : '' ?>"
                                        aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                                        aria-label="Slide <?= $i + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>

                            <div class="carousel-inner">
                                <?php foreach ($poster_items as $i => $poster):
                                    $poster_path = '../uploads/posters/' . basename($poster['Ev_Poster']);
                                    $safe_alt = htmlspecialchars($poster['Ev_Name'] ?? 'Event Poster', ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                        <div class="poster-slide">
                                            <img src="<?= $poster_path ?>" alt="<?= $safe_alt ?>" loading="lazy"
                                                style="cursor: pointer; background: transparent;"
                                                onclick="showPosterModal('<?= $poster_path ?>', '<?= addslashes($poster['Ev_Name']) ?>', '<?= addslashes($poster['Club_Name']) ?>')"
                                                onerror="this.src='../assets/img/PlaceHolder.png';">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button class="carousel-control-prev" type="button" data-bs-target="#posterCarousel"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#posterCarousel"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <img src="../assets/img/PlaceHolder.png" class="img-fluid"
                                style="max-height: 200px; background: transparent;" alt="No events">
                            <p class="mt-2 text-muted">No ongoing events at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Calendar Section -->
                <div class="calendar-container mb-3">
                    <h5 class="section-title">
                        <i class="fas fa-calendar me-2"></i>Calendar
                    </h5>
                    <div class="calendar-header">
                        <button class="calendar-nav" onclick="previousMonth()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h6 id="currentMonth" class="mb-0"></h6>
                        <button class="calendar-nav" onclick="nextMonth()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="calendar-grid-small" id="calendarGrid">
                        <!-- Calendar will be generated by JavaScript -->
                    </div>
                </div>

                <!-- Students Section with Scrolling -->
                <div class="container-card mb-3" style="padding: 1rem;">
                    <h5 class="section-title">
                        <i class="fas fa-user-graduate me-2"></i>Student List
                    </h5>
                    <div class="table-container">
                        <div class="student-list-container">
                            <table class="table summary-table mb-0">
                                <thead style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="font-size: 0.8rem;">ID</th>
                                        <th style="font-size: 0.8rem;">Name</th>
                                        <th style="font-size: 0.8rem;">Program</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = mysqli_fetch_assoc($student_result)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($student['Stu_ID']); ?></strong></td>
                                            <td style="font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($student['Stu_Name']); ?>
                                            </td>
                                            <td style="font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($student['Stu_Program']); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button class="icon-btn small" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="View All Students"
                                onclick="window.location.href='../admin/studentmanagement.php'">
                                <i class="fas fa-user-graduate"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-8">
                <!-- Main Event Section (Large) -->
                <div class="container-card" style="padding: 1rem;">
                    <h4 class="section-title">
                        <i class="fas fa-calendar-check me-2"></i>Event Management
                    </h4>
                    <div class="events-table-container">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs" id="eventTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="proposals-tab" data-bs-toggle="tab"
                                    data-bs-target="#proposals" type="button" role="tab">
                                    <i class="fas fa-file-alt me-2"></i>Event Proposals
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports"
                                    type="button" role="tab">
                                    <i class="fas fa-clipboard-check me-2"></i>Post-Event Reports
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="eventTabContent">
                            <!-- Event Proposals Tab -->
                            <div class="tab-pane fade show active" id="proposals" role="tabpanel">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table summary-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Event Name</th>
                                                <th>Date</th>
                                                <th>Student</th>
                                                <th>Club</th>
                                                <th>Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($main_events as $event): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($event['Ev_Name']); ?></strong><br>
                                                        <small class="text-muted">Event ID:
                                                            <?php echo htmlspecialchars($event['Ev_ID']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($event['Ev_Date'])); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($event['Stu_Name']); ?><br>
                                                        <small
                                                            class="text-muted"><?php echo htmlspecialchars($event['Stu_ID']); ?></small>
                                                    </td>
                                                    <td><span
                                                            class="club-text"><?php echo htmlspecialchars($event['Club_Name']); ?></span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="status-badge status-<?php echo strtolower($event['Status_Display']); ?>">
                                                            <?php echo htmlspecialchars($event['Status_Display']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <button class="btn btn-sm btn-outline-primary me-1"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="Edit Event">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" title="View Event Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Post-Event Reports Tab -->
                            <div class="tab-pane fade" id="reports" role="tabpanel">
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table summary-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Event Name</th>
                                                <th>Report ID</th>
                                                <th>Submitted Date</th>
                                                <th>Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($post_events)): ?>
                                                <?php foreach ($post_events as $post_event): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($post_event['Ev_Name']); ?></strong><br>
                                                            <small class="text-muted">Event ID:
                                                                <?php echo htmlspecialchars($post_event['Ev_ID']); ?></small>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($post_event['Rep_ID']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <?php echo date('M j, Y', strtotime($post_event['submitted_date'])); ?><br>
                                                            <small
                                                                class="text-muted"><?php echo date('H:i', strtotime($post_event['submitted_date'])); ?></small>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="status-badge status-<?php echo strtolower($post_event['Status_Display']); ?>">
                                                                <?php echo htmlspecialchars($post_event['Status_Display']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-outline-primary me-1"
                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                title="Edit Report">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                                data-bs-placement="top" title="View Report">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                                                        No post-event reports submitted yet.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button class="icon-btn me-2" onclick="window.location.href='../admin/eventmanagement.php'"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="View All Events">
                                <i class="fas fa-list"></i>
                            </button>
                            <button class="icon-btn add-btn" onclick="showAddEventAlert()" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="Add New Event">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advisor Section -->
                <div class="container-card" style="padding: 1rem; min-height: 400px;">
                    <h5 class="section-title">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Advisors List
                    </h5>
                    <div class="table-container" style="min-height: 320px;">
                        <div class="table-responsive">
                            <table class="table summary-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Advisor Name</th>
                                        <th>Club</th>
                                        <th>Email</th>
                                        <th>Events Completed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($advisor = mysqli_fetch_assoc($advisor_result)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($advisor['Adv_Name']); ?></strong></td>
                                            <td><span
                                                    class="club-text"><?php echo htmlspecialchars($advisor['Club_Name']); ?></span>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($advisor['Adv_Email']); ?></small></td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo $advisor['events_done'] > 0 ? 'success' : 'secondary'; ?>"
                                                    style="font-size: 0.9rem; padding: 0.4rem 0.8rem;">
                                                    <?php echo htmlspecialchars($advisor['events_done']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button class="icon-btn small" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="View All Advisors"
                                onclick="window.location.href='../admin/advisormanagement.php'">
                                <i class="fas fa-users"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // PHP data to JavaScript
        const calendarEvents = <?php echo json_encode($calendar_events); ?>;

        let currentDate = new Date();
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];

        function generateCalendar(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            document.getElementById('currentMonth').textContent =
                monthNames[month] + ' ' + year;

            let calendarHTML = '';
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            // Add day headers
            dayHeaders.forEach(day => {
                calendarHTML += `<div class="calendar-day" style="background: var(--header-green); color: white; font-weight: bold; text-align: center; padding: 0.3rem; font-size: 0.8rem;">${day}</div>`;
            });

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < startingDayOfWeek; i++) {
                const prevDate = new Date(year, month, 0 - (startingDayOfWeek - 1 - i));
                calendarHTML += `<div class="calendar-day other-month" style="font-size: 0.8rem;">${prevDate.getDate()}</div>`;
            }

            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const today = new Date();
                const isToday = year === today.getFullYear() &&
                    month === today.getMonth() &&
                    day === today.getDate();

                let dayClass = 'calendar-day';
                if (isToday) dayClass += ' today';
                if (calendarEvents[dateStr]) dayClass += ' has-event';

                let eventHTML = '';
                if (calendarEvents[dateStr]) {
                    eventHTML = `<div class="event-indicator" onclick="showEventDetails('${dateStr}')">
                        ${calendarEvents[dateStr].length}
                     </div>`;
                }

                calendarHTML += `<div class="${dayClass}" style="font-size: 0.8rem;">
                    <div style="font-weight: bold; margin-bottom: 2px;">${day}</div>
                    ${eventHTML}
                </div>`;
            }

            // Fill remaining cells
            const totalCells = Math.ceil((daysInMonth + startingDayOfWeek) / 7) * 7;
            const remainingCells = totalCells - (daysInMonth + startingDayOfWeek);

            for (let i = 1; i <= remainingCells; i++) {
                calendarHTML += `<div class="calendar-day other-month" style="font-size: 0.8rem;">${i}</div>`;
            }

            document.getElementById('calendarGrid').innerHTML = calendarHTML;
        }

        function showEventDetails(dateStr) {
            const events = calendarEvents[dateStr];
            if (!events || events.length === 0) return;

            let modalContent = `
                <div class="mb-3">
                    <h6><i class="fas fa-calendar me-2"></i>Events on ${new Date(dateStr).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            })}</h6>
                </div>
            `;

            events.forEach((event, index) => {
                modalContent += `
                    <div class="card mb-2" style="border: 1px solid var(--header-green);">
                        <div class="card-body">
                            <h6 class="card-title text-success">
                                <i class="fas fa-calendar-check me-2"></i>${event.name}
                            </h6>
                            <p class="card-text mb-1">
                                <strong><i class="fas fa-users me-2"></i>Club:</strong> ${event.club}
                            </p>
                            <p class="card-text mb-1">
                                <strong><i class="fas fa-user me-2"></i>Student:</strong> ${event.student}
                            </p>
                            <p class="card-text mb-0">
                                <strong><i class="fas fa-id-card me-2"></i>Student ID:</strong> ${event.student_id}
                            </p>
                        </div>
                    </div>
                `;
            });

            document.getElementById('eventModalBody').innerHTML = modalContent;

            const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            eventModal.show();
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
        }

        // Show poster in modal
        function showPosterModal(imageSrc, eventName, clubName) {
            document.getElementById('posterModalImage').src = imageSrc;
            document.getElementById('posterModalEventName').textContent = eventName;
            document.getElementById('posterModalClubName').textContent = clubName;
            document.getElementById('posterModalTitle').textContent = eventName + ' - Event Poster';

            const posterModal = new bootstrap.Modal(document.getElementById('posterModal'));
            posterModal.show();
        }

        // Initialize calendar
        generateCalendar(currentDate.getFullYear(), currentDate.getMonth());

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Show add event alert
        function showAddEventAlert() {
            Swal.fire({
                icon: 'warning',
                title: 'Feature Not Available',
                text: '⚠️ Sorry! This feature is not available at the moment. It will be included in a future update. Thank you for your understanding.',
                confirmButtonColor: '#25aa20',
                confirmButtonText: 'OK'
            });
        }

        // Logout functionality using your existing LogoutDesign.php
        document.getElementById('confirmLogout').addEventListener('click', () => {
            fetch('../Logout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert('Logout failed. Please try again.');
                    }
                })
                .catch(err => {
                    console.error('Error during logout:', err);
                    alert('An error occurred. Please try again.');
                });
        });
    </script>
</body>

</html>