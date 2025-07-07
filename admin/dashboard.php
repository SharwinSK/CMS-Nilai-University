<?php
session_start();
include __DIR__ . '/../dbconfig.php';




/* ─── SECURITY ───────────────────────────────────────── */
if (
    !isset($_SESSION['Admin_ID']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'admin'
) {
    header("Location: adminlogin.php");
    exit();
}


/* ─── QUICK METRICS ──────────────────────────────────── */
$totalStudents = 0;
$totalAdvisors = 0;
$pendingProposals = 0;
$completedEvents = 0;

/* Students */
$res = $conn->query("SELECT COUNT(*) AS n FROM student");
if ($row = $res->fetch_assoc())
    $totalStudents = $row['n'];



/* Advisors */
$res = $conn->query("SELECT COUNT(*) AS n FROM advisor");
if ($row = $res->fetch_assoc())
    $totalAdvisors = $row['n'];

// Total Events Ongoing  

$res = $conn->query("SELECT COUNT(*) AS n FROM events WHERE Status_ID = 5");
if ($row = $res->fetch_assoc()) {
    $ongoingEvents = $row['n'];
} else {
    $ongoingEvents = 0;
}


/* Completed events  (Status_ID = 5 = ‘Approved by Coordinator’) */

$res = $conn->query("
    SELECT COUNT(*) AS n 
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
 
");
if ($row = $res->fetch_assoc())
    $completedEvents = $row['n'];

/* ─── EVENT HIGHLIGHTS (latest 3 posters) ────────────── */
$highlights = [];
$sql = "
    SELECT e.Ev_Name, e.Ev_Poster
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE e.Ev_Poster IS NOT NULL
      AND e.Status_ID = 5
      AND ep.Rep_ID IS NULL  -- No post-event report yet
    ORDER BY e.Updated_At DESC
    LIMIT 3
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc())
    $highlights[] = $row;

/* ─── CALENDAR EVENTS (dates for current & next month) ─ */
$calendarEvents = [];           // 'YYYY-MM-DD' => [ titles ]
$monthStart = date('Y-m-01');
$nextMonthEnd = date('Y-m-t', strtotime('+1 month'));

$sql = "
    SELECT Ev_Date, Ev_Name
    FROM events
    WHERE Ev_Date BETWEEN '$monthStart' AND '$nextMonthEnd'
      AND Status_ID = 5
";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $date = $row['Ev_Date'];
    if (!isset($calendarEvents[$date]))
        $calendarEvents[$date] = [];
    $calendarEvents[$date][] = $row['Ev_Name'];
}

/* Pass calendar data to JS */
$calendarJs = json_encode($calendarEvents, JSON_HEX_TAG);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai University - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tooltip.js/1.3.3/tooltip.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>

    <style>
        :root {
            --primary-color: #03a791;
            --secondary-color: #81e7af;
            --accent-color: #e9f5be;
            --warm-color: #f1ba88;
            --light-bg: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, var(--accent-color), var(--light-bg));
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling */
        .offcanvas-start {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            width: 280px;
        }

        .offcanvas-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.5rem;
        }

        .offcanvas-title {
            color: white;
            font-weight: bold;
            font-size: 1.4rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
            transform: translateX(5px);
        }

        .nav-link.active {
            background-color: var(--warm-color);
            color: var(--primary-color) !important;
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stats-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stats-icon {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Carousel Styling */
        .carousel-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .carousel-item img {
            border-radius: 10px;
            object-fit: cover;
        }

        .carousel-caption {
            background: rgba(3, 167, 145, 0.9);
            border-radius: 8px;
            padding: 1rem;
        }

        /* Calendar Styling */
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--secondary-color);
        }

        .calendar-nav {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .calendar-nav:hover {
            background: var(--warm-color);
            transform: scale(1.05);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-day {
            background: white;
            padding: 0.8rem;
            min-height: 80px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .calendar-day:hover {
            background: var(--accent-color);
        }

        .calendar-day.other-month {
            background: #f8f9fa;
            color: #ccc;
        }

        .calendar-day.today {
            background: var(--warm-color);
            color: white;
            font-weight: bold;
        }

        .calendar-day.has-event {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
        }

        .calendar-day.has-event::after {
            content: '';
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
        }

        .event-indicator {
            font-size: 0.7rem;
            background: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            margin-top: 2px;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.75rem;
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            cursor: pointer;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg"
        style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#adminSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand text-white fw-bold" href="#">
                <i class="fas fa-university me-2"></i>Nilai University CMS
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i
                                    class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="adminSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link active" href="dashboard.php" data-section="dashboard">
                    <i class="fas fa-home me-2"></i>Admin Dashboard
                </a>
                <a class="nav-link" href="eventmanagement.php" data-section="events">
                    <i class="fas fa-calendar-alt me-2"></i>Event Management
                </a>
                <a class="nav-link" href="clubmanagement.php" data-section="clubs">
                    <i class="fas fa-users me-2"></i>Club Management
                </a>
                <a class="nav-link" href="advisormanagement.php" data-section="advisors">
                    <i class="fas fa-user-tie me-2"></i>Advisor Management
                </a>
                <a class="nav-link" href="coordinatormanagement.php" data-section="coordinators">
                    <i class="fas fa-user-cog me-2"></i>Coordinator Management
                </a>
                <a class="nav-link" href="usermanagement.php" data-section="users">
                    <i class="fas fa-user-friends me-2"></i>User Management
                </a>
                <a class="nav-link" href="reportexport.php" data-section="reports">
                    <i class="fas fa-chart-bar me-2"></i>Report & Export
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="section-title">
                    <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                </h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= number_format($totalStudents) ?></div>
                            <div class="stats-label">Total Students</div>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= number_format($totalAdvisors) ?></div>
                            <div class="stats-label">Total Advisors</div>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= number_format($ongoingEvents) ?></div>
                            <div class="stats-label">Total Event Ongoing</div>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= number_format($completedEvents) ?></div>
                            <div class="stats-label">Events Completed</div>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Event Carousel -->
            <div class="col-lg-8">
                <div class="carousel-container">
                    <h4 class="section-title">
                        <i class="fas fa-images me-2"></i>Event Highlights
                    </h4>
                    <?php if (empty($highlights)): ?>
                        <div class="p-4 text-center text-muted" style="font-style: italic;">
                            No event highlights currently.
                        </div>
                    <?php endif; ?>
                    <div id="eventCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="0"
                                class="active"></button>
                            <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#eventCarousel" data-bs-slide-to="2"></button>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($highlights as $index => $ev): ?>
                                <?php
                                $posterPath = $ev['Ev_Poster'];

                                // Fix path if it's relative (like "uploads/...")
                                if ($posterPath && !preg_match('#^https?://#', $posterPath)) {
                                    if (!str_starts_with($posterPath, '../')) {
                                        $posterPath = '../' . ltrim($posterPath, '/');
                                    }
                                }
                                ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">

                                    <img src="<?= htmlspecialchars($posterPath) ?>" class="d-block w-100"
                                        alt="<?= htmlspecialchars($ev['Ev_Name']) ?>"
                                        style="height: 400px; width: 100%; object-fit: contain; background: #fff;">
                                </div>
                            <?php endforeach; ?>


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
                </div>
            </div>

            <!-- Calendar -->
            <div class="col-lg-4">
                <div class="calendar-container">
                    <h4 class="section-title">
                        <i class="fas fa-calendar me-2"></i>Event Calendar
                    </h4>
                    <div class="calendar-header">
                        <button class="calendar-nav" onclick="previousMonth()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h5 id="currentMonth" class="mb-0"></h5>
                        <button class="calendar-nav" onclick="nextMonth()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- Calendar will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>

        // Sample events data
        const events = <?= $calendarJs ?>;

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
                calendarHTML += `<div class="calendar-day" style="background: var(--primary-color); color: white; font-weight: bold; text-align: center; padding: 0.5rem;">${day}</div>`;
            });

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < startingDayOfWeek; i++) {
                const prevDate = new Date(year, month, 0 - (startingDayOfWeek - 1 - i));
                calendarHTML += `<div class="calendar-day other-month">${prevDate.getDate()}</div>`;
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
                if (events[dateStr]) dayClass += ' has-event';

                let eventHTML = '';
                if (events[dateStr]) {
                    const tooltipText = events[dateStr].length + ' event(s):\n' + events[dateStr].join('\n');
                    eventHTML = `<div class="event-indicator"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="${tooltipText.replace(/"/g, '&quot;')}">
                    ${events[dateStr].length} event(s)
                 </div>`;
                }


                calendarHTML += `<div class="${dayClass}">
                    <div style="font-weight: bold; margin-bottom: 4px;">${day}</div>
                    ${eventHTML}
                </div>`;
            }

            // Fill remaining cells
            const totalCells = Math.ceil((daysInMonth + startingDayOfWeek) / 7) * 7;
            const remainingCells = totalCells - (daysInMonth + startingDayOfWeek);

            for (let i = 1; i <= remainingCells; i++) {
                calendarHTML += `<div class="calendar-day other-month">${i}</div>`;
            }


            document.getElementById('calendarGrid').innerHTML = calendarHTML;

        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
        }

        // Initialize calendar
        generateCalendar(currentDate.getFullYear(), currentDate.getMonth());


        // Enable Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

    </script>
</body>

</html>