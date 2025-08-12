<?php
session_start();
include('../db/dbconfig.php');

$rep_id = $_GET['rep_id'] ?? '';

if (empty($rep_id)) {
    die("Invalid access.");
}

// Get basic report info
$stmt = $conn->prepare("SELECT Ev_ID, Status_ID FROM eventpostmortem WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Post-event report not found.");
}

$row = $result->fetch_assoc();
$ev_id = $row['Ev_ID'];
$status_id = (int) $row['Status_ID'];

// Get status name and class
$statusName = '';
$statusClass = 'status-default';
switch ($status_id) {
    case 6:
        $statusName = 'Under Review';
        $statusClass = 'status-under-review';
        break;
    case 7:
        $statusName = 'Rejected';
        $statusClass = 'status-rejected';
        break;
    case 8:
        $statusName = 'Approved';
        $statusClass = 'status-approved';
        break;
    default:
        $statusName = 'Unknown Status';
        $statusClass = 'status-default';
        break;
}

// Get event information
$proposerName = '';
$eventName = '';
$clubName = '';
$objectives = '';

$stmt = $conn->prepare("
    SELECT s.Stu_Name, e.Ev_Name, c.Club_Name, e.Ev_Objectives
    FROM events e
    JOIN student s ON e.Stu_ID = s.Stu_ID
    JOIN club c ON e.Club_ID = c.Club_ID
    WHERE e.Ev_ID = ?
");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$stmt->bind_result($proposerName, $eventName, $clubName, $objectives);
$stmt->fetch();
$stmt->close();

// Get event flows
$eventFlows = [];
$stmt = $conn->prepare("SELECT EvFlow_Time, EvFlow_Description FROM eventflows WHERE Rep_ID = ? ORDER BY EvFlow_Time");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $eventFlows[] = $row;
}
$stmt->close();

// Get meetings
$meetings = [];
$stmt = $conn->prepare("SELECT * FROM posteventmeeting WHERE Rep_ID = ? ORDER BY Meeting_Date, Start_Time");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$meetingResult = $stmt->get_result();

while ($row = $meetingResult->fetch_assoc()) {
    $meetings[] = $row;
}
$stmt->close();

// Get attendance
$attendance = [];
$stmt = $conn->prepare("SELECT * FROM committeeattendance WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$attendanceResult = $stmt->get_result();

while ($row = $attendanceResult->fetch_assoc()) {
    $attendance[$row['Com_ID']][$row['Meeting_ID']] = $row['Attendance_Status'];
}
$stmt->close();

// Get photos
$photoFilenames = [];
$stmt = $conn->prepare("SELECT rep_photo FROM eventpostmortem WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$stmt->bind_result($photoJSON);
$stmt->fetch();
$stmt->close();

$photoFilenames = json_decode($photoJSON, true);
$photoFilenames = is_array($photoFilenames) ? $photoFilenames : [];

// Get budget statement
$budgetStatementFile = null;
$stmt = $conn->prepare("SELECT statement FROM budgetsummary WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$stmt->bind_result($budgetStatementFile);
$stmt->fetch();
$stmt->close();

// Get challenges, recommendations, and conclusion
$challenges = '';
$recommendation = '';
$conclusion = '';

$stmt = $conn->prepare("
    SELECT Rep_ChallengesDifficulties, Rep_recomendation, Rep_Conclusion 
    FROM eventpostmortem 
    WHERE Rep_ID = ?
");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$stmt->bind_result($challenges, $recommendation, $conclusion);
$stmt->fetch();
$stmt->close();

// Get individual reports
$individualReports = [];
$stmt = $conn->prepare("SELECT Com_ID, IR_File FROM individualreport WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $individualReports[$row['Com_ID']] = $row['IR_File'];
}
$stmt->close();

// Get committee members
$committeeMembers = [];
$stmt = $conn->prepare("SELECT Com_ID, Com_Name, Com_Position, Com_COCUClaimers FROM committee WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $committeeMembers[] = $row;
}
$stmt->close();

// Get attendance data
$attendanceData = [];
$stmt = $conn->prepare("SELECT Meeting_ID, Com_ID, Attendance_Status FROM committeeattendance WHERE Rep_ID = ?");
$stmt->bind_param("s", $rep_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $com_id = $row['Com_ID'];
    $meeting_id = $row['Meeting_ID'];
    $status = $row['Attendance_Status'];
    $attendanceData[$com_id][$meeting_id] = $status;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Event Report - View</title>
    <style>
        :root {
            --primary-medium: #9de5ff;
            --primary-dark: #aca8ff;
            --primary-purple: #ac73ff;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Arial", sans-serif;
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-dark));
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-purple), var(--primary-dark));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-under-review {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .status-default {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            background: #fafafa;
        }

        .section-title {
            font-size: 1.8em;
            color: var(--primary-purple);
            margin-bottom: 20px;
            border-bottom: 3px solid var(--primary-medium);
            padding-bottom: 10px;
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .info-label {
            font-weight: bold;
            color: #333;
            min-width: 180px;
            margin-right: 20px;
        }

        .info-value {
            flex: 1;
            color: #555;
            line-height: 1.5;
        }

        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: var(--primary-medium);
            font-weight: bold;
            color: #333;
        }

        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .photo-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .photo-item:hover {
            transform: scale(1.05);
        }

        .photo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .file-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-name {
            font-weight: bold;
            color: #333;
        }

        .btn {
            background: var(--primary-purple);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #9a5bff;
        }

        .btn-secondary {
            background: var(--primary-dark);
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-back:hover {
            background: #545b62;
        }

        .attendance-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .meeting-date {
            font-weight: bold;
            min-width: 80px;
            color: #333;
        }

        .status-indicator {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-present {
            background: #d4edda;
            color: var(--success-color);
        }

        .status-absent {
            background: #f8d7da;
            color: var(--danger-color);
        }

        .status-not-set {
            background: #fff3cd;
            color: var(--warning-color);
        }

        .floating-nav {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .floating-btn {
            background: var(--primary-purple);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            min-height: 36px;
            min-width: 100px;
            position: relative;
        }

        .floating-btn:hover {
            background: #9a5bff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .floating-btn-secondary {
            background: #28a745;
        }

        .floating-btn-secondary:hover {
            background: #218838;
        }

        .floating-btn-back {
            background: #6c757d;
        }

        .floating-btn-back:hover {
            background: #545b62;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            max-height: 80%;
            object-fit: contain;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #bbb;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .scroll-arrows {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 999;
        }

        .scroll-btn {
            background: var(--primary-purple);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .scroll-btn:hover {
            background: #9a5bff;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 768px) {
            .scroll-arrows {
                right: 10px;
            }

            .scroll-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }

        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
            }

            .info-label {
                min-width: auto;
                margin-bottom: 5px;
            }

            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .floating-nav {
                right: 10px;
                bottom: 10px;
            }

            .floating-btn {
                min-width: 85px;
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Fixed Navigation Buttons -->
    <div class="floating-nav">
        <a href="../student/postevent/PostEventEdit_form.php?mode=edit&rep_id=<?= htmlspecialchars($rep_id) ?>"
            class="floating-btn">
            ✏️ Edit
        </a>
        <a href="../components/pdf/reportgeneratepdf.php?id=<?= urlencode($rep_id) ?>" target="_blank"
            class="floating-btn floating-btn-secondary">
            📄 Export PDF
        </a>

        <button onclick="window.history.back()" class="floating-btn floating-btn-back">
            Return
        </button>
    </div>

    <div class="container">
        <div class="header">
            <div class="status-badge <?= $statusClass ?>">Status: <?= htmlspecialchars($statusName) ?></div>
            <h1>Post Event Report</h1>
            <p>Event Report View</p>
        </div>

        <div class="content">
            <!-- Section 1: Event Information -->
            <div class="section">
                <h2 class="section-title">1. Event Information</h2>
                <div class="info-row">
                    <div class="info-label">Proposer Name:</div>
                    <div class="info-value"><?= htmlspecialchars($proposerName) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Event Name:</div>
                    <div class="info-value"><?= htmlspecialchars($eventName) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Club Name:</div>
                    <div class="info-value"><?= htmlspecialchars($clubName) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Event Objectives:</div>
                    <div class="info-value"><?= htmlspecialchars($objectives) ?: 'Not specified' ?></div>
                </div>
            </div>

            <!-- Section 2: Event Flow -->
            <div class="section">
                <h2 class="section-title">2. Event Flow (Event Day)</h2>
                <?php if (!empty($eventFlows)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventFlows as $flow): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('H:i', strtotime($flow['EvFlow_Time']))) ?></td>
                                        <td><?= htmlspecialchars($flow['EvFlow_Description']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No event flow information recorded.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 3: Meetings -->
            <div class="section">
                <h2 class="section-title">3. Meetings</h2>
                <?php if (!empty($meetings)): ?>
                    <div class="table-container">
                        <table>
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
                                <?php foreach ($meetings as $meeting): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('M d, Y', strtotime($meeting['Meeting_Date']))) ?></td>
                                        <td><?= htmlspecialchars(date('H:i', strtotime($meeting['Start_Time']))) ?></td>
                                        <td><?= htmlspecialchars(date('H:i', strtotime($meeting['End_Time']))) ?></td>
                                        <td><?= htmlspecialchars($meeting['Meeting_Location']) ?></td>
                                        <td><?= htmlspecialchars($meeting['Meeting_Description']) ?: 'No description' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No meetings recorded.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 4: Uploads -->
            <div class="section">
                <h2 class="section-title">4. Uploads</h2>

                <!-- Event Photos -->
                <div class="info-row">
                    <div class="info-label">Event Photos:</div>
                    <div class="info-value">
                        <?php if (!empty($photoFilenames)): ?>
                            <div class="photo-gallery">
                                <?php foreach ($photoFilenames as $photo): ?>
                                    <div class="photo-item"
                                        onclick="openModal('../../uploads/photos/<?= htmlspecialchars($photo) ?>')">
                                        <img src="../uploads/photos/<?= htmlspecialchars($photo) ?>" alt="Event Photo">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <em>No photos uploaded</em>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Budget Statement -->
                <div class="info-row">
                    <div class="info-label">Budget Statement:</div>
                    <div class="info-value">
                        <?php if (!empty($budgetStatementFile)): ?>
                            <a href="../uploads/statements/<?= htmlspecialchars($budgetStatementFile) ?>" target="_blank"
                                class="btn">View</a>
                        <?php else: ?>
                            <em>No budget statement uploaded</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Section 5: Challenges and Recommendations -->
            <div class="section">
                <h2 class="section-title">5. Challenges and Recommendations</h2>
                <div class="info-row">
                    <div class="info-label">Challenges and Difficulties:</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($challenges)) ?: 'Not specified' ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Recommendations:</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($recommendation)) ?: 'Not specified' ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Conclusion:</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($conclusion)) ?: 'Not specified' ?></div>
                </div>
            </div>

            <!-- Section 6: COCU Claimer Attendance -->
            <div class="section">
                <h2 class="section-title">6. COCU Claimer Attendance</h2>
                <?php
                $cocuClaimers = array_filter($committeeMembers, function ($member) {
                    return $member['Com_COCUClaimers'] === 'yes';
                });
                ?>

                <?php if (!empty($cocuClaimers) && !empty($meetings)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>COCU Claimer Name</th>
                                    <th>Meeting Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cocuClaimers as $member): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($member['Com_Name']) ?></strong></td>
                                        <td>
                                            <?php foreach ($meetings as $meeting): ?>
                                                <?php
                                                $meetingID = $meeting['Meeting_ID'];
                                                $status = $attendanceData[$member['Com_ID']][$meetingID] ?? 'not-set';
                                                $statusClass = $status === 'present' ? 'status-present' :
                                                    ($status === 'absent' ? 'status-absent' : 'status-not-set');
                                                $statusText = $status === 'present' ? 'Present' :
                                                    ($status === 'absent' ? 'Absent' : 'Not Set');
                                                ?>
                                                <div class="attendance-row">
                                                    <span
                                                        class="meeting-date"><?= htmlspecialchars(date('M d', strtotime($meeting['Meeting_Date']))) ?>:</span>
                                                    <span class="status-indicator <?= $statusClass ?>"><?= $statusText ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <?php if (empty($cocuClaimers)): ?>
                            No COCU claimers found.
                        <?php else: ?>
                            No meetings scheduled for attendance tracking.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 7: Individual Reports -->
            <div class="section">
                <h2 class="section-title">7. Individual Reports (COCU Claimers)</h2>
                <?php if (!empty($cocuClaimers)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Position</th>
                                    <th>Report</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cocuClaimers as $member): ?>
                                    <?php
                                    $comId = $member['Com_ID'];
                                    $reportFile = $individualReports[$comId] ?? '';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['Com_Name']) ?></td>
                                        <td><?= htmlspecialchars($comId) ?></td>
                                        <td><?= htmlspecialchars($member['Com_Position']) ?></td>
                                        <td>
                                            <?php if (!empty($reportFile)): ?>
                                                <a href="../uploads/individualreports/<?= htmlspecialchars($reportFile) ?>"
                                                    target="_blank" class="btn btn-secondary">View</a>
                                            <?php else: ?>
                                                <em>No report submitted</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No COCU claimers found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for image preview -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    <!-- Scroll Arrow Buttons -->
    <div class="scroll-arrows">
        <button class="scroll-btn" onclick="scrollToTop()" title="Scroll to top">
            ↑
        </button>
        <button class="scroll-btn" onclick="scrollToBottom()" title="Scroll to bottom">
            ↓
        </button>
    </div>
    <script>
        function openModal(imageSrc) {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            modal.style.display = "block";
            modalImg.src = imageSrc;
        }
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }
        function closeModal() {
            document.getElementById("imageModal").style.display = "none";
        }

        // Close modal when clicking outside the image
        window.addEventListener("click", function (event) {
            const modal = document.getElementById("imageModal");
            if (event.target === modal) {
                closeModal();
            }
        });

        // Print styling
        window.addEventListener('beforeprint', function () {
            document.body.style.background = 'white';
            document.querySelector('.floating-nav').style.display = 'none';
        });

        window.addEventListener('afterprint', function () {
            document.body.style.background = '';
            document.querySelector('.floating-nav').style.display = 'flex';
        });
    </script>
</body>

</html>