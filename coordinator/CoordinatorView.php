<?php
session_start();
include('../db/dbconfig.php'); // adjust path if needed
$currentPage = 'proposal';
if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$stmt = $conn->prepare("SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?");
$stmt->bind_param("s", $coordinator_id);
$stmt->execute();
$result = $stmt->get_result();
$coordinator_name = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['Coor_Name'] : "Coordinator";

// Fetch Proposals Pending Coordinator Review
$proposal_query = "
SELECT e.Ev_ID, e.Ev_Name, s.Stu_Name, c.Club_Name, e.Updated_At
FROM events e
JOIN student s ON e.Stu_ID = s.Stu_ID
JOIN club c ON e.Club_ID = c.Club_ID
JOIN eventstatus es ON e.Status_ID = es.Status_ID
WHERE es.Status_Name = 'Approved by Advisor (Pending Coordinator Review)'
ORDER BY e.Updated_At DESC
";
$proposals_result = $conn->query($proposal_query);

// Fetch Postmortems Pending Review
$post_query = "
SELECT ep.Rep_ID, e.Ev_ID, e.Ev_Name, s.Stu_Name, c.Club_Name, ep.Updated_At 
FROM eventpostmortem ep
JOIN events e ON ep.Ev_ID = e.Ev_ID
JOIN student s ON e.Stu_ID = s.Stu_ID
JOIN club c ON e.Club_ID = c.Club_ID
JOIN eventstatus es ON ep.Status_ID = es.Status_ID
WHERE es.Status_Name = 'Postmortem Pending Review'
ORDER BY ep.Updated_At DESC
";
$postmortems_result = $conn->query($post_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>All Submissions - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/coordinator/eventlist.css?v=<?= time() ?>" rel="stylesheet" />
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />

</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-inbox"></i>
                All Submissions
            </h1>
            <p class="mb-0 text-muted">
                Manage and review all proposal and post-event submissions
            </p>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="submissionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="proposals-tab" data-bs-toggle="tab" data-bs-target="#proposals"
                    type="button" role="tab">
                    <i class="fas fa-file-alt me-2"></i>
                    📝 Proposal Submissions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="post-events-tab" data-bs-toggle="tab" data-bs-target="#post-events"
                    type="button" role="tab">
                    <i class="fas fa-clipboard-check me-2"></i>
                    📄 Post-Event Submissions
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="submissionTabContent">
            <!-- Proposal Submissions Tab -->
            <div class="tab-pane fade show active" id="proposals" role="tabpanel">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt text-primary me-2"></i>
                            Proposal Submissions
                        </h5>
                        <span class="badge bg-primary"><?= $proposals_result->num_rows ?> Total Submissions</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Student Name</th>
                                    <th>Club Name</th>
                                    <th>Submission Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="proposalsTableBody">
                                <?php if ($proposals_result->num_rows > 0): ?>
                                    <?php while ($row = $proposals_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['Ev_Name']) ?></strong></td>
                                            <td><?= htmlspecialchars($row['Stu_Name']) ?></td>
                                            <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                            <td><?= date('d M Y', strtotime($row['Updated_At'])) ?></td>
                                            <td>
                                                <a href="CoorProposalDecision.php?type=proposal&id=<?= $row['Ev_ID'] ?>"
                                                    class="action-btn btn-view">
                                                    <i class="fas fa-eye"></i>
                                                    <span class="tooltip">View Full Proposal</span>
                                                </a>
                                                <a href="../components/pdf/generate_pdf.php?id=<?= $row['Ev_ID'] ?>"
                                                    class="action-btn btn-export">
                                                    <i class="fas fa-download"></i>
                                                    <span class="tooltip">Export Proposal PDF</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No proposals pending review.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Post-Event Submissions Tab -->
            <div class="tab-pane fade" id="post-events" role="tabpanel">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check text-primary me-2"></i>
                            Post-Event Submissions
                        </h5>
                        <span class="badge bg-success"><?= $postmortems_result->num_rows ?> Total Reports</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="event-id-col">Event ID</th>
                                    <th>Event Name</th>
                                    <th>Student Name</th>
                                    <th>Club Name</th>
                                    <th>Submission Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="postEventsTableBody">
                                <?php if ($postmortems_result->num_rows > 0): ?>
                                    <?php while ($row = $postmortems_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="event-id-col"><strong><?= htmlspecialchars($row['Ev_ID']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($row['Ev_Name']) ?></td>
                                            <td><?= htmlspecialchars($row['Stu_Name']) ?></td>
                                            <td><?= htmlspecialchars($row['Club_Name']) ?></td>
                                            <td><?= date('d M Y', strtotime($row['Updated_At'])) ?></td>
                                            <td>
                                                <a href="CoorPostDecision.php?type=postmortem&id=<?= $row['Rep_ID'] ?>"
                                                    class="action-btn btn-view">
                                                    <i class="fas fa-eye"></i>
                                                    <span class="tooltip">View Full Post-Event Report</span>
                                                </a>
                                                <a href="reportgeneratepdf.php?id=<?= $row['Rep_ID'] ?>"
                                                    class="action-btn btn-export">
                                                    <i class="fas fa-download"></i>
                                                    <span class="tooltip">Export Post-Event PDF</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No post-event reports pending review.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
            tab.addEventListener("shown.bs.tab", function () {
                // Simple tab switching - no filter reset needed since filters are removed
            });
        });
    </script>
</body>

</html>