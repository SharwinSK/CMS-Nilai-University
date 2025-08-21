<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'eventmanagement';
// Check if user is logged in and is an admin
if (!isset($_SESSION['Admin_ID']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../adminlogin.php");
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

// Query for post-event reports - FIXED: Added Status_Name from eventstatus table
$postEventQuery = "
    SELECT 
        ep.Rep_ID, ep.Ev_ID, ep.Updated_At, ep.created_at,
        e.Ev_Name, e.Ev_Date,
        s.Stu_Name, c.Club_Name, st.Status_Name
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN eventstatus st ON ep.Status_ID = st.Status_ID
    ORDER BY ep.Updated_At DESC
";
$postEventResult = $conn->query($postEventQuery);

$clubsResultPost = $conn->query("SELECT DISTINCT Club_Name FROM club ORDER BY Club_Name ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Management - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />
    <style>
        /* Enhanced styling using main.css theme colors */
        :root {
            --primary-green: #25aa20;
            --hover-orange: #ff8645;
            --body-orange: rgb(253, 255, 112);
            --container-beige: #DDDAD0;
            --text-dark: #333;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        body {
            background: var(--body-orange);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-content {
            padding: 2rem;
            margin-top: 1rem;
        }

        .content-header {
            background: var(--container-beige);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--primary-green);
        }

        .content-header h2 {
            color: var(--primary-green);
            margin: 0;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .content-header h2 i {
            color: var(--hover-orange);
            font-size: 2.5rem;
        }

        /* Tab Navigation Styling */
        .nav-tabs {
            border: none;
            background: var(--container-beige);
            border-radius: 15px 15px 0 0;
            padding: 0.5rem;
            margin-bottom: 0;
        }

        .nav-tabs .nav-link {
            color: var(--primary-green);
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border-radius: 12px;
            margin: 0 0.25rem;
            background: transparent;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(255, 134, 69, 0.1);
            color: var(--hover-orange);
            transform: translateY(-2px);
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-green);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 170, 32, 0.3);
        }

        .tab-content {
            background: var(--container-beige);
            border-radius: 0 0 15px 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--primary-green);
            border-top: none;
        }

        /* Search and Filter Container */
        .search-filter-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--primary-green);
        }

        .form-control, .form-select {
            border: 2px solid var(--primary-green);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--hover-orange);
            box-shadow: 0 0 0 0.25rem rgba(255, 134, 69, 0.25);
            transform: translateY(-1px);
        }

        /* Table Styling */
        .table-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--primary-green);
        }

        .table {
            margin: 0;
            background: white;
        }

        .table thead th {
            background: var(--primary-green);
            color: white;
            font-weight: 700;
            border: none;
            padding: 1.25rem 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .table tbody td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(37, 170, 32, 0.1);
            font-weight: 500;
        }

        .table tbody tr:hover {
            background: rgba(37, 170, 32, 0.05);
            transform: translateX(2px);
            transition: all 0.3s ease;
        }

        .table tbody tr:nth-child(even) {
            background: rgba(221, 218, 208, 0.3);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        .status-approved {
            background: #d1edff;
            color: #0c5460;
            border: 1px solid #17a2b8;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #dc3545;
        }

        .status-draft {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #6c757d;
        }

        /* Activity Badges */
        .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.3rem 0.5rem;
            border-radius: 15px;
        }

        .bg-warning-subtle {
            background: #fff3cd !important;
            color: #856404 !important;
            border: 1px solid #ffc107;
        }

        .bg-success-subtle {
            background: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #28a745;
        }

        /* Button Styling */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-outline-primary {
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .btn-outline-primary:hover {
            background: var(--primary-green);
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            border: none;
        }

        .btn-warning {
            background: #ffc107;
            border: none;
            color: #333;
        }

        .btn-danger {
            background: #dc3545;
            border: none;
        }

        .btn-success {
            background: #28a745;
            border: none;
        }

        /* Action buttons container */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            min-width: 40px;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .content-header h2 {
                font-size: 1.5rem;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .btn-sm {
                min-width: 80px;
            }
        }

        /* Loading animation */
        .table tbody tr {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h2>
                <i class="fas fa-calendar-check"></i>
                Event Management System
            </h2>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="eventTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="proposals-tab" data-bs-toggle="tab" data-bs-target="#proposals"
                    type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>Event Proposals
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
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color: var(--primary-green);">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Search events or students..."
                                    id="proposalSearch" />
                            </div>
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
                                <th><i class="fas fa-hashtag me-1"></i>Event ID</th>
                                <th><i class="fas fa-event me-1"></i>Event Name</th>
                                <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                <th><i class="fas fa-calendar me-1"></i>Created Date</th>
                                <th><i class="fas fa-users me-1"></i>Club</th>
                                <th><i class="fas fa-user me-1"></i>Student</th>
                                <th><i class="fas fa-activity me-1"></i>Activity</th>
                                <th><i class="fas fa-cogs me-1"></i>Actions</th>
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
                                    <td><strong><?= htmlspecialchars($row['Ev_ID']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['Ev_Name']) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['Status_Name']) ?></span></td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Stu_Name']) ?></td>
                                    <td>
                                        <?php if ($row['activity_flag'] === 'No Activity'): ?>
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>No Activity
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-info btn-sm"
                                                href="eventAction.php?action=view&type=proposal&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="btn btn-warning btn-sm"
                                                href="eventAction.php?action=edit&type=proposal&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Edit Event">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-danger btn-sm"
                                                href="eventAction.php?action=delete&type=abandoned&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Delete Event"
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
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-color: var(--primary-green);">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Search events or students..."
                                    id="reportSearch" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="reportStatusFilter">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
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
                                <th><i class="fas fa-hashtag me-1"></i>Event ID</th>
                                <th><i class="fas fa-event me-1"></i>Event Name</th>
                                <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                <th><i class="fas fa-calendar me-1"></i>Created Date</th>
                                <th><i class="fas fa-users me-1"></i>Club</th>
                                <th><i class="fas fa-user me-1"></i>Student</th>
                                <th><i class="fas fa-cogs me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $postEventResult->fetch_assoc()): ?>
                                <?php
                                // Map the database status to simpler display names
                                $dbStatus = $row['Status_Name'] ?? 'Postmortem Pending Review';
                                $displayStatus = match (true) {
                                    str_contains($dbStatus, 'Pending') => 'Pending',
                                    str_contains($dbStatus, 'Approved') => 'Approved', 
                                    str_contains($dbStatus, 'Rejected') => 'Rejected',
                                    default => 'Pending'
                                };
                                
                                $statusClass = match ($displayStatus) {
                                    'Pending' => 'status-pending',
                                    'Approved' => 'status-approved',
                                    'Rejected' => 'status-rejected',
                                    default => 'status-draft'
                                };
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['Ev_ID']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['Ev_Name']) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $displayStatus ?></span></td>
                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                    <td><?= htmlspecialchars($row['Stu_Name']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a class="btn btn-info btn-sm"
                                                href="eventAction.php?action=view&type=report&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="View Report">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="btn btn-warning btn-sm"
                                                href="eventAction.php?action=edit&type=report&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Edit Report">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a class="btn btn-danger btn-sm"
                                                href="eventAction.php?action=delete&type=report&id=<?= urlencode($row['Ev_ID']) ?>"
                                                title="Delete Report" 
                                                onclick="return confirm('Delete this post-event report?');">
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
        // Enhanced filtering functions
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
                const student = row.children[5].textContent.toLowerCase();
                const rowStatus = row.children[2].textContent.toLowerCase();
                const rowClub = row.children[4].textContent.toLowerCase();

                const matchSearch = name.includes(search) || student.includes(search);
                const matchStatus = !status || rowStatus.includes(status);
                const matchClub = !club || rowClub.includes(club);

                if (matchSearch && matchStatus && matchClub) {
                    row.style.display = "";
                    row.style.animation = "fadeIn 0.5s ease-in";
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
                const student = row.children[5].textContent.toLowerCase();
                const evStatus = row.children[2].textContent.toLowerCase();
                const evClub = row.children[4].textContent.toLowerCase();

                const matchSearch = evName.includes(search) || student.includes(search);
                const matchStatus = !status || evStatus.includes(status);
                const matchClub = !club || evClub.includes(club);

                if (matchSearch && matchStatus && matchClub) {
                    row.style.display = "";
                    row.style.animation = "fadeIn 0.5s ease-in";
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

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Add smooth scrolling and enhanced interactions
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.2)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Enhanced table row hover effects
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.background = 'rgba(37, 170, 32, 0.1)';
                this.style.transform = 'translateX(4px)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.background = '';
                this.style.transform = 'translateX(0)';
            });
        });

        // Tab switching animation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                // Add loading effect
                const tabContent = document.querySelector('.tab-content');
                tabContent.style.opacity = '0.7';
                
                setTimeout(() => {
                    tabContent.style.opacity = '1';
                }, 150);
            });
        });

        // Search input enhancements
        document.querySelectorAll('input[type="text"]').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-1px)';
                this.parentElement.style.boxShadow = '0 4px 15px rgba(255, 134, 69, 0.2)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
                this.parentElement.style.boxShadow = 'none';
            });
        });

        // Status badge animations
        document.querySelectorAll('.status-badge').forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Confirmation dialogs with better styling
        function confirmDelete(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Add loading states for buttons
        document.querySelectorAll('a[href*="action="]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href.includes('delete')) {
                    return; // Let the onclick handle it
                }
                
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.style.pointerEvents = 'none';
                
                // Restore after a short delay (in real app, this would be when the page loads)
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.style.pointerEvents = 'auto';
                }, 1000);
            });
        });

        // Auto-refresh functionality (optional)
        let autoRefresh = false;
        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            if (autoRefresh) {
                setInterval(() => {
                    if (autoRefresh) {
                        location.reload();
                    }
                }, 30000); // Refresh every 30 seconds
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + F to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const activeTab = document.querySelector('.tab-pane.active');
                const searchInput = activeTab.querySelector('input[type="text"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape to clear filters
            if (e.key === 'Escape') {
                const activeTab = document.querySelector('.tab-pane.active');
                if (activeTab.id === 'proposals') {
                    resetProposalFilters();
                } else if (activeTab.id === 'reports') {
                    resetReportFilters();
                }
            }
        });

        // Add data export functionality
        function exportTableData(tableId, filename) {
            const table = document.getElementById(tableId);
            const rows = Array.from(table.querySelectorAll('tr:not([style*="display: none"])'));
            
            let csv = '';
            rows.forEach(row => {
                const cols = Array.from(row.querySelectorAll('th, td'));
                const rowData = cols.map(col => {
                    // Remove HTML tags and clean up text
                    return '"' + col.textContent.replace(/"/g, '""').trim() + '"';
                }).join(',');
                csv += rowData + '\n';
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Add export buttons (you can add these to the UI if needed)
        function addExportButtons() {
            const proposalContainer = document.querySelector('#proposals .search-filter-container .row');
            const reportContainer = document.querySelector('#reports .search-filter-container .row');
            
            // Add export button for proposals
            const proposalExportBtn = document.createElement('div');
            proposalExportBtn.className = 'col-md-2 mt-2';
            proposalExportBtn.innerHTML = `
                <button class="btn btn-outline-success w-100" onclick="exportTableData('proposalsTable', 'event-proposals')">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            `;
            proposalContainer.appendChild(proposalExportBtn);
            
            // Add export button for reports
            const reportExportBtn = document.createElement('div');
            reportExportBtn.className = 'col-md-2 mt-2';
            reportExportBtn.innerHTML = `
                <button class="btn btn-outline-success w-100" onclick="exportTableData('reportsTable', 'post-event-reports')">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            `;
            reportContainer.appendChild(reportExportBtn);
        }

        // Initialize export buttons
        // addExportButtons(); // Uncomment if you want export functionality

        console.log('Event Management System initialized successfully!');
    </script>
</body>

</html>