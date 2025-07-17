<?php
session_start();
include('../db/dbconfig.php');

$currentPage = 'history'; // Set current page for active sidebar item

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: ../studentlogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];
$student_name = $_SESSION['Stu_Name'];

// Get filters from URL
$filter_name = $_GET['name'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? '';
$filter_club = $_GET['club'] ?? '';
$filter_type = $_GET['type'] ?? '';

$results_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $results_per_page;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event History - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="../assets/css/student.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <?php include('../components/header.php'); ?>
    <?php include('../components/offcanvas.php'); ?>
    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-history me-2"></i>Event History
            </h2>
            <p class="page-subtitle">
            </p>
        </div>
        <!-- Filter Section -->
        <?php include('../model/FilteringModal.php'); ?>
    </div>

    <!-- Events Table -->
    <div class="event-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Reference No.</th>
                        <th>Event Date</th>
                        <th>Club Name</th>
                        <th>Event Type</th>
                        <th>View</th>
                        <th>Export Documents</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $completed_events_query = "
                                                SELECT 
                                                e.Ev_ID, e.Ev_Name, e.Ev_Date, c.Club_Name, e.Ev_TypeCode, e.Ev_RefNum, ep.Rep_ID
                                                FROM events e
                                                JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
                                                JOIN eventstatus es ON ep.Status_ID = es.Status_ID
                                                LEFT JOIN club c ON e.Club_ID = c.Club_ID
                                                WHERE e.Stu_ID = '$stu_id' 
                                                AND es.Status_Name = 'Postmortem Approved'";
                    // Filters
                    if (!empty($filter_year)) {
                        $completed_events_query .= " AND YEAR(e.Ev_Date) = '$filter_year'";
                    }
                    if (!empty($filter_month)) {
                        $completed_events_query .= " AND MONTH(e.Ev_Date) = '$filter_month'";
                    }
                    if (!empty($filter_club)) {
                        $completed_events_query .= " AND c.Club_Name = '$filter_club'";
                    }
                    if (!empty($filter_type)) {
                        $completed_events_query .= " AND e.Ev_TypeCode = '$filter_type'";
                    }
                    if (!empty($filter_name)) {
                        $completed_events_query .= " AND e.Ev_Name LIKE '%$filter_name%'";
                    }

                    // ✅ Append LIMIT here
                    $completed_events_query .= " LIMIT $offset, $results_per_page";
                    $result = $conn->query($completed_events_query);
                    if (!$result) {
                        die("Database query failed: " . $conn->error);
                    }

                    // Same filters applied to count query
                    $count_query = "
                                    SELECT COUNT(*) AS total 
                                    FROM events e
                                    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
                                    JOIN eventstatus es ON ep.Status_ID = es.Status_ID
                                    LEFT JOIN club c ON e.Club_ID = c.Club_ID
                                    WHERE e.Stu_ID = '$stu_id' 
                                    AND es.Status_Name = 'Postmortem Approved'";

                    if (!empty($filter_year))
                        $count_query .= " AND YEAR(e.Ev_Date) = '$filter_year'";
                    if (!empty($filter_month))
                        $count_query .= " AND MONTH(e.Ev_Date) = '$filter_month'";
                    if (!empty($filter_club))
                        $count_query .= " AND c.Club_Name = '$filter_club'";
                    if (!empty($filter_type))
                        $count_query .= " AND e.Ev_TypeCode = '$filter_type'";
                    if (!empty($filter_name))
                        $count_query .= " AND e.Ev_Name LIKE '%$filter_name%'";

                    $total_result = $conn->query($count_query);
                    $total_rows = $total_result->fetch_assoc()['total'];
                    $total_pages = ceil($total_rows / $results_per_page);

                    if ($result->num_rows > 0):
                        while ($event = $result->fetch_assoc()):
                            $badgeClass = 'badge-default'; // fallback default
                            switch (strtoupper($event['Ev_TypeCode'])) {
                                case 'CSR':
                                    $badgeClass = 'badge-csr';
                                    break;
                                case 'USR':
                                    $badgeClass = 'badge-usr';
                                    break;
                                case 'SDG':
                                    $badgeClass = 'badge-sdg';
                                    break;
                                // Add more if you ever have new types
                            }

                            ?>
                            <tr>
                                <td><strong><?= $event['Ev_Name'] ?></strong></td>
                                <td><code><?= $event['Ev_RefNum'] ?></code></td>
                                <td><?= date('d M Y', strtotime($event['Ev_Date'])) ?></td>
                                <td><?= $event['Club_Name'] ?></td>
                                <td><span class="event-badge <?= $badgeClass ?>"><?= $event['Ev_TypeCode'] ?></span></td>
                                <td>
                                    <div class="<?= ($loopIndex == 0) ? 'dropup' : 'dropdown' ?>">
                                        <button class="btn view-btn btn-sm dropdown-toggle" type="button"
                                            id="viewDropdown<?= $event['Ev_ID'] ?>" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="viewDropdown<?= $event['Ev_ID'] ?>">
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    onclick="viewProposal(<?= $event['Ev_ID'] ?>)">
                                                    <i class="fas fa-file-alt me-2"></i>View Proposal
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                    onclick="viewPostEvent(<?= $event['Ev_ID'] ?>)">
                                                    <i class="fas fa-file-pdf me-2"></i>View Post Event
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>

                                <!-- Export Documents Column -->
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <a href="../components/pdf/generate_pdf.php?id=<?= $event['Ev_ID'] ?>"
                                            class="btn proposal-btn btn-sm" title="Export Proposal Document">
                                            <i class="fas fa-file-alt"></i> Proposal PDF
                                        </a>
                                        <a href="../components/pdf/reportgeneratepdf.php?rep_id=<?= $event['Rep_ID'] ?>"
                                            class="btn postevent-btn btn-sm" title="Export Post Event Document">
                                            <i class="fas fa-file-pdf"></i> Post Event PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="6" class="no-events">
                                <i class="fas fa-calendar-times"></i>
                                <h5>No events found</h5>
                                <p>Try adjusting your filters or check back later.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php
            $base_url = $_SERVER['PHP_SELF'] . '?';
            $filter_params = $_GET;
            unset($filter_params['page']); // We'll add it in loop
            
            for ($page = 1; $page <= $total_pages; $page++):
                $filter_params['page'] = $page;
                $page_link = $base_url . http_build_query($filter_params);
                $active = ($page == $current_page) ? 'active' : '';
                ?>
                <li class="page-item <?= $active ?>">
                    <a class="page-link" href="<?= htmlspecialchars($page_link) ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProposal(eventId) {
            window.location.href = `../model/viewproposal.php?event_id=${encodeURIComponent(eventId)}`;
        }

        function viewPostEvent(eventId) {
            window.location.href = `../model/viewpostevent.php?event_id=${encodeURIComponent(eventId)}`;
        }
        function resetFilters() {
            window.location.href = window.location.pathname; // clear all filters
        }
    </script>

</body>

</html>