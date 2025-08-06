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
LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID AND ep.Status_ID = 6
WHERE e.Status_ID = 5 AND ep.Ev_ID IS NULL
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Coordinator Dashboard - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/coordinator.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">


            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="stats-card events">
                        <i class="fas fa-calendar-alt fa-3x mb-3" style="color: var(--primary-color)"></i>
                        <div class="stats-number" style="color: var(--primary-color)">
                            <?= $totalEvents ?>
                        </div>

                        <div class="stats-label">Total Events</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stats-card clubs">
                        <i class="fas fa-users fa-3x mb-3" style="color: var(--secondary-color)"></i>
                        <div class="stats-number" style="color: var(--primary-color)">
                            <?= $totalClubs ?>
                        </div>
                        <div class="stats-label">Total Clubs</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Poster Carousel -->
                    <div class="dashboard-card">
                        <?php include('../components/carousel.php'); ?>
                    </div>

                    <!-- Graph Panel -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar"></i>
                            Event Statistics
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

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">Are you sure you want to log out?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger">Log Out</button>
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