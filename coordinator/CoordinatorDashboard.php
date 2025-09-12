<?php
session_start();
include('../db/dbconfig.php');
$currentPage = 'dashboard';
if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$stmt = $conn->prepare("SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?");
$stmt->bind_param("s", $coordinator_id);
$stmt->execute();
$result = $stmt->get_result();
$coordinator_name = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['Coor_Name'] : "Coordinator";


// Total Completed Events (Postmortem Approved)
$totalEventsQuery = "
SELECT COUNT(*) AS total
FROM eventpostmortem
WHERE Status_ID = 8
";
$totalEvents = $conn->query($totalEventsQuery)->fetch_assoc()['total'] ?? 0;

// Total Clubs
$totalClubsQuery = "SELECT COUNT(*) AS total FROM club";
$totalClubs = $conn->query($totalClubsQuery)->fetch_assoc()['total'] ?? 0;

$notifications = [];

// Proposal Notifications (Status_ID = 3)
$proposalNotify = "
SELECT e.Ev_ID, e.Ev_Name, c.Club_Name, e.created_at
FROM events e
JOIN club c ON e.Club_ID = c.Club_ID
WHERE e.Status_ID = 3
ORDER BY e.created_at DESC
";
$resultProposal = $conn->query($proposalNotify);
while ($row = $resultProposal->fetch_assoc()) {
    $notifications[] = [
        'type' => 'proposal',
        'event' => $row['Ev_Name'],
        'club' => $row['Club_Name'],
        'time' => time_elapsed_string($row['created_at']),
        'link' => "CoorProposalDecision.php?type=proposal&id=" . $row['Ev_ID']
    ];
}

// Post-Event Notifications (Status_ID = 6)
$postEventNotify = "
SELECT ep.Rep_ID, e.Ev_Name, c.Club_Name, ep.created_at
FROM eventpostmortem ep
JOIN events e ON ep.Ev_ID = e.Ev_ID
JOIN club c ON e.Club_ID = c.Club_ID
WHERE ep.Status_ID = 6
ORDER BY ep.created_at DESC
";
$resultPost = $conn->query($postEventNotify);
while ($row = $resultPost->fetch_assoc()) {
    $notifications[] = [
        'type' => 'post-event',
        'event' => $row['Ev_Name'],
        'club' => $row['Club_Name'],
        'time' => time_elapsed_string($row['created_at']),
        'link' => "CoorPostDecision.php?type=postmortem&id=" . $row['Rep_ID']
    ];
}

// Helper function
function time_elapsed_string($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60)
        return 'Just now';
    $intervals = [
        31536000 => 'year',
        2592000 => 'month',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
    ];
    foreach ($intervals as $secs => $str) {
        $d = floor($diff / $secs);
        if ($d > 0) {
            return "$d $str" . ($d > 1 ? 's' : '') . " ago";
        }
    }
    return 'Just now';
}

$calendarData = [];

$calendarQuery = "
SELECT e.Ev_Name, e.Ev_Date, c.Club_Name
FROM events e
JOIN club c ON e.Club_ID = c.Club_ID
LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
WHERE e.Status_ID = 5 AND (ep.Ev_ID IS NULL OR ep.Status_ID != 8)
";

$calendarResult = $conn->query($calendarQuery);
while ($row = $calendarResult->fetch_assoc()) {
    $dateKey = date('Y-n-j', strtotime($row['Ev_Date']));
    $calendarData[$dateKey] = [
        'name' => $row['Ev_Name'],
        'club' => $row['Club_Name']
    ];
}

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

$event_summary_query = "
SELECT 
    e.Ev_ID,
    e.Ev_Name, 
    e.Ev_Date,
    COALESCE(eps.Status_Name, es.Status_Name) as Status_Name,
    c.Club_Name
FROM events e
JOIN eventstatus es ON e.Status_ID = es.Status_ID
JOIN club c ON e.Club_ID = c.Club_ID
LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
LEFT JOIN eventstatus eps ON ep.Status_ID = eps.Status_ID
WHERE (e.Status_ID IN (3, 4, 5) AND (ep.Status_ID IS NULL OR ep.Status_ID != 8))
   OR (ep.Status_ID IN (6, 7))
ORDER BY e.created_at DESC
LIMIT 8
";

$event_summary_result = $conn->query($event_summary_query);
$event_summary = [];
while ($row = $event_summary_result->fetch_assoc()) {
    $event_summary[] = $row;
}
// Get completed events count by event type (Status_ID = 8 means Postmortem Approved)
$eventTypeStats = [];

$eventTypeQuery = "
SELECT 
    e.Ev_TypeCode,
    COUNT(*) as total_completed
FROM events e
JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
WHERE ep.Status_ID = 8
GROUP BY e.Ev_TypeCode
ORDER BY e.Ev_TypeCode
";

$eventTypeResult = $conn->query($eventTypeQuery);
while ($row = $eventTypeResult->fetch_assoc()) {
    $eventTypeStats[$row['Ev_TypeCode']] = $row['total_completed'];
}

$sdgCount = isset($eventTypeStats['SDG']) ? $eventTypeStats['SDG'] : 0;
$csrCount = isset($eventTypeStats['CSR']) ? $eventTypeStats['CSR'] : 0;
$usrCount = isset($eventTypeStats['USR']) ? $eventTypeStats['USR'] : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Coordinator Dashboard - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/coordinator/dashboard.css?v=<?= time() ?>" rel="stylesheet" />
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">

            <!-- Dashboard Content -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Poster Carousel -->
                    <div class="dashboard-card carousel-container">
                        <?php include('../components/carousel.php'); ?>
                    </div>

                    <!-- Graph Panel -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i>
                            Event Statistics (still in Development)
                        </div>
                        <div class="graph-controls">
                            <select id="monthFilter">
                                <option value="">Select Month</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <select id="yearFilter">
                                <option value="">Select Year</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                            </select>
                            <select id="eventTypeFilter">
                                <option value="">Select Event Type</option>
                                <option value="academic">Academic</option>
                                <option value="cultural">Cultural</option>
                                <option value="sports">Sports</option>
                                <option value="social">Social</option>
                            </select>
                            <select id="clubFilter">
                                <option value="">Filter by Club</option>
                                <option value="cs">Computer Science Club</option>
                                <option value="cultural">Cultural Society</option>
                                <option value="sports">Sports Club</option>
                                <option value="debate">Debate Society</option>
                            </select>
                        </div>
                        <div class="graph-container">
                            <canvas id="eventChart"></canvas>
                        </div>
                    </div>
                    <div class="dashboard-card future-placeholder">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i>
                            Coming Soon
                        </div>
                        <div class="placeholder-content">
                            <div class="placeholder-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h4>Analytics Dashboard</h4>
                            <p class="placeholder-text">
                                Advanced event analytics and insights will be available here in future updates.
                            </p>
                            <div class="feature-list">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    Event Performance Metrics
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    Club Activity Analytics
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    Attendance Tracking
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Notification Panel -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-bell"></i>
                            Recent Notifications
                        </div>
                        <?php foreach ($notifications as $n): ?>
                            <div class="notification-item <?= $n['type'] ?>">
                                <div class="notification-content">
                                    <div class="notification-info">
                                        <strong><?= htmlspecialchars($n['event']) ?></strong>
                                        <p class="mb-1 small">
                                            Club: <?= htmlspecialchars($n['club']) ?>
                                        </p>
                                        <small class="text-muted">
                                            <?= $n['type'] === 'proposal' ? 'Proposal' : 'Post-Event' ?> • <?= $n['time'] ?>
                                        </small>
                                    </div>
                                    <div class="notification-actions">
                                        <span
                                            class="notification-badge <?= $n['type'] === 'proposal' ? 'badge-proposal' : 'badge-post-event' ?>">
                                            <?= $n['type'] === 'proposal' ? 'Proposal' : 'Post-Event' ?>
                                        </span>
                                        <a class="view-btn" href="<?= $n['link'] ?>">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </div>


                    <!-- Event Summary Panel for Coordinator Dashboard -->
                    <div class="dashboard-card">
                        <div class="event-summary-header">
                            <h4 class="section-title">
                                <i class="fas fa-list-ul me-2"></i>
                                Event Summary
                            </h4>
                            <a href="CoordinatorProgressView.php" class="view-btn">
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
                                                    <?= htmlspecialchars($event['Club_Name']) ?> •
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
                                                case 'Postmortem Approved':
                                                    $status_class = 'status-completed';
                                                    $status_short = 'Completed';
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
                    <!-- Event Type Statistics Panel -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie"></i>
                            Event Type Statistics
                        </div>
                        <div class="event-type-stats">
                            <div class="stat-item sdg-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-globe-americas"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?= $sdgCount ?></div>
                                    <div class="stat-label">SDG Events</div>
                                </div>
                            </div>

                            <div class="stat-item csr-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?= $csrCount ?></div>
                                    <div class="stat-label">CSR Events</div>
                                </div>
                            </div>

                            <div class="stat-item usr-stat">
                                <div class="stat-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?= $usrCount ?></div>
                                    <div class="stat-label">USR Events</div>
                                </div>
                            </div>
                        </div>

                        <div class="total-summary">
                            <div class="total-line">
                                <span class="total-label">Total Completed:</span>
                                <span class="total-count"><?= ($sdgCount + $csrCount + $usrCount) ?></span>
                            </div>
                        </div>
                    </div>
                    <!-- Calendar View -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-calendar"></i>
                            Event Calendar
                        </div>
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <button class="calendar-nav" id="prevMonth">‹</button>
                                <h5 id="currentMonth">July 2025</h5>
                                <button class="calendar-nav" id="nextMonth">›</button>
                            </div>
                            <div class="calendar-grid" id="calendarGrid">
                                <!-- Calendar will be generated by JavaScript -->
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- Event Details Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Event details will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Details Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalTitle">
                        Notification Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="notificationModalBody">
                    <!-- Notification details will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="button" class="btn" style="background: var(--primary-color); color: white"
                        id="approveBtn">
                        Approve
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="rejectBtn">
                        Reject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade poster-modal" id="posterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Poster</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="posterModalImage" src="" alt="Event Poster" class="poster-image">
                    <div class="event-details">
                        <div class="event-name" id="posterEventName"></div>
                        <div class="event-date" id="posterEventDate">
                            <i class="fas fa-calendar-alt"></i>
                            <span></span>
                        </div>
                        <div class="event-club" id="posterEventClub">
                            <i class="fas fa-users"></i>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>

        // Chart.js Setup with standardized dimensions
        const ctx = document.getElementById("eventChart").getContext("2d");
        const eventChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "May",
                    "Jun",
                    "Jul",
                    "Aug",
                    "Sep",
                    "Oct",
                    "Nov",
                    "Dec",
                ],
                datasets: [
                    {
                        label: "Events",
                        data: [3, 5, 2, 8, 4, 6, 7, 5, 9, 6, 4, 8],
                        backgroundColor: "#0ABAB5",
                        borderColor: "#56DFCF",
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: "top",
                        labels: {
                            font: {
                                size: 14,
                                weight: "bold",
                            },
                            color: "#333",
                        },
                    },
                    tooltip: {
                        backgroundColor: "rgba(10, 186, 181, 0.9)",
                        titleColor: "white",
                        bodyColor: "white",
                        borderColor: "#56DFCF",
                        borderWidth: 1,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: "#e0e0e0",
                        },
                        ticks: {
                            font: {
                                size: 12,
                            },
                            color: "#666",
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            font: {
                                size: 12,
                            },
                            color: "#666",
                        },
                    },
                },
                elements: {
                    bar: {
                        backgroundColor: "#0ABAB5",
                    },
                },
            },
        });

        // Filter functionality
        document.querySelectorAll(".graph-controls select").forEach((select) => {
            select.addEventListener("change", updateChart);
        });

        function updateChart() {
            // This would typically fetch new data based on filters
            const randomData = Array.from(
                { length: 7 },
                () => Math.floor(Math.random() * 10) + 1
            );
            eventChart.data.datasets[0].data = randomData;
            eventChart.update();
        }

        // Calendar functionality
        const currentDate = new Date();
        let currentCalendarMonth = currentDate.getMonth();
        let currentCalendarYear = currentDate.getFullYear();

        const eventDates = <?= json_encode($calendarData); ?>;

        function generateCalendar(month, year) {
            const monthNames = [
                "January",
                "February",
                "March",
                "April",
                "May",
                "June",
                "July",
                "August",
                "September",
                "October",
                "November",
                "December",
            ];
            const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

            document.getElementById(
                "currentMonth"
            ).textContent = `${monthNames[month]} ${year}`;

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            let calendarHTML = "";

            // Day headers
            dayNames.forEach((day) => {
                calendarHTML += `<div class="day-header">${day}</div>`;
            });

            // Empty cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += '<div class="calendar-day"></div>';
            }

            // Days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateKey = `${year}-${month + 1}-${day}`;
                const hasEvent = eventDates[dateKey];
                const eventClass = hasEvent ? "has-event" : "";

                calendarHTML += `
                    <div class="calendar-day ${eventClass}" onclick="showEventDetails('${dateKey}')">
                        <div class="day-number">${day}</div>
                    </div>
                `;
            }

            document.getElementById("calendarGrid").innerHTML = calendarHTML;
        }

        function showEventDetails(dateKey) {
            const event = eventDates[dateKey];
            if (event) {
                document.getElementById("eventModalTitle").textContent = event.name;
                document.getElementById("eventModalBody").innerHTML = `
                    <p><strong>Event:</strong> ${event.name}</p>
                    <p><strong>Club:</strong> ${event.club}</p>
                    <p><strong>Date:</strong> ${dateKey}</p>
                `;
                new bootstrap.Modal(document.getElementById("eventModal")).show();
            }
        }

        // Calendar navigation
        document.getElementById("prevMonth").addEventListener("click", () => {
            currentCalendarMonth--;
            if (currentCalendarMonth < 0) {
                currentCalendarMonth = 11;
                currentCalendarYear--;
            }
            generateCalendar(currentCalendarMonth, currentCalendarYear);
        });

        document.getElementById("nextMonth").addEventListener("click", () => {
            currentCalendarMonth++;
            if (currentCalendarMonth > 11) {
                currentCalendarMonth = 0;
                currentCalendarYear++;
            }
            generateCalendar(currentCalendarMonth, currentCalendarYear);
        });

        // Initialize calendar
        generateCalendar(currentCalendarMonth, currentCalendarYear);

        // Enhanced calendar with event indicators
        function updateCalendarWithEvents() {
            const eventDays = document.querySelectorAll(".calendar-day.has-event");
            eventDays.forEach((day) => {
                day.style.position = "relative";
                day.style.cursor = "pointer";

                day.addEventListener("mouseenter", function () {
                    this.style.transform = "scale(1.05)";
                    this.style.zIndex = "10";
                    this.style.boxShadow = "0 5px 15px rgba(0,0,0,0.2)";
                });

                day.addEventListener("mouseleave", function () {
                    this.style.transform = "scale(1)";
                    this.style.zIndex = "1";
                    this.style.boxShadow = "none";
                });
            });
        }

        // Initialize all components when page loads
        document.addEventListener("DOMContentLoaded", function () {
            updateCalendarWithEvents();

            // Add smooth scrolling to dashboard sections
            document.querySelectorAll(".sidebar-item").forEach((item) => {
                item.addEventListener("click", function () {
                    // Close offcanvas on mobile
                    const offcanvas = bootstrap.Offcanvas.getInstance(
                        document.getElementById("sidebar")
                    );
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                });
            });
        });

        // Dark mode toggle (bonus feature)
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
            localStorage.setItem(
                "darkMode",
                document.body.classList.contains("dark-mode")
            );
        }

        // Load dark mode preference
        if (localStorage.getItem("darkMode") === "true") {
            document.body.classList.add("dark-mode");
        }
    </script>
</body>

</html>