<?php
include '../dbconfig.php';

// Fetch all proposal events with activity status and club list
$proposalEventQuery = "
    SELECT 
        e.Ev_ID, e.Ev_Name, e.Ev_Date, e.Ev_TypeRef, e.Updated_At, e.created_at,
        s.Stu_Name, c.Club_Name, st.Status_Name,
        CASE 
            WHEN (st.Status_Name IN ('Rejected by Advisor', 'Rejected by Coordinator') 
                  AND DATEDIFF(NOW(), e.Updated_At) >= 30) 
            THEN 'No Activity'
            ELSE 'Active'
        END AS activity_flag
    FROM events e
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN eventstatus st ON e.Status_ID = st.Status_ID
    ORDER BY e.Ev_Date DESC
";
$proposalResult = $conn->query($proposalEventQuery);

// Fetch clubs for filter dropdown
$clubsQuery = "SELECT DISTINCT Club_Name FROM club ORDER BY Club_Name ASC";
$clubsResult = $conn->query($clubsQuery);


// Query for post-event reports
$postEventQuery = "
    SELECT 
        ep.Rep_ID, ep.Ev_ID, ep.Rep_PostStatus, ep.Updated_At, ep.created_at,
        e.Ev_Name, e.Ev_Date,
        s.Stu_Name, c.Club_Name
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    ORDER BY ep.Updated_At DESC
";
$postEventResult = $conn->query($postEventQuery);

$clubsResultPost = $conn->query("SELECT DISTINCT Club_Name FROM club ORDER BY Club_Name ASC");


// Query for completed events
$completedEventQuery = "
  SELECT 
    e.Ev_ID, e.Ev_Name, e.Ev_Date, e.Ev_TypeRef,
    s.Stu_Name, c.Club_Name, ep.Rep_PostStatus
  FROM events e
  INNER JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
  LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
  LEFT JOIN club c ON e.Club_ID = c.Club_ID
  WHERE ep.Rep_PostStatus = 'Accepted'
  ORDER BY e.Ev_Date DESC
";

$completedResult = $conn->query($completedEventQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Management - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #03a791;
            --secondary-color: #81e7af;
            --accent-color: #e9f5be;
            --warm-color: #f1ba88;
            --light-bg: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg,
                    var(--accent-color),
                    var(--light-bg));
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling */
        .offcanvas-start {
            background: linear-gradient(135deg,
                    var(--primary-color),
                    var(--secondary-color));
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

        /* Content Styling */
        .main-content {
            padding: 2rem;
            margin-top: 1rem;
        }

        .content-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .content-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-weight: bold;
        }

        .nav-tabs .nav-link {
            color: var(--primary-color);
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg,
                    var(--primary-color),
                    var(--secondary-color));
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            background-color: rgba(3, 167, 145, 0.1);
        }

        .tab-content {
            background: white;
            border-radius: 0 0 15px 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: linear-gradient(135deg,
                    var(--primary-color),
                    var(--secondary-color));
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }

        .table tbody tr:hover {
            background-color: rgba(3, 167, 145, 0.05);
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            margin: 0 0.2rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1edff;
            color: #0c5460;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .search-filter-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(3, 167, 145, 0.25);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(3, 167, 145, 0.25);
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg" style="
        background: linear-gradient(
          135deg,
          var(--primary-color),
          var(--secondary-color)
        );
      ">
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
                        <li>
                            <a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li>
                            <a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                        </li>
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
                <a class="nav-link" href="dashboard.php" data-section="dashboard">
                    <i class="fas fa-home me-2"></i>Admin Dashboard
                </a>
                <a class="nav-link active" href="eventmanagement.php" data-section="events">
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
    <div class="main-content">
        <div class="content-header">
            <h2>
                <i class="fas fa-calendar-check me-3"></i>Event Management
            </h2>

        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="eventTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="proposals-tab" data-bs-toggle="tab" data-bs-target="#proposals"
                    type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Proposal Events
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button"
                    role="tab">
                    <i class="fas fa-file-medical me-2"></i>Post-Event Reports
                </button>
            </li>

        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="eventTabContent">
            <!-- Proposal Events Tab -->
            <div class="tab-pane fade show active" id="proposals" role="tabpanel">
                <div class="search-filter-container">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search by event name or student..."
                                id="proposalSearch" />
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="proposalStatusFilter">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="proposalClubFilter">
                                <option value="">All Clubs</option>
                                <?php while ($club = $clubsResult->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($club['Club_Name']) ?>">
                                        <?= htmlspecialchars($club['Club_Name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" onclick="resetProposalFilters()">
                                <i class="fas fa-refresh me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Proposal Events Table -->
                <div class="table-container">
                    <table class="table table-hover mb-0" id="proposalsTable">
                        <thead>
                            <tr>
                                <th>Event&nbsp;ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Create&nbsp;Date</th>
                                <th>Club</th>
                                <th>Student</th>
                                <th>Active</th>
                                <th>Actions</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $proposalResult->fetch_assoc()): ?>
                                <?php
                                $status = strtolower($row['Status_Name']);
                                $statusClass = match (true) {
                                    str_contains($status, 'pending') => 'status-pending',
                                    str_contains($status, 'approved') => 'status-approved',
                                    str_contains($status, 'rejected') => 'status-rejected',
                                    default => 'status-draft'
                                };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Ev_ID']) ?></td>
                                    <td><?= htmlspecialchars($row['Ev_Name']) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $row['Status_Name'] ?></span>
                                    </td>
                                    <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Stu_Name']) ?></td>
                                    <td>
                                        <?php if ($row['activity_flag'] === 'No Activity'): ?>
                                            <span class="badge bg-warning-subtle text-warning">⚠ No Activity</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success">🟢 Active</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <a class="btn btn-info btn-sm"
                                                href="eventAction.php?action=view&type=proposal&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="btn btn-warning btn-sm"
                                                href="eventAction.php?action=edit&type=proposal&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-danger btn-sm"
                                                href="eventAction.php?action=delete&type=abandoned&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Delete"
                                                onclick="return confirm('This event has no activity. Delete permanently?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <a class="btn btn-success btn-sm"
                                                href="eventAction.php?action=export&type=proposal&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Export PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        </div>
                                    </td>


                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Post-Event Reports Tab -->
            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="search-filter-container">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search by event name or student..."
                                id="reportSearch" />
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="reportStatusFilter">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Accepted">Accepted</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-select" id="reportClubFilter">
                                <option value="">All Clubs</option>
                                <?php while ($club = $clubsResultPost->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($club['Club_Name']) ?>">
                                        <?= htmlspecialchars($club['Club_Name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" onclick="resetReportFilters()">
                                <i class="fas fa-refresh me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table table-hover mb-0" id="reportsTable">
                        <thead>
                            <tr>
                                <th>Event&nbsp;ID</th>
                                <th>Event Name</th>
                                <th>Status</th>
                                <th>Create&nbsp;Date</th>
                                <th>Club</th>
                                <th>Student</th>
                                <th>Actions</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $postEventResult->fetch_assoc()): ?>
                                <?php
                                $status = strtolower($row['Rep_PostStatus']);
                                $statusClass = match (true) {
                                    str_contains($status, 'pending') => 'status-pending',
                                    str_contains($status, 'accepted') => 'status-approved',
                                    str_contains($status, 'rejected') => 'status-rejected',
                                    default => 'status-draft'
                                };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Ev_ID']) ?></td>
                                    <td><?= htmlspecialchars($row['Ev_Name']) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $row['Rep_PostStatus'] ?></span>
                                    </td>
                                    <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Stu_Name']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <a class="btn btn-info btn-sm"
                                                href="eventAction.php?action=view&type=report&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="btn btn-warning btn-sm"
                                                href="eventAction.php?action=edit&type=report&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-danger btn-sm"
                                                href="eventAction.php?action=delete&type=report&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Delete" onclick="return confirm('Delete this post-event report?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a class="btn btn-success btn-sm"
                                                href="eventAction.php?action=export&type=report&id=<?= urlencode($row['Rep_ID']) ?>"
                                                title="Export PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        </div>
                                    </td>


                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Proposal Events Functions
        function viewDetails(eventId) {
            alert("Viewing details for Event ID: " + eventId);
            // Add your PHP/AJAX call here
        }

        function editProposal(eventId) {
            alert("Editing proposal for Event ID: " + eventId);
            // Add your PHP/AJAX call here
        }

        function deleteProposal(eventId) {
            if (confirm("Are you sure you want to delete this proposal?")) {
                alert("Deleting proposal for Event ID: " + eventId);
                // Add your PHP/AJAX call here
            }
        }

        function exportProposalPDF(eventId) {
            alert("Exporting PDF for Event ID: " + eventId);
            // Add your PDF export functionality here
        }

        // Post-Event Reports Functions
        function viewReport(reportId) {
            alert("Viewing report for Report ID: " + reportId);
            // Add your PHP/AJAX call here
        }

        function editReport(reportId) {
            alert("Editing report for Report ID: " + reportId);
            // Add your PHP/AJAX call here
        }

        function deleteReport(reportId) {
            if (confirm("Are you sure you want to delete this report?")) {
                alert("Deleting report for Report ID: " + reportId);
                // Add your PHP/AJAX call here
            }
        }

        function exportReportPDF(reportId) {
            alert("Exporting PDF for Report ID: " + reportId);
            // Add your PDF export functionality here
        }

        // Complete Events Functions
        function exportCompletedPDF(eventId) {
            alert("Exporting PDF for Completed Event ID: " + eventId);
            // Add your PDF export functionality here
        }

        function deleteCompleted(eventId) {
            if (confirm("Are you sure you want to delete this completed event?")) {
                alert("Deleting completed event for Event ID: " + eventId);
                // Add your PHP/AJAX call here
            }
        }

        document.getElementById("proposalStatusFilter").addEventListener("change", filterProposalTable);
        document.getElementById("proposalClubFilter").addEventListener("change", filterProposalTable);
        document.getElementById("proposalSearch").addEventListener("input", filterProposalTable);

        function filterProposalTable() {
            const search = document.getElementById("proposalSearch").value.toLowerCase();
            const status = document.getElementById("proposalStatusFilter").value.toLowerCase();
            const club = document.getElementById("proposalClubFilter").value.toLowerCase();

            const rows = document.querySelectorAll("#proposalsTable tbody tr");

            rows.forEach(row => {
                const name = row.children[1].textContent.toLowerCase();
                const rowStatus = row.children[2].textContent.toLowerCase();
                const rowClub = row.children[4].textContent.toLowerCase();

                const matchSearch = name.includes(search);
                const matchStatus = !status || rowStatus.includes(status);
                const matchClub = !club || rowClub.includes(club);

                if (matchSearch && matchStatus && matchClub) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function resetProposalFilters() {
            document.getElementById("proposalSearch").value = "";
            document.getElementById("proposalStatusFilter").value = "";
            document.getElementById("proposalClubFilter").value = "";
            filterProposalTable();
        }

        // Post-Event Reports Filtering
        document.getElementById("reportSearch").addEventListener("input", filterReportTable);
        document.getElementById("reportStatusFilter").addEventListener("change", filterReportTable);
        document.getElementById("reportClubFilter").addEventListener("change", filterReportTable);

        function filterReportTable() {
            const search = document.getElementById("reportSearch").value.toLowerCase();
            const status = document.getElementById("reportStatusFilter").value.toLowerCase();
            const club = document.getElementById("reportClubFilter").value.toLowerCase();

            const rows = document.querySelectorAll("#reportsTable tbody tr");

            rows.forEach(row => {
                const evName = row.children[1].textContent.toLowerCase();
                const evStatus = row.children[2].textContent.toLowerCase();
                const evClub = row.children[4].textContent.toLowerCase();

                const matchSearch = evName.includes(search);
                const matchStatus = !status || evStatus.includes(status);
                const matchClub = !club || evClub.includes(club);

                if (matchSearch && matchStatus && matchClub) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function resetReportFilters() {
            document.getElementById("reportSearch").value = "";
            document.getElementById("reportStatusFilter").value = "";
            document.getElementById("reportClubFilter").value = "";
            filterReportTable();
        }
        document.querySelectorAll('[title]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    </script>
</body>

</html>