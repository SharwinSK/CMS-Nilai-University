<?php
include('../db/dbconfig.php');
require_once '../model/sendMailTemplates.php';

session_start();

if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$rep_id = $_GET['id'] ?? '';
if (empty($rep_id)) {
    die("Missing report ID.");
}

// 🔍 Fetch eventpostmortem + related event + student + club
$stmt = $conn->prepare("
    SELECT 
        ep.*, 
        e.Ev_ID, e.Ev_Name, e.Ev_Poster, e.Ev_Objectives, 
        s.Stu_Name, s.Stu_Email,
        a.Adv_Email,
        c.Club_Name,
        bs.statement AS BudgetStatement
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    JOIN student s ON e.Stu_ID = s.Stu_ID
   LEFT JOIN advisor a ON e.Club_ID = a.Club_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN budgetsummary bs ON e.Ev_ID = bs.Ev_ID
    WHERE ep.Rep_ID = ?
");

$stmt->bind_param("s", $rep_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();

if (!$details) {
    die("Post-event report not found.");
}

// 🔁 Fetch Event Flow (eventflows table)
$flow_stmt = $conn->prepare("SELECT * FROM eventflows WHERE Rep_ID = ? ORDER BY STR_TO_DATE(EvFlow_Time, '%H:%i:%s')");
$flow_stmt->bind_param("s", $rep_id);
$flow_stmt->execute();
$event_flows = $flow_stmt->get_result();

// 🔁 Fetch Meeting Details
$meet_stmt = $conn->prepare("SELECT * FROM posteventmeeting WHERE Rep_ID = ? ORDER BY Meeting_Date");
$meet_stmt->bind_param("s", $rep_id);
$meet_stmt->execute();
$meeting_result = $meet_stmt->get_result();

// ✅ Individual Reports: only COCU claimers for this report (Rep_ID)
$report_stmt = $conn->prepare("
  SELECT 
      c.Com_ID,
      c.Com_Name,
      c.Com_Position,
      ir.IR_File
  FROM eventpostmortem ep
  JOIN committee c
        ON c.Ev_ID = ep.Ev_ID
       AND c.Com_COCUClaimers = 'yes'          -- << ONLY COCU claimers
  LEFT JOIN individualreport ir
        ON ir.Rep_ID = ep.Rep_ID               -- same report
       AND ir.Com_ID = c.Com_ID                -- same committee member
  WHERE ep.Rep_ID = ?
  ORDER BY c.Com_Position, c.Com_Name
");
$report_stmt->bind_param("s", $rep_id);
$report_stmt->execute();
$individual_reports = $report_stmt->get_result();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rep_id = $_POST['rep_id'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $rejectedSections = $_POST['rejected_sections'] ?? [];

    // Fetch Ev_ID and Event Details for notifications
    $ev_query = $conn->prepare("
        SELECT ep.Ev_ID, e.Ev_Name, e.Ev_RefNum 
        FROM eventpostmortem ep 
        JOIN events e ON ep.Ev_ID = e.Ev_ID 
        WHERE ep.Rep_ID = ?
    ");
    $ev_query->bind_param("s", $rep_id);
    $ev_query->execute();
    $ev_result = $ev_query->get_result();
    $event_data = $ev_result->fetch_assoc();

    $ev_id = $event_data['Ev_ID'] ?? null;
    $event_name = $event_data['Ev_Name'] ?? '';
    $event_ref_num = $event_data['Ev_RefNum'] ?? '';

    if (!$ev_id) {
        die("Invalid report reference.");
    }

    // Fetch committee members with COCU claims for this event
    $committee_query = $conn->prepare("
        SELECT Com_Name, Com_Email, Com_ID 
        FROM committee 
        WHERE Ev_ID = ? AND Com_COCUClaimers IN ('yes', '1')
    ");
    $committee_query->bind_param("s", $ev_id);
    $committee_query->execute();
    $committee_result = $committee_query->get_result();

    $committee_members = [];
    while ($member = $committee_result->fetch_assoc()) {
        $committee_members[] = $member;
    }

    if ($action === 'approve') {
        $status_id = 8;
        $stmt = $conn->prepare("UPDATE eventpostmortem SET Status_ID = ? WHERE Rep_ID = ?");
        $stmt->bind_param("is", $status_id, $rep_id);
        $stmt->execute();

        // ✅ Send post-event approval email to student and advisor
        $eventName = $details['Ev_Name'];
        $studentEmail = $details['Stu_Email'];
        $advisorEmail = $details['Adv_Email'];
        $advisorName = $details['Adv_Name']; // You might need to add this to the main query if it doesn't exist

        postEventApproved($eventName, $studentEmail, $advisorEmail, $advisorName);

        // ✅ Send notification to committee members with COCU claims
        if (!empty($committee_members)) {
            if (!empty($event_ref_num)) {
                postEventApprovedCommittee($event_name, $event_ref_num, $committee_members);
            } else {
                // Log warning: Event has no reference number
                error_log("Warning: Event {$ev_id} approved but has no reference number for COCU notification");
            }
        } else {
            // Optional: Log info that no committee members have COCU claims
            error_log("Info: Event {$ev_id} has no committee members with COCU claims");
        }

        header("Location: ../coordinator/CoordinatorDashboard.php");
        exit();
    } elseif ($action === 'reject') {
        if (empty($feedback)) {
            die("Feedback is required for rejection.");
        }

        $status_id = 7;

        // Update eventpostmortem status
        $update = $conn->prepare("UPDATE eventpostmortem SET Status_ID = ? WHERE Rep_ID = ?");
        $update->bind_param("is", $status_id, $rep_id);
        $update->execute();

        // Store feedback in eventcomment
        $sectionList = implode(", ", $rejectedSections); // For record
        $full_comment = "[Rejected Sections: $sectionList]\n\n$feedback";

        $insert = $conn->prepare("INSERT INTO eventcomment (Ev_ID, Status_ID, Reviewer_Comment, Updated_By, Comment_Type)
                                  VALUES (?, ?, ?, 'Coordinator', 'postmortem')");
        $insert->bind_param("sis", $ev_id, $status_id, $full_comment);
        $insert->execute();

        // ❌ Send post-event rejection email
        $eventName = $details['Ev_Name'];
        $studentName = $details['Stu_Name'];
        $studentEmail = $details['Stu_Email'];

        postEventRejected($eventName, $studentName, $studentEmail);

        header("Location: ../coordinator/CoordinatorDashboard.php");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Post Event Review - Coordinator Decision Form</title>
    <link href="../assets/css/coordinator/coorpostevent.css?v=<?= time() ?>" rel="stylesheet" />
    <style>
        /* Loading Screen Styles */
        .loading-screen {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-content {
            background: var(--container-beige);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--header-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Disabled button styles */
        .approve-btn:disabled {
            background: #6c757d !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }

        .approve-btn:disabled:hover {
            transform: none !important;
            background: #6c757d !important;
        }

        /* Individual report checkbox styles */
        .individual-report-checkboxes {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .individual-report-checkboxes .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .individual-report-checkboxes input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: var(--header-green);
        }

        .individual-report-checkboxes label {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .report-status {
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            margin-top: 0.3rem;
        }

        .report-status.approved {
            background-color: var(--header-green);
            color: white;
        }

        .report-status.rejected {
            background-color: #dc3545;
            color: white;
        }

        .report-status.pending {
            background-color: #ffc107;
            color: #212529;
        }
    </style>
</head>

<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text" id="loadingText">Processing...</div>
        </div>
    </div>

    <div class="header">
        <div class="header-left">
            <button class="back-btn" onclick="history.back()">←</button>
            <h1>Post Event Review</h1>
        </div>
        <button class="export-btn" onclick="exportToPDF()">📄 Export PDF</button>
    </div>

    <div class="floating-actions">
        <div class="floating-actions-title">Quick Actions</div>
        <button class="floating-btn approve-all" onclick="approveAll()">
            ✓ Approve All
        </button>
        <button class="floating-btn reject-all" onclick="rejectAll()">
            ✗ Reject All
        </button>
        <button class="floating-btn clear-all" onclick="clearAll()">
            ◯ Clear All
        </button>
    </div>

    <div class="container">
        <!-- Section 1: Poster -->
        <div class="section" data-section="poster">
            <div class="section-header">
                <div class="section-title">1. Event Poster</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="poster-approve" name="poster" value="approve" />
                        <label for="poster-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="poster-reject" name="poster" value="reject" />
                        <label for="poster-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <div class="poster-container">
                    <?php
                    $poster_path = '';
                    if (!empty($details['Ev_Poster'])) {
                        $poster_path = str_replace('../../', '../', htmlspecialchars($details['Ev_Poster']));
                    }
                    ?>

                    <div class="poster-container">
                        <?php if (!empty($poster_path)): ?>
                            <img src="<?= $poster_path ?>" alt="Event Poster"
                                style="max-height: 400px; border-radius: 8px;" />
                        <?php else: ?>
                            <div class="poster-placeholder">No poster uploaded.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Event Details -->
        <div class="section" data-section="details">
            <div class="section-header">
                <div class="section-title">2. Event Details</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="details-approve" name="details" value="approve" />
                        <label for="details-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="details-reject" name="details" value="reject" />
                        <label for="details-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <div class="event-details">
                    <div class="detail-item">
                        <div class="detail-label">Event Name</div>
                        <div class="detail-value"><?= htmlspecialchars($details['Ev_Name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Student Name</div>
                        <div class="detail-value"><?= htmlspecialchars($details['Stu_Name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Club Name</div>
                        <div class="detail-value"><?= htmlspecialchars($details['Club_Name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Objectives</div>
                        <div class="detail-value">
                            <?= nl2br(htmlspecialchars($details['Ev_Objectives'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Event Flow -->
        <div class="section" data-section="flow">
            <div class="section-header">
                <div class="section-title">3. Event Flow</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="flow-approve" name="flow" value="approve" />
                        <label for="flow-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="flow-reject" name="flow" value="reject" />
                        <label for="flow-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($event_flows->num_rows > 0): ?>
                            <?php while ($row = $event_flows->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['EvFlow_Time']) ?></td>
                                    <td><?= htmlspecialchars($row['EvFlow_Description']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center">No event flow submitted.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 4: Meeting -->
        <div class="section" data-section="meeting">
            <div class="section-header">
                <div class="section-title">4. Meeting Details</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="meeting-approve" name="meeting" value="approve" />
                        <label for="meeting-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="meeting-reject" name="meeting" value="reject" />
                        <label for="meeting-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Location</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($meeting_result->num_rows > 0): ?>
                            <?php while ($meeting = $meeting_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($meeting['Meeting_Date']) ?></td>
                                    <td><?= htmlspecialchars($meeting['Start_Time']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($meeting['End_Time'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($meeting['Meeting_Location'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($meeting['Meeting_Description'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No post-event meetings submitted.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 5: Challenges and Recommendations -->
        <div class="section" data-section="challenges">
            <div class="section-header">
                <div class="section-title">
                    5. Event Challenges and Recommendations
                </div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="challenges-approve" name="challenges" value="approve" />
                        <label for="challenges-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="challenges-reject" name="challenges" value="reject" />
                        <label for="challenges-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <div class="challenges-content">
                    <div class="challenges-section">
                        <h4>Challenges and Difficulties</h4>
                        <div class="challenges-text">
                            <?= nl2br(htmlspecialchars($details['Rep_ChallengesDifficulties'])) ?>
                        </div>

                        <h4>Recommendations</h4>
                        <div class="challenges-text">
                            <?= nl2br(htmlspecialchars($details['Rep_recomendation'])) ?>
                        </div>
                    </div>
                    <div class="challenges-section">
                        <h4>Conclusion</h4>
                        <div class="challenges-text">
                            <?= nl2br(htmlspecialchars($details['Rep_Conclusion'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Event Photos -->
        <div class="section" data-section="photos">
            <div class="section-header">
                <div class="section-title">6. Event Photos</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="photos-approve" name="photos" value="approve" />
                        <label for="photos-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="photos-reject" name="photos" value="reject" />
                        <label for="photos-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <div class="photo-grid">
                    <?php
                    $photoGridHTML = '';
                    if (!empty($details['rep_photo'])) {
                        $photos = json_decode($details['rep_photo'], true);
                        if (is_array($photos)) {
                            foreach ($photos as $index => $photoPath) {
                                $escapedPath = '../uploads/photos/' . basename($photoPath);
                                $photoGridHTML .= '
        <div class="photo-item" onclick="openPhotoModal(\'' . $escapedPath . '\')">
            <img src="' . $escapedPath . '" alt="Event Photo ' . ($index + 1) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;" />
        </div>
    ';
                            }

                        } else {
                            $photoGridHTML = '<p>No valid photos found.</p>';
                        }
                    } else {
                        $photoGridHTML = '<p>No event photos uploaded.</p>';
                    }
                    echo $photoGridHTML;
                    ?>

                </div>
            </div>
        </div>

        <!-- Section 7: Budget Statement -->
        <div class="section" data-section="budget">
            <div class="section-header">
                <div class="section-title">7. Budget Statement</div>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="budget-approve" name="budget" value="approve" />
                        <label for="budget-approve">Approve</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="budget-reject" name="budget" value="reject" />
                        <label for="budget-reject">Reject</label>
                    </div>
                </div>
            </div>
            <div class="section-content">
                <div class="budget-row">
                    <div class="budget-info">Budget Statement Document</div>
                    <button class="view-btn" onclick="viewBudget()">
                        📄 View Budget File
                    </button>
                </div>
            </div>
        </div>

        <!-- Section 8: Individual Reports for COCU Claimers -->
        <div class="section" data-section="reports">
            <div class="section-header">
                <div class="section-title">
                    8. Individual Reports for COCU Claimers
                </div>
                <!-- No overall checkbox group here since we have individual ones -->
            </div>
            <div class="section-content">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Position</th>
                            <th>Attendance %</th>
                            <th>Report</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset the result pointer for individual reports
                        $report_stmt = $conn->prepare("
                          SELECT 
                              c.Com_ID,
                              c.Com_Name,
                              c.Com_Position,
                              ir.IR_File
                          FROM eventpostmortem ep
                          JOIN committee c
                                ON c.Ev_ID = ep.Ev_ID
                               AND c.Com_COCUClaimers = 'yes'
                          LEFT JOIN individualreport ir
                                ON ir.Rep_ID = ep.Rep_ID
                               AND ir.Com_ID = c.Com_ID
                          WHERE ep.Rep_ID = ?
                          ORDER BY c.Com_Position, c.Com_Name
                        ");
                        $report_stmt->bind_param("s", $rep_id);
                        $report_stmt->execute();
                        $individual_reports = $report_stmt->get_result();

                        // Fetch total meetings
                        $totalMeetingQuery = $conn->prepare("SELECT COUNT(*) AS total FROM posteventmeeting WHERE Rep_ID = ?");
                        $totalMeetingQuery->bind_param("s", $rep_id);
                        $totalMeetingQuery->execute();
                        $totalMeetingResult = $totalMeetingQuery->get_result();
                        $totalMeetings = $totalMeetingResult->fetch_assoc()['total'] ?? 0;

                        while ($report = $individual_reports->fetch_assoc()):
                            $com_id = $report['Com_ID'];

                            // Fetch attendance count
                            $attendQuery = $conn->prepare("
                                SELECT COUNT(*) AS attended 
                                FROM committeeattendance 
                                WHERE Rep_ID = ? AND Com_ID = ? AND Attendance_Status = 'Present'
                            ");
                            $attendQuery->bind_param("ss", $rep_id, $com_id);
                            $attendQuery->execute();
                            $attendResult = $attendQuery->get_result();
                            $attended = $attendResult->fetch_assoc()['attended'] ?? 0;

                            $percentage = ($totalMeetings > 0) ? round(($attended / $totalMeetings) * 100, 2) : 0;
                            $attendanceClass = ($percentage >= 80) ? 'good' : (($percentage >= 60) ? 'average' : 'poor');

                            $reportFile = !empty($report['IR_File']) ? '../uploads/individualreports/' . basename($report['IR_File']) : '';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($report['Com_Name']) ?></td>
                                <td><?= htmlspecialchars($report['Com_ID']) ?></td>
                                <td><?= htmlspecialchars($report['Com_Position']) ?></td>
                                <td><span class="attendance-percentage <?= $attendanceClass ?>"><?= $percentage ?>%</span>
                                </td>
                                <td>
                                    <?php if (!empty($reportFile)): ?>
                                        <button class="view-btn" onclick="viewReport('<?= $reportFile ?>')">View
                                            Report</button>
                                    <?php else: ?>
                                        <span class="text-muted">No report</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($reportFile)): ?>
                                        <div class="individual-report-checkboxes">
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="report-<?= $com_id ?>-approve"
                                                    name="report-<?= $com_id ?>" value="approve" data-com-id="<?= $com_id ?>"
                                                    data-com-name="<?= htmlspecialchars($report['Com_Name']) ?>" />
                                                <label for="report-<?= $com_id ?>-approve">✓</label>
                                            </div>
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="report-<?= $com_id ?>-reject"
                                                    name="report-<?= $com_id ?>" value="reject" data-com-id="<?= $com_id ?>"
                                                    data-com-name="<?= htmlspecialchars($report['Com_Name']) ?>" />
                                                <label for="report-<?= $com_id ?>-reject">✗</label>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="approve-btn" id="approveEventBtn" onclick="approveEvent()" disabled>
                ✓ Approve Event
            </button>
            <button class="reject-btn" onclick="showRejectModal()">
                ✗ Reject Event
            </button>
        </div>
    </div>

    <!-- Photo Modal -->
    <div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
        <div class="photo-modal-content" onclick="event.stopPropagation()">
            <div class="photo-modal-header">
                <div class="photo-modal-title" id="photoModalTitle">Photo</div>
                <button class="photo-close-btn" onclick="closePhotoModal()">
                    &times;
                </button>
            </div>
            <div class="photo-modal-image" id="photoModalImage">
                <!-- Photo content will be displayed here -->
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Event - Feedback Required</h3>
                <button class="close-btn" onclick="closeRejectModal()">
                    &times;
                </button>
            </div>
            <div>
                <label for="rejectedSections">Rejected Sections:</label>
                <div id="rejectedSections" style="
              background: var(--accent-color);
              padding: 1rem;
              margin: 1rem 0;
              border-radius: 6px;
              min-height: 50px;
            ">
                    <!-- Rejected sections will be populated here -->
                </div>

                <label for="feedbackText">Feedback:</label>
                <textarea id="feedbackText" class="feedback-textarea"
                    placeholder="Please provide detailed feedback for the rejection..."></textarea>
            </div>
            <div class="modal-actions">
                <button class="view-btn" onclick="closeRejectModal()">Cancel</button>
                <button class="reject-btn" onclick="submitRejection()">
                    Submit Rejection
                </button>
            </div>
        </div>
    </div>
    <!-- Scroll Arrow Buttons -->
    <div class="scroll-arrows" id="scrollArrows">
        <button class="scroll-arrow-btn scroll-up" id="scrollUpBtn" title="Scroll to top"></button>
        <button class="scroll-arrow-btn scroll-down" id="scrollDownBtn" title="Scroll to bottom"></button>
    </div>
    <script>
        // Global variables for validation
        const sections = ["poster", "details", "flow", "meeting", "challenges", "photos", "budget"];
        const individualReportIds = []; // Will be populated dynamically

        // Show loading screen
        function showLoading(message) {
            const loadingScreen = document.getElementById("loadingScreen");
            const loadingText = document.getElementById("loadingText");
            loadingText.textContent = message;
            loadingScreen.style.display = "flex";
        }

        // Hide loading screen
        function hideLoading() {
            const loadingScreen = document.getElementById("loadingScreen");
            loadingScreen.style.display = "none";
        }

        // Get all individual report committee IDs
        function getIndividualReportIds() {
            const reportCheckboxes = document.querySelectorAll('input[data-com-id]');
            const ids = new Set();
            reportCheckboxes.forEach(checkbox => {
                ids.add(checkbox.getAttribute('data-com-id'));
            });
            return Array.from(ids);
        }

        // Validate approval button state
        function validateApprovalButton() {
            const approveBtn = document.getElementById("approveEventBtn");
            let allApproved = true;

            // Check main sections
            sections.forEach((section) => {
                const approveCheckbox = document.getElementById(`${section}-approve`);
                const rejectCheckbox = document.getElementById(`${section}-reject`);

                // If nothing is selected OR reject is selected, disable approve
                if (!approveCheckbox.checked || rejectCheckbox.checked) {
                    allApproved = false;
                }
            });

            // Check individual reports - all must be approved
            const reportIds = getIndividualReportIds();
            reportIds.forEach((comId) => {
                const approveCheckbox = document.getElementById(`report-${comId}-approve`);
                const rejectCheckbox = document.getElementById(`report-${comId}-reject`);

                // If report exists but not approved or is rejected, disable approve
                if (approveCheckbox && (!approveCheckbox.checked || (rejectCheckbox && rejectCheckbox.checked))) {
                    allApproved = false;
                }
            });

            approveBtn.disabled = !allApproved;
        }

        // Checkbox functionality
        document.addEventListener("DOMContentLoaded", function () {
            // Handle main section checkbox interactions
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:not([data-com-id])');
            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener("change", function () {
                    const section = this.name;
                    const value = this.value;
                    const otherValue = value === "approve" ? "reject" : "approve";
                    const otherCheckbox = document.getElementById(`${section}-${otherValue}`);

                    if (this.checked && otherCheckbox) {
                        otherCheckbox.checked = false;
                    }

                    validateApprovalButton();
                });
            });

            // Handle individual report checkbox interactions
            const reportCheckboxes = document.querySelectorAll('input[data-com-id]');
            reportCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener("change", function () {
                    const comId = this.getAttribute('data-com-id');
                    const value = this.value;
                    const otherValue = value === "approve" ? "reject" : "approve";
                    const otherCheckbox = document.getElementById(`report-${comId}-${otherValue}`);

                    if (this.checked && otherCheckbox) {
                        otherCheckbox.checked = false;
                    }

                    validateApprovalButton();
                });
            });

            // Initial validation
            validateApprovalButton();
        });

        function approveAll() {
            // Approve all main sections
            const approveCheckboxes = document.querySelectorAll('input[value="approve"]:not([data-com-id])');
            const rejectCheckboxes = document.querySelectorAll('input[value="reject"]:not([data-com-id])');

            approveCheckboxes.forEach((checkbox) => (checkbox.checked = true));
            rejectCheckboxes.forEach((checkbox) => (checkbox.checked = false));

            // Approve all individual reports
            const reportApproveCheckboxes = document.querySelectorAll('input[data-com-id][value="approve"]');
            const reportRejectCheckboxes = document.querySelectorAll('input[data-com-id][value="reject"]');

            reportApproveCheckboxes.forEach((checkbox) => (checkbox.checked = true));
            reportRejectCheckboxes.forEach((checkbox) => (checkbox.checked = false));

            validateApprovalButton();
        }

        function rejectAll() {
            // Reject all main sections
            const approveCheckboxes = document.querySelectorAll('input[value="approve"]:not([data-com-id])');
            const rejectCheckboxes = document.querySelectorAll('input[value="reject"]:not([data-com-id])');

            approveCheckboxes.forEach((checkbox) => (checkbox.checked = false));
            rejectCheckboxes.forEach((checkbox) => (checkbox.checked = true));

            // Reject all individual reports
            const reportApproveCheckboxes = document.querySelectorAll('input[data-com-id][value="approve"]');
            const reportRejectCheckboxes = document.querySelectorAll('input[data-com-id][value="reject"]');

            reportApproveCheckboxes.forEach((checkbox) => (checkbox.checked = false));
            reportRejectCheckboxes.forEach((checkbox) => (checkbox.checked = true));

            validateApprovalButton();
        }

        function clearAll() {
            const allCheckboxes = document.querySelectorAll('input[type="checkbox"]');
            allCheckboxes.forEach((checkbox) => (checkbox.checked = false));

            validateApprovalButton();
        }

        function approveEvent() {
            if (confirm("Are you sure you want to approve this post-event report?")) {
                showLoading("Approving event...");

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                form.innerHTML = `
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="rep_id" value="<?= $rep_id ?>">
        `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showRejectModal() {
            const rejectedSections = getRejectedSections();
            const rejectedSectionsDiv = document.getElementById("rejectedSections");

            if (rejectedSections.length > 0) {
                rejectedSectionsDiv.innerHTML = rejectedSections
                    .map(
                        (section) =>
                            `<span style="background: #dc3545; color: white; padding: 0.3rem 0.6rem; border-radius: 4px; margin: 0.2rem; display: inline-block;">${section}</span>`
                    )
                    .join("");
            } else {
                rejectedSectionsDiv.innerHTML =
                    '<span style="color: #666;">No sections rejected</span>';
            }

            document.getElementById("rejectModal").style.display = "block";
        }

        function closeRejectModal() {
            document.getElementById("rejectModal").style.display = "none";
            document.getElementById("feedbackText").value = "";
        }

        function getRejectedSections() {
            const rejectedSections = [];

            // Check main sections
            sections.forEach((section) => {
                const rejectCheckbox = document.getElementById(`${section}-reject`);
                if (rejectCheckbox && rejectCheckbox.checked) {
                    const sectionNames = {
                        poster: "Event Poster",
                        details: "Event Details",
                        flow: "Event Flow",
                        meeting: "Meeting Details",
                        challenges: "Challenges and Recommendations",
                        photos: "Event Photos",
                        budget: "Budget Statement"
                    };
                    rejectedSections.push(sectionNames[section]);
                }
            });

            // Check individual reports
            const reportIds = getIndividualReportIds();
            reportIds.forEach((comId) => {
                const rejectCheckbox = document.getElementById(`report-${comId}-reject`);
                if (rejectCheckbox && rejectCheckbox.checked) {
                    const comName = rejectCheckbox.getAttribute('data-com-name');
                    rejectedSections.push(`Individual Report - ${comName}`);
                }
            });

            return rejectedSections;
        }

        function submitRejection() {
            const feedback = document.getElementById("feedbackText").value.trim();
            const rejectedSections = getRejectedSections();

            if (!feedback) {
                alert("Please provide feedback for the rejection.");
                return;
            }

            if (rejectedSections.length === 0) {
                alert("Please select at least one section to reject.");
                return;
            }

            if (confirm("Are you sure you want to reject this event with feedback?")) {
                showLoading("Rejecting event...");

                // Create hidden form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                form.innerHTML = `
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="rep_id" value="<?= $rep_id ?>">
            <input type="hidden" name="feedback" value="${feedback}">
        `;

                // For each rejected section
                rejectedSections.forEach(section => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'rejected_sections[]';
                    hiddenInput.value = section;
                    form.appendChild(hiddenInput);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportToPDF() {
            showLoading("Generating PDF...");
            // Link to the PDF generation with the current report ID
            window.open(`../components/pdf/reportgeneratepdf.php?id=<?= $rep_id ?>`, '_blank');
            setTimeout(() => {
                hideLoading();
            }, 2000); // Hide loading after 2 seconds
        }

        function viewBudget() {
            <?php if (!empty($details['BudgetStatement'])): ?>
                window.open("<?= '../uploads/statements/' . basename($details['BudgetStatement']) ?>", "_blank");
            <?php else: ?>
                alert("No budget statement uploaded.");
            <?php endif; ?>
        }

        function viewReport(filePath) {
            window.open(filePath, "_blank");
        }

        function openPhotoModal(photoPath) {
            const modal = document.getElementById("photoModal");
            const title = document.getElementById("photoModalTitle");
            const image = document.getElementById("photoModalImage");

            title.textContent = "Event Photo";
            image.innerHTML = `<img src="${photoPath}" alt="Event Photo" style="max-width: 100%; height: auto; border-radius: 10px;" />`;

            modal.style.display = "block";
            document.body.style.overflow = "hidden";
        }

        function closePhotoModal() {
            const modal = document.getElementById("photoModal");
            modal.style.display = "none";
            document.body.style.overflow = "auto"; // Restore scrolling
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById("rejectModal");
            if (event.target === modal) {
                closeRejectModal();
            }
        };

        // Handle escape key to close modal
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeRejectModal();
                closePhotoModal();
            }
        });

    </script>
</body>

</html>