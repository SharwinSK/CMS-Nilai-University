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
    SELECT 
        e.Ev_ID, 
        e.Ev_Name, 
        es.Status_Name, 
        ec.Reviewer_Comment, 
        ec.Updated_By
    FROM events e
    JOIN eventstatus es ON e.Status_ID = es.Status_ID
    LEFT JOIN eventcomment ec ON ec.Comment_ID = (
        SELECT Comment_ID 
        FROM eventcomment 
        WHERE Ev_ID = e.Ev_ID 
          AND Comment_Type = 'proposal'
        ORDER BY Updated_At DESC 
        LIMIT 1
    )
    WHERE e.Stu_ID = ? 
    AND NOT EXISTS (
        SELECT 1 
        FROM eventpostmortem ep
        WHERE ep.Ev_ID = e.Ev_ID
    )
";

$stmt = $conn->prepare($proposals_query);
$stmt->bind_param("s", $stu_id);
$stmt->execute();
$proposals_result = $stmt->get_result();


$postmortem_query = "
SELECT 
    ep.Rep_ID, 
    ep.Ev_ID, 
    e.Ev_Name, 
    es.Status_Name, 
    ec.Reviewer_Comment,
    ec.Updated_By,
    ec.Updated_At
FROM eventpostmortem ep
JOIN events e ON ep.Ev_ID = e.Ev_ID
JOIN eventstatus es ON ep.Status_ID = es.Status_ID
LEFT JOIN eventcomment ec ON ec.Comment_ID = (
    SELECT Comment_ID 
    FROM eventcomment 
    WHERE Ev_ID = ep.Ev_ID 
      AND Comment_Type = 'postmortem'
    ORDER BY Updated_At DESC 
    LIMIT 1
)
WHERE e.Stu_ID = ?
";


$postmortem_stmt = $conn->prepare($postmortem_query);
$postmortem_stmt->bind_param("s", $stu_id);
$postmortem_stmt->execute();
$postmortem_result = $postmortem_stmt->get_result();

$feedback_by_event = [];
while ($proposal = $proposals_result->fetch_assoc()) {
    $event_id = $proposal['Ev_ID'];
    if (!isset($feedback_by_event[$event_id])) {
        $feedback_by_event[$event_id] = [
            'Ev_ID' => $proposal['Ev_ID'],
            'Ev_Name' => $proposal['Ev_Name'],
            'Status_Name' => $proposal['Status_Name'],
            'Advisor' => '',
            'Coordinator' => ''
        ];
    }

    if ($proposal['Updated_By'] === 'Advisor') {
        $feedback_by_event[$event_id]['Advisor'] = $proposal['Reviewer_Comment'];
    } elseif ($proposal['Updated_By'] === 'Coordinator') {
        $feedback_by_event[$event_id]['Coordinator'] = $proposal['Reviewer_Comment'];
    }
}

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
                    <?php foreach ($feedback_by_event as $proposal): ?>

                        <tr>
                            <td><?php echo $proposal['Ev_ID']; ?></td>
                            <td><?php echo $proposal['Ev_Name']; ?></td>
                            <td>
                                <?php
                                $status_text = $proposal['Status_Name'];
                                $status_class = ($status_text === 'Approved by Coordinator') ? 'success' :
                                    (($status_text === 'Rejected by Coordinator' || $status_text === 'Rejected by Advisor') ? 'danger' : 'warning');
                                echo "<span class='badge bg-$status_class'>{$status_text}</span>";
                                ?>
                            </td>

                            <td>
                                <button class="btn btn-info btn-sm btn-action" data-bs-toggle="modal"
                                    data-bs-target="#feedbackModal" onclick="showFeedback('Advisor Feedback', 
                                    '<?php echo addslashes($proposal['Advisor']); ?>')">
                                    View
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm btn-action" data-bs-toggle="modal"
                                    data-bs-target="#feedbackModal" onclick="showFeedback('Coordinator Feedback', 
                                    '<?php echo addslashes($proposal['Coordinator']); ?>')">
                                    View
                                </button>
                            </td>
                            <td>
                                <?php if (
                                    $proposal['Status_Name'] === 'Rejected by Advisor'
                                    || $proposal['Status_Name'] === 'Rejected by Coordinator'
                                ): ?>
                                    <a href="ModifyProposal.php?event_id=<?php echo $proposal['Ev_ID']; ?>"
                                        class="btn btn-warning btn-sm btn-action">Modify</a>
                                    <button class="btn btn-danger btn-sm btn-action"
                                        onclick="confirmDelete('<?php echo $proposal['Ev_ID']; ?>')">Delete</button>
                                <?php elseif ($proposal['Status_Name'] === 'Approved by Coordinator'): ?>
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
                    <?php endforeach; ?>

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
                                <?php
                                $status = $report['Status_Name'];
                                $badge = ($status === 'Approved') ? 'success' :
                                    (($status === 'Rejected') ? 'danger' : 'warning');
                                echo "<span class='badge bg-$badge'>$status</span>";
                                ?>
                            </td>

                            <td>
                                <a href="reportgeneratepdf.php?id=<?php echo $report['Rep_ID']; ?>"
                                    class="btn btn-warning btn-sm">Export</a>
                                <?php if ($report['Status_Name'] === 'Postmortem Rejected'): ?>
                                    <a href="ModifyPostmortem.php?rep_id=<?php echo $report['Rep_ID']; ?>"
                                        class="btn btn-danger btn-sm ms-1">Modify</a>
                                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackModal"
                                        onclick="showFeedback('Postmortem Feedback', '<?php echo addslashes($report['Reviewer_Comment']); ?>')">
                                        View Feedback
                                    </button>
                                <?php endif; ?>

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