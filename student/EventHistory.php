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

    <style>
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-purple);
        }

        .filter-btn {
            background: var(--primary-purple);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .reset-btn {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .reset-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .event-table {
            background: white;
            border-radius: 15px;
            overflow: visible !important;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: var(--primary-purple);
            color: white;
            font-weight: 600;
            padding: 15px;
            border: none;
            text-align: center;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: #f8f9ff;
        }

        .event-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-academic {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-cultural {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-sports {
            background: #e8f5e8;
            color: #388e3c;
        }

        .badge-workshop {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-seminar {
            background: #fce4ec;
            color: #c2185b;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 2px;
        }



        .export-btn {
            background: #dc3545;
            color: white;
        }

        .export-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .page-title {
            color: var(--primary-purple);
            font-weight: bold;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #666;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-ongoing {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        .page-link {
            color: var(--primary-purple);
            border: 1px solid var(--primary-purple);
            margin: 0 2px;
            border-radius: 8px;
        }

        .page-link:hover {
            background: var(--primary-purple);
            color: white;
        }

        .page-item.active .page-link {
            background: var(--primary-purple);
            border-color: var(--primary-purple);
        }

        .search-input {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.2rem rgba(172, 115, 255, 0.25);
        }

        .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
        }

        .form-select:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.2rem rgba(172, 115, 255, 0.25);
        }

        .no-events {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .no-events i {
            font-size: 4rem;
            color: var(--primary-purple);
            margin-bottom: 20px;
        }

        .view-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background: #138496;
            color: white;
            transform: translateY(-2px);
        }

        /* Dropdown menu styles for view button */
        .dropdown-menu {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            min-width: 180px;
            z-index: 1050 !important;
        }

        .dropdown-item {
            padding: 10px 15px;
            font-size: 0.9rem;
            color: #495057;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary-purple);
        }

        .dropdown-item i {
            color: #6c757d;
        }

        .dropdown-item:hover i {
            color: var(--primary-purple);
        }

        /* Proposal Button Styles */
        .proposal-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .proposal-btn:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
        }

        /* Post Event Button Styles */
        .postevent-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .postevent-btn:hover {
            background: #c82333;
            color: white;
            transform: translateY(-2px);
        }

        /* Column width adjustments */
        .table th:nth-child(6) {
            /* View column */
            width: 100px;
            text-align: center;
        }

        .table th:nth-child(7) {
            /* Export Documents column */
            width: 150px;
            text-align: center;
        }

        .table td:nth-child(6),
        .table td:nth-child(7) {
            text-align: center;
            vertical-align: middle;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {

            .table th:nth-child(6),
            .table th:nth-child(7) {
                font-size: 0.9rem;
            }

            .proposal-btn,
            .postevent-btn {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
        }

        .table-responsive {
            overflow: visible !important;
            padding-top: 30px;
            /* add space so dropdown can open downward */
        }


        .table tbody tr {
            position: relative;
            z-index: 1;
        }
    </style>
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
        e.Ev_ID, 
        e.Ev_Name, 
        e.Ev_Date, 
        c.Club_Name, 
        e.Ev_TypeCode, 
        e.Ev_RefNum, 
        ep.Rep_ID
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    JOIN eventstatus es ON ep.Status_ID = es.Status_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    WHERE e.Stu_ID = '$stu_id' 
      AND es.Status_Name = 'Postmortem Approved'
";

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
      AND es.Status_Name = 'Postmortem Approved'
";

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
                            $badgeClass = 'badge-academic';
                            switch (strtolower($event['Ev_TypeCode'])) {
                                case 'sports':
                                    $badgeClass = 'badge-sports';
                                    break;
                                case 'cultural':
                                    $badgeClass = 'badge-cultural';
                                    break;
                                case 'workshop':
                                    $badgeClass = 'badge-workshop';
                                    break;
                                case 'seminar':
                                    $badgeClass = 'badge-seminar';
                                    break;
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