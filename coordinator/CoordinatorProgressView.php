<?php
session_start();
include('../db/dbconfig.php');

if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$stmt = $conn->prepare("SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?");
$stmt->bind_param('s', $coordinator_id);
$stmt->execute();
$result = $stmt->get_result();
$coordinator_name = $result->fetch_assoc()['Coor_Name'] ?? 'Coordinator';
$typeFilter = $_GET['type'] ?? '';
$clubFilter = $_GET['club'] ?? '';
$query = "
    SELECT 
        e.Ev_ID, e.Ev_Name, s.Stu_Name, c.Club_Name, e.Ev_Date, e.Ev_TypeCode,
        e.Status_ID, es.Status_Name
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    LEFT JOIN eventstatus es_post ON ep.Status_ID = es_post.Status_ID
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    WHERE es.Status_Name IN ('Pending Coordinator Review', 'Approved by Coordinator')
      AND (
          ep.Status_ID IS NULL OR 
          es_post.Status_Name NOT IN ('Postmortem Pending Review', 'Postmortem Approved')
      )
";

// Add filter conditions
if (!empty($typeFilter)) {
    $query .= " AND e.Ev_TypeCode = '" . $conn->real_escape_string($typeFilter) . "'";
}

if (!empty($clubFilter)) {
    $query .= " AND c.Club_Name = '" . $conn->real_escape_string($clubFilter) . "'";
}


$stmt = $conn->prepare($query);
$stmt->execute();
$proposals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);



$clubs_result = $conn->query("SELECT Club_Name FROM club ORDER BY Club_Name");
$clubs = [];
while ($row = $clubs_result->fetch_assoc()) {
    $clubs[] = $row['Club_Name'];
}

$type_result = $conn->query("SELECT DISTINCT Type_Code FROM eventtyperef ORDER BY Type_Code");
$eventTypes = [];
while ($row = $type_result->fetch_assoc()) {
    $eventTypes[] = $row['Type_Code'];
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ongoing Events - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/css/dataTables.bootstrap5.min.css"
        rel="stylesheet" />
    <link href="../assets/css/coordinator.css?v=<?= time() ?>" rel="stylesheet" />
    <style>
        /* Main Content Styles */
        .main-content {
            margin-left: 0;
            padding: 20px;
            min-height: calc(100vh - 76px);
        }

        .event-type-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            color: white;
        }

        .event-type-badge.CSR {
            background-color: #FFB300;
        }

        .event-type-badge.USR {
            background-color: #1976D2;
        }

        .event-type-badge.SDG {
            background-color: #388E3C;
        }
    </style>
</head>

<body>

    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-calendar-check me-3"></i>Ongoing Events</h1>
                    <p>Manage and monitor all currently active events</p>
                </div>
            </div>

            <!-- Filter Button -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-calendar-check me-2 text-primary"></i>Ongoing
                            Events List
                        </h5>
                        <small class="text-muted">Manage and monitor all currently active events</small>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i>Filter Events
                        </button>
                    </div>
                </div>

                <!-- Events Table -->
                <div class="table-container">
                    <table class="table table-hover" id="ongoingEventsTable">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Event Name</th>
                                <th>Student Name</th>
                                <th>Club Name</th>
                                <th>Event Type</th>
                                <th>Event Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($proposals) > 0): ?>
                                <?php foreach ($proposals as $proposal): ?>
                                    <tr>
                                        <td><strong><?php echo $proposal['Ev_ID']; ?></strong></td>
                                        <td><?php echo $proposal['Ev_Name']; ?></td>
                                        <td><?php echo $proposal['Stu_Name']; ?></td>
                                        <td><?php echo $proposal['Club_Name']; ?></td>
                                        <td>
                                            <span class="event-type-badge <?php echo $proposal['Ev_TypeCode']; ?>">
                                                <?php echo $proposal['Ev_TypeCode']; ?>
                                            </span>


                                        </td>
                                        <td><?php echo $proposal['Ev_Date']; ?></td>
                                        <td>
                                            <span class="status-badge status-ongoing">Ongoing</span>
                                        </td>
                                        <td>
                                            <a href="view-event-details.php?id=<?php echo $proposal['Ev_ID']; ?>"
                                                class="btn btn-info btn-sm me-1" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../components/pdf/generate_pdf.php?id=<?php echo $proposal['Ev_ID']; ?>"
                                                class="btn btn-success btn-sm" title="Export Event">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No ongoing events found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="
              background: linear-gradient(
                135deg,
                var(--primary-color),
                var(--secondary-color)
              );
              color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-filter me-2"></i>Filter Events
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="eventTypeFilter" class="form-label fw-semibold">
                                <i class="fas fa-tag me-2 text-primary"></i>Event Type
                            </label>
                            <select class="form-select" id="eventTypeFilter">
                                <option value="">All Event Types</option>
                                <?php foreach ($eventTypes as $eventType): ?>
                                    <option value="<?php echo $eventType; ?>" <?php echo ($typeFilter == $eventType) ? 'selected' : ''; ?>>
                                        <?php echo $eventType; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="clubFilter" class="form-label fw-semibold">
                                <i class="fas fa-users me-2 text-primary"></i>Club
                            </label>
                            <select class="form-select" id="clubFilter">
                                <option value="">All Clubs</option>
                                <?php foreach ($clubs as $clubName): ?>
                                    <option value="<?php echo $clubName; ?>" <?php echo ($clubFilter == $clubName) ? 'selected' : ''; ?>>
                                        <?php echo $clubName; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()"
                        data-bs-dismiss="modal">
                        <i class="fas fa-refresh me-2"></i>Clear All
                    </button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- jQuery FIRST -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <!-- Bootstrap (AFTER jQuery) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables core (AFTER jQuery) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Bootstrap 5 integration -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.6/js/dataTables.bootstrap5.min.js"></script>


    <script>
        // Initialize DataTable
        let table;

        $(document).ready(function () {
            table = $("#ongoingEventsTable").DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, "asc"]],
                language: {
                    search: "Search Events:",
                    lengthMenu: "Show _MENU_ events per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ ongoing events",
                    emptyTable: "No ongoing events found",
                },
            });
        });

        // Apply Filters Function
        function applyFilters() {
            const eventType = document.getElementById("eventTypeFilter").value;
            const club = document.getElementById("clubFilter").value;

            window.location.href = `CoordinatorProgressView.php?type=${encodeURIComponent(eventType)}&club=${encodeURIComponent(club)}`;
        }


        // Clear Filters Function
        function clearFilters() {
            document.getElementById("eventTypeFilter").value = "";
            document.getElementById("clubFilter").value = "";
            table.columns().search("").draw();
            showToast("All filters cleared!", "info");
        }

        // Export Single Event Function


        // Toast Notification Function
        function showToast(message, type = "info") {
            // Create toast element
            const toast = document.createElement("div");
            toast.className = `alert alert-${type === "success" ? "success" : type === "error" ? "danger" : "info"
                } position-fixed`;
            toast.style.cssText = `
                top: 20px; 
                right: 20px; 
                z-index: 9999;
                min-width: 300px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === "success"
                    ? "check-circle"
                    : type === "error"
                        ? "exclamation-circle"
                        : "info-circle"
                } me-2"></i>
                    ${message}
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 3000);
        }

        // Search functionality enhancement
        $("#ongoingEventsTable_filter input").attr(
            "placeholder",
            "Search events, students, or clubs..."
        );

        // Add smooth animations
        $(document).ready(function () {
            $(".content-card").hide().fadeIn(800);
            $(".page-header").hide().slideDown(600);
        });


    </script>
</body>

</html>