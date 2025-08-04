<?php
session_start();
include('../db/dbconfig.php');

if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

$advisor_id = $_SESSION['Adv_ID'];
$club_id = $_SESSION['Club_ID'];

// Fetch advisor name
$stmt = $conn->prepare("SELECT Adv_Name FROM advisor WHERE Adv_ID = ?");
$stmt->bind_param('s', $advisor_id);
$stmt->execute();
$result = $stmt->get_result();
$advisor_name = $result->fetch_assoc()['Adv_Name'] ?? 'Advisor';

// Fetch ongoing events (exclude postmortem approved = 8)
$query = "
SELECT 
    e.Ev_ID, e.Ev_Name, s.Stu_Name, c.Club_Name, e.Ev_Date,
    es.Status_Name AS Ev_Status, ep.Status_ID AS Post_Status_ID
FROM events e
JOIN student s ON e.Stu_ID = s.Stu_ID
JOIN club c ON e.Club_ID = c.Club_ID
LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
LEFT JOIN eventstatus es ON e.Status_ID = es.Status_ID
WHERE e.Club_ID = ?
  AND es.Status_Name IN (
        'Approved by Advisor (Pending Coordinator Review)',
        'Rejected by Coordinator',
        'Approved by Coordinator'
    )
  AND (
        ep.Status_ID IS NULL 
        OR ep.Status_ID != 8
    )
ORDER BY e.Ev_Date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($events);
$underReview = $ongoing = $rejected = $finished = 0;

foreach ($events as $e) {
    if ($e['Post_Status_ID'] == 6)
        $finished++;
    elseif ($e['Ev_Status'] === 'Approved by Advisor (Pending Coordinator Review)')
        $underReview++;
    elseif ($e['Ev_Status'] === 'Rejected by Coordinator')
        $rejected++;
    elseif ($e['Ev_Status'] === 'Approved by Coordinator')
        $ongoing++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Progress - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/advisor.css?v=<?= time() ?>" rel="stylesheet" />

    <style>
        .main-content {
            padding: 30px;
            margin-top: 20px;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(172, 115, 255, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
        }

        .header-left {
            flex: 1;
        }

        .header-right {
            flex-shrink: 0;
        }
    </style>
</head>

<body>
    <?php include('../components/Advoffcanvas.php'); ?>
    <?php include('../components/Advheader.php'); ?>
    <?php include('../model/LogoutDesign.php'); ?>


    <!-- Main Content -->
    <div class="main-content">

        <!-- Page Header with Event Summary -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tasks me-3" style="font-size: 2.5rem; color: var(--primary-purple)"></i>
                        <div>
                            <h1 class="page-title">🚧 Event Progress</h1>
                            <p class="page-subtitle">
                                Monitor and manage ongoing student events
                            </p>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <div class="summary-section">
                        <div class="event-count">
                            <span class="count-number"><?= $total ?></span>
                            <span class="count-label">Events in Progress</span>
                        </div>
                        <div class="status-indicators">
                            <span class="status-dot dot-under-review"></span>
                            <span class="status-text"><?= $underReview ?> Under Review</span> <span
                                class="status-dot dot-ongoing"></span>
                            <span class="status-text"><?= $ongoing ?> Ongoing</span>
                            <span class="status-dot dot-rejected"></span>
                            <span class="status-text"><?= $rejected ?> Rejected</span>
                            <span class="status-dot dot-finished"></span>
                            <span class="status-text"><?= $finished ?> Finished</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Container -->
        <div class="events-container">
            <!-- Search and Filter -->
            <div class="search-filter">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" class="form-control search-input" id="searchEvents"
                            placeholder="🔍 Search events by name, student, or club..." />
                    </div>
                    <div class="col-md-4">
                        <select class="form-select search-input" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Under Review">Under Review</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Finished">Finished</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Events Table -->
            <div class="table-responsive">
                <table class="table" id="eventsTable">
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Event Name</th>
                            <th>Student</th>
                            <th>Club</th>
                            <th>Event Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTableBody">
                        <?php if (count($events) > 0): ?>
                            <?php foreach ($events as $event): ?>
                                <?php
                                $statusLabel = 'Unknown';
                                $statusClass = 'unknown';
                                $showButtons = true;

                                if ($event['Post_Status_ID'] == 6) {
                                    $statusLabel = 'Event Finish';
                                    $statusClass = 'finish';
                                    $showButtons = false;
                                } elseif ($event['Ev_Status'] === 'Approved by Advisor (Pending Coordinator Review)') {
                                    $statusLabel = 'Under Review';
                                    $statusClass = 'under-review';
                                } elseif ($event['Ev_Status'] === 'Rejected by Coordinator') {
                                    $statusLabel = 'Rejected';
                                    $statusClass = 'rejected';
                                } elseif ($event['Ev_Status'] === 'Approved by Coordinator') {
                                    $statusLabel = 'Ongoing';
                                    $statusClass = 'ongoing';
                                }
                                ?>
                                <tr>
                                    <td><strong><?= $event['Ev_ID'] ?></strong></td>
                                    <td><?= htmlspecialchars($event['Ev_Name']) ?></td>
                                    <td><?= htmlspecialchars($event['Stu_Name']) ?></td>
                                    <td><?= htmlspecialchars($event['Club_Name']) ?></td>
                                    <td><?= date("d M Y", strtotime($event['Ev_Date'])) ?></td>
                                    <td><span class="status-badge status-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                    <td>
                                        <?php if ($showButtons): ?>
                                            <a href="../model/viewProposal.php?id=<?= urlencode($event['Ev_ID']) ?>"
                                                class="btn-action btn-view">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            <a href="../components/pdf/generate_pdf.php?id=<?= $event['Ev_ID'] ?>"
                                                class="btn-action btn-export">
                                                <i class="fas fa-file-pdf me-1"></i>Export
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No ongoing events available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>

            <!-- Empty State (initially hidden) -->
            <div class="empty-state" id="emptyState" style="display: none">
                <i class="fas fa-calendar-times"></i>
                <h4>No Ongoing Events Found</h4>
                <p>
                    There are currently no ongoing events matching your search criteria.
                </p>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--primary-purple); color: white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Event Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventDetailsContent">
                    <!-- Event details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>


        function formatDate(dateString) {
            const options = { year: "numeric", month: "short", day: "numeric" };
            return new Date(dateString).toLocaleDateString("en-US", options);
        }


        function filterEvents() {
            const searchTerm = document.getElementById("searchEvents").value.toLowerCase();
            const statusFilter = document.getElementById("statusFilter").value.toLowerCase();
            const rows = document.querySelectorAll("#eventsTableBody tr");

            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.querySelector(".status-badge")?.textContent.toLowerCase() || "";

                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesStatus) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            // Show/hide empty state
            const emptyState = document.getElementById("emptyState");
            if (visibleCount === 0) {
                emptyState.style.display = "block";
            } else {
                emptyState.style.display = "none";
            }
        }

        // Event listeners
        document.getElementById("searchEvents").addEventListener("input", filterEvents);
        document.getElementById("statusFilter").addEventListener("change", filterEvents);


    </script>
</body>

</html>