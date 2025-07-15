<?php
session_start();
include('../db/dbconfig.php'); // Adjust path if needed

$currentPage = 'progress';
if (!isset($_SESSION['Stu_ID'])) {
    header("Location: ../studentlogin.php");
    exit();
}

$stu_id = $_SESSION['Stu_ID'];
// Fetch proposal progress data
$proposals_query = "
    SELECT 
        e.Ev_ID, 
        e.Ev_Name, 
        e.Ev_Date,
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
    JOIN eventstatus s ON ep.Status_ID = s.Status_ID
    WHERE ep.Ev_ID = e.Ev_ID AND s.Status_Name = 'Postmortem Approved'
)

";

$stmt = $conn->prepare($proposals_query);
$stmt->bind_param("s", $stu_id);
$stmt->execute();
$proposals_result = $stmt->get_result();

$feedback_by_event = [];
while ($row = $proposals_result->fetch_assoc()) {
    $event_id = $row['Ev_ID'];
    if (!isset($feedback_by_event[$event_id])) {
        $feedback_by_event[$event_id] = [
            'Ev_ID' => $row['Ev_ID'],
            'Ev_Name' => $row['Ev_Name'],
            'Ev_Date' => $row['Ev_Date'],
            'Status_Name' => $row['Status_Name'],
            'Advisor' => '',
            'Coordinator' => ''
        ];
    }

    if ($row['Updated_By'] === 'Advisor') {
        $feedback_by_event[$event_id]['Advisor'] = $row['Reviewer_Comment'];
    } elseif ($row['Updated_By'] === 'Coordinator') {
        $feedback_by_event[$event_id]['Coordinator'] = $row['Reviewer_Comment'];
    }
}

// Fetch postmortem reports submitted by student
$postmortem_query = "
SELECT 
    ep.Rep_ID, 
    ep.Ev_ID, 
    e.Ev_Name, 
    ep.created_at AS SubmitDate,
    es.Status_Name, 
    ec.Reviewer_Comment
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
AND es.Status_Name != 'Postmortem Approved'

";

$postmortem_stmt = $conn->prepare($postmortem_query);
$postmortem_stmt->bind_param("s", $stu_id);
$postmortem_stmt->execute();
$postmortem_result = $postmortem_stmt->get_result();
?>
<?php include('../components/header.php'); ?>
<?php include('../components/offcanvas.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Progress - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/student.css?v=<?= time() ?>" rel="stylesheet" />

</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container-fluid">
            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">
                        <i class="fas fa-sync-alt me-2"></i>
                        Event Track Progress
                    </h1>
                </div>
            </div>

            <!-- Proposal Section -->
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">
                        <i class="fas fa-file-alt me-2"></i>
                        Event Proposals
                    </h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Status</th>
                                    <th>Advisor Feedback</th>
                                    <th>Coordinator Feedback</th>
                                    <th>Export</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="proposalTableBody">
                                <?php foreach ($feedback_by_event as $proposal): ?>
                                    <?php
                                    $status = $proposal['Status_Name'];
                                    $badge_class = 'status-pending';
                                    if (strpos($status, 'Rejected') !== false)
                                        $badge_class = 'status-rejected';
                                    elseif (strpos($status, 'Approved') !== false)
                                        $badge_class = 'status-approved';
                                    ?>
                                    <tr>
                                        <td><?= $proposal['Ev_ID'] ?></td>
                                        <td><?= $proposal['Ev_Name'] ?></td>
                                        <td><span class="status-badge <?= $badge_class ?>"><?= $status ?></span></td>

                                        <td>
                                            <button class="action-btn btn-feedback" data-bs-toggle="modal"
                                                data-bs-target="#feedbackModal"
                                                onclick="showFeedback('Advisor', '<?= addslashes($proposal['Advisor']) ?>', '<?= $proposal['Ev_ID'] ?>')">
                                                <i class="fas fa-comments"></i> View
                                            </button>
                                        </td>
                                        <td>
                                            <button class="action-btn btn-feedback" data-bs-toggle="modal"
                                                data-bs-target="#feedbackModal"
                                                onclick="showFeedback('Coordinator', '<?= addslashes($proposal['Coordinator']) ?>', '<?= $proposal['Ev_ID'] ?>')">
                                                <i class="fas fa-comments"></i> View
                                            </button>
                                        </td>
                                        <td>
                                            <button class="action-btn btn-export"
                                                onclick="exportProposal('<?= $proposal['Ev_ID'] ?>')">
                                                <i class="fas fa-download"></i> Export
                                            </button>
                                        </td>
                                        <td>
                                            <?php
                                            // Check if postmortem already exists for this event
                                            $postmortem_exists_query = "SELECT 1 FROM eventpostmortem WHERE Ev_ID = ?";
                                            $check_stmt = $conn->prepare($postmortem_exists_query);
                                            $check_stmt->bind_param("s", $proposal['Ev_ID']);
                                            $check_stmt->execute();
                                            $postmortem_exists = $check_stmt->get_result()->num_rows > 0;

                                            if ($postmortem_exists) {
                                                echo '<span class="text-muted">N/A</span>';
                                            } elseif ($status === 'Pending Advisor Review' || $status === 'Approved by Advisor (Pending Coordinator Review)') {
                                                echo '<button class="action-btn btn-edit" onclick="editProposal(\'' . $proposal['Ev_ID'] . '\')"><i class="fas fa-edit"></i> Edit</button>';
                                                echo '<button class="action-btn btn-view" onclick="viewProposal(\'' . $proposal['Ev_ID'] . '\')"><i class="fas fa-eye"></i> View</button>';
                                            } elseif ($status === 'Rejected by Advisor' || $status === 'Rejected by Coordinator') {
                                                echo '<button class="action-btn btn-modify" onclick="modifyProposal(\'' . $proposal['Ev_ID'] . '\')"><i class="fas fa-wrench"></i> Modify</button>';
                                                echo '<button class="action-btn btn-delete" onclick="deleteProposal(\'' . $proposal['Ev_ID'] . '\')"><i class="fas fa-trash"></i> Delete</button>';
                                            } elseif ($status === 'Approved by Coordinator') {
                                                echo '<button class="action-btn btn-view" onclick="viewProposal(\'' . $proposal['Ev_ID'] . '\')"><i class="fas fa-eye"></i> View</button>';
                                                echo '<button class="action-btn btn-postmortem" onclick="createPostmortem(\'' . $proposal['Ev_ID'] . '\')"><i class="fas fa-plus"></i> Create Postmortem</button>';
                                            } else {
                                                echo '<span class="text-muted">N/A</span>';
                                            }
                                            ?>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>

            <!-- Post Event Section -->
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-check me-2"></i>
                        Post Event Reports
                    </h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Status</th>
                                    <th>Coordinator Feedback</th>
                                    <th>Export</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="postEventTableBody">
                                <?php while ($report = $postmortem_result->fetch_assoc()): ?>
                                    <?php
                                    $status = $report['Status_Name'];
                                    $badge_class = 'status-pending';
                                    if (strpos($status, 'Rejected') !== false)
                                        $badge_class = 'status-rejected';
                                    elseif (strpos($status, 'Approved') !== false)
                                        $badge_class = 'status-approved';
                                    ?>
                                    <tr>
                                        <td><?= $report['Ev_ID'] ?></td>
                                        <td><?= $report['Ev_Name'] ?></td>
                                        <td><span class="status-badge <?= $badge_class ?>"><?= $status ?></span></td>

                                        <td>
                                            <button class="action-btn btn-feedback"
                                                onclick="showFeedback('Coordinator', '<?= addslashes($report['Reviewer_Comment']) ?>', '<?= $report['Ev_ID'] ?>')">
                                                <i class="fas fa-comments"></i> View
                                            </button>
                                        </td>
                                        <td>
                                            <button class="action-btn btn-export"
                                                onclick="exportPostEvent('<?= $report['Rep_ID'] ?>')">
                                                <i class="fas fa-download"></i> Export PDF
                                            </button>
                                        </td>
                                        <td>
                                            <?php
                                            if ($status === 'Postmortem Pending Review') {
                                                echo '<button class="action-btn btn-edit" onclick="editPostEvent(\'' . $report['Rep_ID'] . '\')"><i class="fas fa-edit"></i> Edit</button>';
                                                echo '<button class="action-btn btn-view" onclick="viewPostEvent(\'' . $report['Rep_ID'] . '\')"><i class="fas fa-eye"></i> View</button>';
                                            } elseif ($status === 'Postmortem Rejected') {
                                                echo '<button class="action-btn btn-modify" onclick="modifyPostEvent(\'' . $report['Rep_ID'] . '\')"><i class="fas fa-wrench"></i> Modify</button>';
                                            } else {
                                                echo '<span class="text-muted">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-comments me-2"></i>
                        <span id="feedbackTitle">Feedback</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="feedbackContent">
                        <!-- Feedback content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/studentjs/stuprogresspage.js?v=<?= time(); ?>"></script>
</body>

</html>