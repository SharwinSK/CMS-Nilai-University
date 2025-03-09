<?php
session_start();
include('dbconfig.php');
include('LogoutDesign.php');

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];
$student_name = $_SESSION['Stu_Name'];

$proposals_query = "
    SELECT e.Ev_ID, e.Ev_Name, e.Ev_Status, e.Ev_AdvisorComments, e.Coor_Comments
    FROM events e
    LEFT JOIN eventpostmortem ep ON e.Ev_ID = ep.Ev_ID
    WHERE e.Stu_ID = '$stu_id' AND (ep.Rep_ID IS NULL OR ep.Rep_PostStatus = 'Rejected')
";
$proposals_result = $conn->query($proposals_query);

$postmortem_query = "
    SELECT ep.Rep_ID, ep.Ev_ID, e.Ev_Name, ep.Rep_PostStatus
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    WHERE e.Stu_ID = '$stu_id' AND ep.Rep_PostStatus = 'Pending Coordinator Review'
";
$postmortem_result = $conn->query($postmortem_query);
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS GA Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styleMain.css">
    <style>
        /* Section Titles */
        .section-title {
            color: #495057;
            text-transform: uppercase;
            font-weight: bold;

        }

        .table thead th {
            background-color: #54C392;
            text-align: center;
        }

        .table tbody td,
        .table tbody tr {
            background-color: #D2FF72;
            /* White background */
            border-color: rgb(0, 0, 0);
            text-align: center;

            /* Light gray border */
        }

        .btn-success {
            background-color: #32CD32;
            /* Green */
            border: none;
        }

        .btn-success:hover {
            background-color: #15B392;
            /* Darker green */
            transform: scale(1.1);
            /* Slight zoom */
        }

        .badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 10px;
        }

        /* Buttons */
        .btn-action {
            transition: transform 0.2s;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        /* Modals */
        .modal-header {
            background-color: #32CD32;
            color: white;
        }

        .modal-footer .btn {
            background-color: #32CD32;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                ☰
            </button>
            <a class="navbar-brand" href="StudentDashboard.php">
                <img src="NU logo.png" alt="Logo">
                Progress
            </a>
            <div class="dropdown ms-auto">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown">
                    <?php echo $student_name; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="StudentProfile.php">Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <ul class="nav flex-column">
                <li class="nav-item"> <a class="nav-link active" href="StudentDashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="StudentProfile.php">User Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="ProposalEvent.php">Create Proposal</a></li>
                <li class="nav-item"><a class="nav-link" href="PostmortemView.php">Create Postmortem</a></li>
                <li class="nav-item"><a class="nav-link" href="ProgressPage.php">Track Progress</a></li>
                <li class="nav-item"><a class="nav-link" href="EventHistory.php">Event History</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <h1 class="text-center">Track Progress</h1>

        <!-- Event Proposals Section -->
        <h3 class="section-title">Event Proposals</h3>
        <?php if ($proposals_result->num_rows > 0): ?>
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Status</th>
                        <th>Advisor Feedback</th>
                        <th>Coordinator Feedback</th>
                        <th>Action</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($proposal = $proposals_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $proposal['Ev_ID']; ?></td>
                            <td><?php echo $proposal['Ev_Name']; ?></td>
                            <td>
                                <?php
                                $status_class = ($proposal['Ev_Status'] === 'Approved by Coordinator') ? 'success' :
                                    (($proposal['Ev_Status'] === 'Rejected by Coordinator' || $proposal['Ev_Status'] === 'Sent Back by Advisor') ? 'danger' : 'warning');
                                echo "<span class='badge bg-$status_class'>{$proposal['Ev_Status']}</span>";
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm btn-action" data-bs-toggle="modal"
                                    data-bs-target="#feedbackModal" onclick="showFeedback('Advisor Feedback', 
                                    '<?php echo addslashes($proposal['Ev_AdvisorComments']); ?>')">
                                    View
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm btn-action" data-bs-toggle="modal"
                                    data-bs-target="#feedbackModal" onclick="showFeedback('Coordinator Feedback', 
                                    '<?php echo addslashes($proposal['Coor_Comments']); ?>')">
                                    View
                                </button>
                            </td>
                            <td>
                                <?php if (
                                    $proposal['Ev_Status'] === 'Sent Back by Advisor'
                                    || $proposal['Ev_Status'] === 'Rejected by Coordinator'
                                ): ?>
                                    <a href="ModifyProposal.php?event_id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-warning btn-sm btn-action">Modify</a>
                                    <button class="btn btn-danger btn-sm btn-action"
                                        onclick="confirmDelete('<?php echo $proposal['Ev_ID']; ?>')">Delete</button>
                                <?php elseif ($proposal['Ev_Status'] === 'Approved by Coordinator'): ?>
                                    <a href="Postmortem.php?event_id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-success btn-sm btn-action">Create Postmortem</a>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="generate_pdf.php?id=<?php echo $proposal['Ev_ID']; ?>"
                                    class="btn btn-warning btn-sm">Export</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center">No event proposals found.</p>
        <?php endif; ?>

        <!-- Postmortem Section -->
        <h3 class="section-title">Postmortem Reports</h3>
        <?php if ($postmortem_result->num_rows > 0): ?>
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Report ID</th>
                        <th>Event ID</th>
                        <th>Event Name</th>
                        <th>Status</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($report = $postmortem_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $report['Rep_ID']; ?></td>
                            <td><?php echo $report['Ev_ID']; ?></td>
                            <td><?php echo $report['Ev_Name']; ?></td>
                            <td>
                                <span class="badge bg-warning">Pending</span>
                            </td>
                            <td>
                                <a href="reportgeneratepdf.php?id=<?php echo $report['Rep_ID']; ?>"
                                    class="btn btn-warning btn-sm">Export</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted text-center">No postmortem reports found.</p>
        <?php endif; ?>

    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="feedbackContent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this proposal? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showFeedback(title, feedback) {
            document.getElementById('feedbackModalLabel').innerText = title;
            document.getElementById('feedbackContent').innerText = feedback || 'No feedback available';
        }
        function confirmDelete(eventId) {
            const deleteUrl = `DeleteProposal.php?event_id=${eventId}`;
            document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php
// End time after processing the page
$end_time = microtime(true);
$page_load_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds

echo "<p style='color: green; font-weight: bold; text-align: center;'>
      Page Load Time: " . $page_load_time . " ms
      </p>";
?>

</html>