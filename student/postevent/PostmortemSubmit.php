<?php
session_start();
include('../../db/dbconfig.php');
include('../../model/sendMailTemplates.php');

$mode = $_GET['mode'] ?? '';

if ($mode !== 'create') {
    die("Invalid mode.");
}

// Step 1: Validate session data
if (!isset($_SESSION['post_event_data'])) {
    die("Session expired or data missing.");
}

$postData = $_SESSION['post_event_data'];
$event_id = $postData['event_id'];

// Step 2: Generate Rep_ID
$query = "SELECT MAX(Rep_ID) AS last_id FROM EventPostmortem";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$report_id = $row['last_id'] ? str_pad((int) $row['last_id'] + 1, 4, '0', STR_PAD_LEFT) : '0001';

// Step 3: Handle file uploads
$photo_paths = $postData['photo_filenames'] ?? [];
$photos = json_encode($photo_paths); // Store in rep_photo

// 2. Upload Budget Statement
$budgetFileName = $postData['budget_statement'] ?? null;

// Start Transaction
$conn->begin_transaction();

try {
    // Step 4: Insert into EventPostmortem
    $stmt = $conn->prepare("
        INSERT INTO eventpostmortem (
    Rep_ID, Ev_ID, Rep_ChallengesDifficulties,
    Rep_Conclusion, Rep_recomendation, rep_photo, Status_ID
) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $status_id = 6; // Postmortem Pending Review
    $stmt->bind_param(
        "ssssssi",
        $report_id,
        $event_id,
        $postData['challenges'],
        $postData['conclusion'],
        $postData['recommendation'],
        $photos,
        $status_id
    );
    $stmt->execute();

    // Step 5: Insert Event Flows
    foreach ($postData['event_flows'] as $flow) {
        $stmt = $conn->prepare("INSERT INTO eventflows (Rep_ID, EvFlow_Time, EvFlow_Description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $report_id, $flow['time'], $flow['description']);
        $stmt->execute();
    }

    // Step 6: Insert Meetings + Attendance
    $attendanceJSON = file_get_contents("php://input");
    $attendanceData = json_decode($attendanceJSON, true)['attendance'] ?? [];

    foreach ($postData['meetings'] as $index => $meeting) {
        // Insert meeting
        $stmt = $conn->prepare("
    INSERT INTO posteventmeeting 
    (Rep_ID, Meeting_Date, Start_Time, End_Time, Meeting_Description, Meeting_Location)
    VALUES (?, ?, ?, ?, ?, ?)
");
        $stmt->bind_param(
            "ssssss",
            $report_id,
            $meeting['date'],
            $meeting['start_time'],
            $meeting['end_time'],
            $meeting['description'],
            $meeting['location']
        );

        $stmt->execute();
        $meeting_id = $conn->insert_id;

        // Insert attendance
        foreach ($attendanceData as $com_id => $statuses) {
            $status = $statuses[$index] ?? 'Absent'; // Default if missing
            $stmt2 = $conn->prepare("
            INSERT INTO committeeattendance (Rep_ID, Meeting_ID, Com_ID, Attendance_Status)
            VALUES (?, ?, ?, ?)
        ");
            $stmt2->bind_param("siss", $report_id, $meeting_id, $com_id, $status);
            $stmt2->execute();
        }
    }

    // Step 7: Insert individual reports
    $individualReports = $postData['individual_reports'] ?? [];
    if (!empty($individualReports)) {
        foreach ($individualReports as $comId => $fileName) {
            $stmt = $conn->prepare("INSERT INTO individualreport (Rep_ID, Com_ID, IR_File) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $report_id, $comId, $fileName);
            $stmt->execute();
        }
    }

    // Step 9: Update Budget Statement path (if uploaded)
    if ($budgetFileName) {
        $stmt = $conn->prepare("UPDATE budgetsummary SET statement = ? WHERE Ev_ID = ?");
        $stmt->bind_param("ss", $budgetFileName, $event_id);
        $stmt->execute();
    }

    // === Fetch event name + club_id
    $stmt = $conn->prepare("SELECT Ev_Name, Club_ID, Stu_ID FROM events WHERE Ev_ID = ?");
    $stmt->bind_param("s", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $eventName = $row['Ev_Name'];
    $clubID = $row['Club_ID'];
    $studentID = $row['Stu_ID'];
    $stmt->close();

    // === Fetch student name
    $stmt = $conn->prepare("SELECT Stu_Name FROM student WHERE Stu_ID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentName = $result->fetch_assoc()['Stu_Name'];
    $stmt->close();

    // === Fetch coordinator email (get the first coordinator as default)
    $stmt = $conn->prepare("SELECT Coor_Name, Coor_Email FROM coordinator LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $coordinatorName = $row['Coor_Name'];
        $coordinatorEmail = $row['Coor_Email'];
    } else {
        // Fallback if no coordinator found
        $coordinatorName = "Coordinator";
        $coordinatorEmail = "coordinator@university.edu"; // Set your default coordinator email
    }
    $stmt->close();

    // ✅ Send email
    postEventSubmitted($coordinatorName, $eventName, $studentName, $coordinatorEmail);

    $conn->commit();
    unset($_SESSION['post_event_data']);

    // Store report details for PDF generation
    $_SESSION['last_report'] = [
        'report_id' => $report_id,
        'event_id' => $event_id,
        'event_name' => $eventName,
        'student_name' => $studentName,
        'submission_date' => date('Y-m-d H:i:s')
    ];

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Event Report Submitted Successfully</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .background-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .floating-shapes {
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .shape-1 {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
            transform: scale(1.2);
        }

        .shape-2 {
            top: 20%;
            right: 10%;
            animation-delay: 2s;
            transform: scale(0.8);
        }

        .shape-3 {
            bottom: 20%;
            left: 15%;
            animation-delay: 4s;
            transform: scale(1.5);
        }

        .shape-4 {
            bottom: 10%;
            right: 20%;
            animation-delay: 1s;
            transform: scale(0.9);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .success-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            text-align: center;
            max-width: 520px;
            width: 100%;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s infinite;
            box-shadow: 0 15px 35px rgba(240, 147, 251, 0.3);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
            animation: checkMark 0.8s ease-in-out 0.3s both;
        }

        @keyframes checkMark {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }

            50% {
                opacity: 1;
                transform: scale(1.2);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .success-message {
            font-size: 1.1rem;
            color: #636e72;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .report-details {
            background: linear-gradient(135deg, #fef7ff 0%, #fff0f5 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid #f093fb;
        }

        .report-id {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }

        .event-name {
            font-size: 1rem;
            color: #636e72;
            margin-bottom: 0.3rem;
        }

        .submission-time {
            font-size: 0.9rem;
            color: #f5576c;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn-custom {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(240, 147, 251, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(240, 147, 251, 0.4);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(79, 172, 254, 0.4);
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        @media (max-width: 576px) {
            .success-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .success-title {
                font-size: 1.8rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
            }
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f093fb;
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

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.6);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(240, 147, 251, 0.2);
        }

        .info-item i {
            color: #f093fb;
            margin-bottom: 0.5rem;
        }

        .info-item h6 {
            color: #2d3436;
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .info-item p {
            color: #636e72;
            font-size: 0.85rem;
            margin: 0;
        }

        @media (max-width: 576px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="background-animation">
        <div class="floating-shapes shape-1"></div>
        <div class="floating-shapes shape-2"></div>
        <div class="floating-shapes shape-3"></div>
        <div class="floating-shapes shape-4"></div>
    </div>

    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-clipboard-check"></i>
        </div>

        <div class="status-badge">
            <i class="fas fa-paper-plane"></i>
            Report Submitted Successfully
        </div>

        <h1 class="success-title">Post-Event Report Submitted!</h1>

        <p class="success-message">
            Your post-event report has been successfully submitted and is now pending coordinator review.
        </p>

        <div class="report-details">
            <div class="report-id">
                <i class="fas fa-file-alt me-2"></i>
                Report ID: <?= htmlspecialchars($report_id) ?>
            </div>
            <div class="event-name">
                <i class="fas fa-calendar-check me-2"></i>
                <?= htmlspecialchars($eventName) ?>
            </div>
        </div>



        <div class="action-buttons">
            <a href="../StudentDashboard.php" class="btn-custom btn-primary">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
            <button onclick="exportReportPDF()" class="btn-custom btn-secondary">
                <i class="fas fa-file-pdf"></i>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Generating Report PDF...</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>

        function exportReportPDF() {
            // Use the report_id stored in session last_report (PHP echo) if available
            const reportId = "<?= isset($_SESSION['last_report']['report_id']) ? $_SESSION['last_report']['report_id'] : '' ?>";

            if (!reportId) {
                alert("Report ID not found. Cannot export PDF.");
                return;
            }

            // Build the PDF URL
            const pdfUrl = `../../components/pdf/reportgeneratepdf.php?id=${encodeURIComponent(reportId)}`;

            // Open in a new tab
            window.open(pdfUrl, "_blank");
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;

            // Add toast styles
            const style = document.createElement('style');
            style.textContent = `
                .toast-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    z-index: 10000;
                    animation: slideInRight 0.3s ease;
                }
                .toast-success {
                    border-left: 4px solid #fd79a8;
                    color: #fd79a8;
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(toast);

            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
                style.remove();
            }, 3000);
        }

        // Add interactive effects
        document.addEventListener('DOMContentLoaded', function () {
            // Add hover effect to buttons
            const buttons = document.querySelectorAll('.btn-custom');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-2px)';
                });

                button.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add click effect
            buttons.forEach(button => {
                button.addEventListener('mousedown', function () {
                    this.style.transform = 'translateY(0) scale(0.98)';
                });

                button.addEventListener('mouseup', function () {
                    this.style.transform = 'translateY(-2px) scale(1)';
                });
            });
        });
    </script>
</body>

</html>