<?php
session_start();
include('../db/dbconfig.php');
$currentPage = 'history';
if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$query_name = "SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?";
$stmt_name = $conn->prepare($query_name);
$stmt_name->bind_param("s", $coordinator_id);
$stmt_name->execute();
$result_name = $stmt_name->get_result();
$coordinator_name = ($result_name->num_rows > 0) ? $result_name->fetch_assoc()['Coor_Name'] : "Coordinator";

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Base query
$query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_RefNum, e.Ev_TypeCode, e.Ev_Date, c.Club_Name
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    WHERE ep.Status_ID = 8
";

$count_query = "
    SELECT COUNT(*) as total
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    WHERE ep.Status_ID = 8
";

$params = [];
$types = "";
$filters = "";

// Search functionality
if (!empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $filters .= " AND (e.Ev_Name LIKE ? OR e.Ev_RefNum LIKE ? OR c.Club_Name LIKE ?)";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= "sss";
}

// Filter functionality
if (!empty($_GET['type'])) {
    $filters .= " AND e.Ev_TypeCode = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}
if (!empty($_GET['club'])) {
    $filters .= " AND c.Club_Name = ?";
    $params[] = $_GET['club'];
    $types .= "s";
}
if (!empty($_GET['year'])) {
    $filters .= " AND YEAR(e.Ev_Date) = ?";
    $params[] = $_GET['year'];
    $types .= "s";
}
if (!empty($_GET['month'])) {
    $filters .= " AND MONTH(e.Ev_Date) = ?";
    $params[] = $_GET['month'];
    $types .= "s";
}

// Apply filters to both queries
$query .= $filters;
$count_query .= $filters;

// Get total count for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add ordering and pagination to main query
$query .= " ORDER BY e.Ev_Date DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Count total approved events (Status_ID = 8 in eventpostmortem)
$totalEvents = 0;
$stmt1 = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM events e
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE ep.Status_ID = 8
");
$stmt1->execute();
$res1 = $stmt1->get_result();
if ($row = $res1->fetch_assoc()) {
    $totalEvents = $row['total'];
}

// Count total clubs from club table (ALL CLUBS)
$totalClubs = 0;
$stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM club");
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($row = $res2->fetch_assoc()) {
    $totalClubs = $row['total'];
}

// Count total committee members (Com_IDs) from approved events
$totalStudents = 0;
$stmt3 = $conn->prepare("
    SELECT COUNT(*) as total
    FROM committee cm
    JOIN events e ON cm.Ev_ID = e.Ev_ID
    JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE ep.Status_ID = 8
");
$stmt3->execute();
$res3 = $stmt3->get_result();
if ($row = $res3->fetch_assoc()) {
    $totalStudents = $row['total'];
}

$club_query = $conn->query("SELECT Club_Name FROM club ORDER BY Club_Name");
$club_options = [];
while ($row = $club_query->fetch_assoc()) {
    $club_options[] = $row['Club_Name'];
}

// Calculate pagination info
$showing_from = $total_records > 0 ? $offset + 1 : 0;
$showing_to = min($offset + $records_per_page, $total_records);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event History - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/coordinator2.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-dark mb-1">
                    <i class="fas fa-history me-2" style="color: var(--primary-color)"></i>
                    Event History
                </h2>
                <p class="text-muted mb-0">View and manage all past events</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #3498db, #2980b9)">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="stats-number" id="totalEvents"><?= number_format($totalEvents) ?></h3>
                            <p class="stats-label">Total Events</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #27ae60, #229954)">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="stats-number" id="totalClubs"><?= number_format($totalClubs) ?></h3>
                            <p class="stats-label">Total Clubs</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b)">
                            <i class="fas fa-user-graduate text-white"></i>
                        </div>
                        <div class="ms-3">
                            <h3 class="stats-number" id="totalStudents"><?= number_format($totalStudents) ?></h3>
                            <p class="stats-label">Students Participated</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-7 mb-3 mb-md-0">
                        <form method="GET" action="" id="searchForm" class="d-flex">
                            <!-- Preserve existing filters -->
                            <?php foreach (['type', 'club', 'year', 'month', 'page'] as $field): ?>
                                <?php if (!empty($_GET[$field]) && $field !== 'search'): ?>
                                    <input type="hidden" name="<?= $field ?>" value="<?= htmlspecialchars($_GET[$field]) ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <div class="search-container flex-grow-1">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" name="search" id="searchInput"
                                    placeholder="Search events by name, club, or reference number..."
                                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" />
                            </div>
                            <button type="submit" class="btn btn-primary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($_GET['search'])): ?>
                                <a href="?" class="btn btn-outline-secondary ms-1" title="Clear search">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-lg-4 col-md-5 text-end">
                        <button class="btn filter-btn" type="button" data-bs-toggle="modal"
                            data-bs-target="#filterModal">
                            <i class="fas fa-filter me-2"></i>
                            Advanced Filters
                            <?php
                            $active_filters = 0;
                            foreach (['type', 'club', 'year', 'month'] as $filter) {
                                if (!empty($_GET[$filter]))
                                    $active_filters++;
                            }
                            if ($active_filters > 0): ?>
                                <span class="badge bg-danger ms-1"><?= $active_filters ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Events Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover" id="eventsTable">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Reference Number</th>
                                <th>Event Name</th>
                                <th>Club Name</th>
                                <th>Event Type</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="eventsTableBody">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                    $type = $row['Ev_TypeCode'];
                                    $badgeClass = match ($type) {
                                        'SDG' => 'badge-sdg',
                                        'USR' => 'badge-usr',
                                        'CSR' => 'badge-csr',
                                        default => 'badge-sdg'
                                    };
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['Ev_ID']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['Ev_RefNum']) ?></td>
                                        <td><?= htmlspecialchars($row['Ev_Name']) ?></td>
                                        <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                        <td><span class="event-type-badge <?= $badgeClass ?>"><?= $type ?></span></td>
                                        <td><?= date('d M Y', strtotime($row['Ev_Date'])) ?></td>
                                        <td>
                                            <button class="btn action-btn btn-view-proposal"
                                                onclick="window.open('viewProposal.php?event_id=<?= urlencode($row['Ev_ID']) ?>', '_blank')"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="View Proposal">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <button class="btn action-btn btn-view-post"
                                                onclick="window.open('viewPostEvent.php?event_id=<?= urlencode($row['Ev_ID']) ?>', '_blank')"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="View Post Event">
                                                <i class="fas fa-chart-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <?php if (!empty($_GET['search']) || !empty($_GET['type']) || !empty($_GET['club']) || !empty($_GET['year']) || !empty($_GET['month'])): ?>
                                            <i class="fas fa-search fa-2x mb-3 d-block"></i>
                                            No events found matching your search criteria.
                                            <br>
                                            <a href="?" class="btn btn-sm btn-outline-primary mt-2">Clear all filters</a>
                                        <?php else: ?>
                                            <i class="fas fa-calendar-times fa-2x mb-3 d-block"></i>
                                            No completed events found.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_records > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Showing <span id="showingFrom"><?= $showing_from ?></span> to
                        <span id="showingTo"><?= $showing_to ?></span> of
                        <span id="totalRecords"><?= $total_records ?></span> entries
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination mb-0">
                                <!-- Previous Button -->
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="<?= $current_page > 1 ? '?' . http_build_query(array_merge($_GET, ['page' => $current_page - 1])) : '#' ?>"
                                        style="border-radius: 10px 0 0 10px">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <?php
                                // Calculate page range to show
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                // Show first page if not in range
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                            <?= $i == $current_page ? 'style="background: var(--primary-color); border-color: var(--primary-color);"' : '' ?>>
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Show last page if not in range -->
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                            href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next Button -->
                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                        href="<?= $current_page < $total_pages ? '?' . http_build_query(array_merge($_GET, ['page' => $current_page + 1])) : '#' ?>"
                                        style="border-radius: 0 10px 10px 0">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-filter me-2"></i>
                        Advanced Filters
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm" method="GET" action="">
                        <!-- Preserve search term -->
                        <?php if (!empty($_GET['search'])): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="eventTypeFilter" class="form-label">Event Type</label>
                                <select class="form-select" id="eventTypeFilter" name="type">
                                    <option value="">All Event Types</option>
                                    <option value="SDG" <?= ($_GET['type'] ?? '') === 'SDG' ? 'selected' : '' ?>>SDG
                                    </option>
                                    <option value="USR" <?= ($_GET['type'] ?? '') === 'USR' ? 'selected' : '' ?>>USR
                                    </option>
                                    <option value="CSR" <?= ($_GET['type'] ?? '') === 'CSR' ? 'selected' : '' ?>>CSR
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clubFilter" class="form-label">Club</label>
                                <select class="form-select" id="clubFilter" name="club">
                                    <option value="">All Clubs</option>
                                    <?php foreach ($club_options as $club): ?>
                                        <option value="<?= htmlspecialchars($club) ?>" <?= ($_GET['club'] ?? '') === $club ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($club) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="yearFilter" class="form-label">Year</label>
                                <select class="form-select" id="yearFilter" name="year">
                                    <option value="">All Years</option>
                                    <?php
                                    $current_year = date('Y');
                                    for ($year = $current_year; $year >= $current_year - 5; $year--): ?>
                                        <option value="<?= $year ?>" <?= ($_GET['year'] ?? '') == $year ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="monthFilter" class="form-label">Month</label>
                                <select class="form-select" id="monthFilter" name="month">
                                    <option value="">All Months</option>
                                    <?php
                                    $months = [
                                        '01' => 'January',
                                        '02' => 'February',
                                        '03' => 'March',
                                        '04' => 'April',
                                        '05' => 'May',
                                        '06' => 'June',
                                        '07' => 'July',
                                        '08' => 'August',
                                        '09' => 'September',
                                        '10' => 'October',
                                        '11' => 'November',
                                        '12' => 'December'
                                    ];
                                    foreach ($months as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= ($_GET['month'] ?? '') === $num ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        Clear Filters
                    </button>
                    <button type="submit" class="btn btn-primary" form="filterForm">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Clear filters function
        function clearFilters() {
            // Get current search term to preserve it
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput ? searchInput.value : '';

            // Redirect with only search term if it exists
            if (searchTerm) {
                window.location.href = '?search=' + encodeURIComponent(searchTerm);
            } else {
                window.location.href = '?';
            }
        }

        // Show notification function
        function showNotification(message, type = "info") {
            const notification = document.createElement("div");
            notification.className = `alert alert-${type === "success" ? "success" : "info"} alert-dismissible fade show position-fixed`;
            notification.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // Animate stats function
        function animateStats() {
            const statsNumbers = document.querySelectorAll('.stats-number');

            statsNumbers.forEach(stat => {
                const targetNumber = parseInt(stat.textContent.replace(/,/g, ''));
                const duration = 2000;
                const increment = targetNumber / (duration / 16);
                let current = 0;

                const updateNumber = () => {
                    current += increment;
                    if (current < targetNumber) {
                        stat.textContent = Math.floor(current).toLocaleString();
                        requestAnimationFrame(updateNumber);
                    } else {
                        stat.textContent = targetNumber.toLocaleString();
                    }
                };

                updateNumber();
            });
        }

        // Initialize page
        document.addEventListener("DOMContentLoaded", function () {
            animateStats();

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Add interactive effects to stats cards
            const statsCards = document.querySelectorAll(".stats-card");
            statsCards.forEach((card) => {
                card.addEventListener("mouseenter", function () {
                    this.style.transform = "translateY(-8px)";
                });

                card.addEventListener("mouseleave", function () {
                    this.style.transform = "translateY(0)";
                });
            });

            // Auto-submit search form with delay
            let searchTimeout;
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        // Auto-submit only if user has typed something or cleared the field
                        if (this.value.length > 2 || (this.value.length === 0 && '<?= $_GET['search'] ?? '' ?>' !== '')) {
                            document.getElementById('searchForm').submit();
                        }
                    }, 500); // 500ms delay
                });
            }
        });

        // Keyboard shortcuts
        document.addEventListener("keydown", function (e) {
            // Ctrl+F to focus search
            if (e.ctrlKey && e.key === "f") {
                e.preventDefault();
                document.getElementById("searchInput").focus();
            }

            // Escape to clear search
            if (e.key === "Escape") {
                const searchInput = document.getElementById("searchInput");
                if (searchInput && searchInput === document.activeElement) {
                    searchInput.value = '';
                    searchInput.blur();
                }
            }
        });

        // Show loading state on form submission
        document.addEventListener('submit', function (e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>

</html>