<?php
include('../../db/dbconfig.php');
include('../../model/sendMailTemplates.php');
session_start();

if ($_GET['mode'] !== 'create') {
    die("Invalid request mode.");
}

$stu_id = $_SESSION['Stu_ID'];

// Generate Event ID: NN/YY
$year_suffix = date('y');
$lastEventQuery = "SELECT MAX(Ev_ID) AS last_id FROM events";
$result = $conn->query($lastEventQuery);
$row = $result->fetch_assoc();

if ($row['last_id']) {
    preg_match('/^(\d+)/', $row['last_id'], $matches);
    $last_num = isset($matches[1]) ? (int) $matches[1] : 0;
    $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
} else {
    $new_num = '01';
}
$event_id = $new_num . '/' . $year_suffix;

// Upload Poster
$poster = null;
if (!empty($_FILES["eventPoster"]["name"])) {
    $target_dir = "../../uploads/posters/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $original_name = basename($_FILES["eventPoster"]["name"]);
    $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_name);
    $target_file = $target_dir . time() . '_' . $safe_name;
    if (move_uploaded_file($_FILES["eventPoster"]["tmp_name"], $target_file)) {
        $poster = $target_file;
    } else {
        die("Poster upload failed.");
    }
}

// Upload Additional Info
$additional_info_path = null;
if (!empty($_FILES["additionalDocument"]["name"])) {
    $target_dir = "../../uploads/additional/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $original_name = basename($_FILES["additionalDocument"]["name"]);
    $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_name);
    $unique_name = time() . '_' . $safe_name;
    $target_path = $target_dir . $unique_name;

    if (move_uploaded_file($_FILES["additionalDocument"]["tmp_name"], $target_path)) {
        $additional_info_path = $target_path;
    } else {
        die("Additional info upload failed.");
    }
}

// === Insert into events ===
$club_id = $_POST['club'];
$ev_name = $_POST['eventName'];
$ev_nature = $_POST['eventNature'];
$ev_objectives = $_POST['eventObjectives'];
$ev_intro = $_POST['eventIntroduction'];
$ev_details = $_POST['eventPurpose'];
$ev_date = $_POST['eventDate'];
$ev_start_time = $_POST['startTime'];
$ev_end_time = $_POST['endTime'];
$ev_pax = $_POST['estimatedParticipants'];
$ev_venue_id = $_POST['venue'];
$ev_alt_venue_id = !empty($_POST['altVenue']) ? $_POST['altVenue'] : null;
$ev_alt_date = !empty($_POST['alternativeDate']) ? $_POST['alternativeDate'] : null;

$stmt = $conn->prepare("INSERT INTO events (
    Ev_ID, Stu_ID, Club_ID, Ev_Name, Ev_ProjectNature, Ev_Objectives, Ev_Intro, Ev_Details,
    Ev_Date, Ev_StartTime, Ev_EndTime, Ev_Pax,
    Ev_VenueID, Ev_AltVenueID, Ev_AlternativeDate, Ev_AdditionalInfo, Ev_Poster, Status_ID
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

$stmt->bind_param(
    "sssssssssssisisss",
    $event_id,
    $stu_id,
    $club_id,
    $ev_name,
    $ev_nature,
    $ev_objectives,
    $ev_intro,
    $ev_details,
    $ev_date,
    $ev_start_time,
    $ev_end_time,
    $ev_pax,
    $ev_venue_id,
    $ev_alt_venue_id,
    $ev_alt_date,
    $additional_info_path,
    $poster
);
if (!$stmt->execute()) {
    die("Error inserting event data: " . $stmt->error);
}
$stmt->close();

// === Insert into PersonInCharge ===
$pic_id = $_POST['picId'];
$pic_name = $_POST['picName'];
$pic_phone = $_POST['picPhone'];

$stmt = $conn->prepare("INSERT INTO PersonInCharge (PIC_ID, Ev_ID, PIC_Name, PIC_PhnNum) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $pic_id, $event_id, $pic_name, $pic_phone);
$stmt->execute();
$stmt->close();

// === Insert Committee Members ===
$committee_stmt = $conn->prepare("INSERT INTO Committee 
    (Com_ID, Ev_ID, Com_Position, Com_Name, Com_Department, Com_PhnNum, Com_JobScope, Com_COCUClaimers, Student_statement)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$cocu_dir = "../../uploads/cocustatement/";
if (!is_dir($cocu_dir))
    mkdir($cocu_dir, 0777, true);
$fileIndex = 0;

foreach ($_POST['committeeName'] as $index => $name) {
    $id = $_POST['committeeId'][$index];
    $position = $_POST['committeePosition'][$index];
    $department = $_POST['committeeDepartment'][$index];
    $phone = $_POST['committeePhone'][$index];
    $job = $_POST['committeeJobScope'][$index];
    $cocu_status = $_POST['cocuClaimer'][$index];
    $cocu_statement_path = null;

    if ($cocu_status === "yes") {
        $filename = $_FILES['cocuStatement']['name'][$fileIndex];
        $tmp = $_FILES['cocuStatement']['tmp_name'][$fileIndex];

        if (!empty($filename) && !empty($tmp)) {
            $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $filename);
            $ext = pathinfo($safe_filename, PATHINFO_EXTENSION);
            $unique = $id . '_cocu_' . time() . '.' . $ext;
            $dest = $cocu_dir . $unique;

            if (move_uploaded_file($tmp, $dest)) {
                $cocu_statement_path = $dest;
            } else {
                die("COCU Statement upload failed.");
            }
        }
        $fileIndex++;
    }

    $committee_stmt->bind_param("sssssssss", $id, $event_id, $position, $name, $department, $phone, $job, $cocu_status, $cocu_statement_path);
    $committee_stmt->execute();
}
$committee_stmt->close();

// === Insert Event Flow (Minutes) ===
foreach ($_POST['eventFlowDate'] as $index => $date) {
    $start = $_POST['eventFlowStart'][$index];
    $end = $_POST['eventFlowEnd'][$index];
    $hours = $_POST['eventFlowHours'][$index];
    $activity = $_POST['eventFlowActivity'][$index];
    $remarks = $_POST['eventFlowRemarks'][$index];

    $stmt = $conn->prepare("INSERT INTO eventminutes (Ev_ID, Date, Start_Time, End_Time, Hours, Activity, Remarks) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $event_id, $date, $start, $end, $hours, $activity, $remarks);
    $stmt->execute();
}
$stmt->close();

// === Insert Budget ===
$total_income = 0;
$total_expense = 0;

$stmt = $conn->prepare("INSERT INTO Budget (Ev_ID, Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks) VALUES (?, ?, ?, ?, ?)");

foreach ($_POST['budgetDescription'] as $index => $desc) {
    $amount = (float) $_POST['budgetAmount'][$index];
    $type = ucfirst(strtolower($_POST['budgetType'][$index])); // Normalize value
    $remarks = $_POST['budgetRemarks'][$index];

    if ($type == "Income")
        $total_income += $amount;
    else if ($type == "Expense")
        $total_expense += $amount;

    $stmt->bind_param("ssdss", $event_id, $desc, $amount, $type, $remarks);
    $stmt->execute();
}
$stmt->close();

$surplus = $total_income - $total_expense;
$prepared_by = $_POST['preparedBy'];

$stmt = $conn->prepare("INSERT INTO BudgetSummary (Ev_ID, Total_Income, Total_Expense, Surplus_Deficit, Prepared_By)
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sddds", $event_id, $total_income, $total_expense, $surplus, $prepared_by);
$stmt->execute();
$stmt->close();

//Email Notifcation for the Advisor 
$advisorQuery = $conn->prepare("SELECT Adv_ID, Adv_Name, Adv_Email 
                                FROM advisor 
                                WHERE Club_ID = ?");

$advisorQuery->bind_param("s", $club_id);
$advisorQuery->execute();
$advisorResult = $advisorQuery->get_result();
$advisorData = $advisorResult->fetch_assoc();
$advisorQuery->close();

$advisorName = $advisorData['Adv_Name'];
$advisorEmail = $advisorData['Adv_Email'];

// === Get Student Name ===
$studentQuery = $conn->prepare("SELECT Stu_Name FROM student WHERE Stu_ID = ?");
$studentQuery->bind_param("s", $stu_id);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$studentData = $studentResult->fetch_assoc();
$studentQuery->close();

$studentName = $studentData['Stu_Name'];

// ✅ Send Email Notification to Advisor
newProposalToAdvisor($studentName, $ev_name, $advisorName, $advisorEmail);

// Store event details for PDF generation
$_SESSION['last_event'] = [
    'event_id' => $event_id,
    'event_name' => $ev_name,
    'event_date' => $ev_date,
    'student_name' => $studentName,
    'submission_date' => date('Y-m-d H:i:s')
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Submitted Successfully</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }

        .shape-2 {
            top: 20%;
            right: 10%;
            animation-delay: 2s;
        }

        .shape-3 {
            bottom: 20%;
            left: 15%;
            animation-delay: 4s;
        }

        .shape-4 {
            bottom: 10%;
            right: 20%;
            animation-delay: 1s;
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
            max-width: 500px;
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s infinite;
            box-shadow: 0 15px 35px rgba(79, 172, 254, 0.3);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .event-details {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4f8 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid #667eea;
        }

        .event-id {
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
            color: #74b9ff;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(253, 121, 168, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(253, 121, 168, 0.4);
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
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
            border-top: 4px solid #667eea;
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
            <i class="fas fa-check"></i>
        </div>

        <div class="status-badge">
            <i class="fas fa-paper-plane"></i>
            Submitted Successfully
        </div>

        <h1 class="success-title">Proposal Submitted!</h1>

        <p class="success-message">
            Your event proposal has been successfully submitted and is now pending advisor approval.
        </p>

        <div class="event-details">
            <div class="event-id">
                <i class="fas fa-hashtag me-2"></i>
                Event ID: <?= htmlspecialchars($event_id) ?>
            </div>
            <div class="event-name">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= htmlspecialchars($ev_name) ?>
            </div>

        </div>

        <div class="action-buttons">
            <a href="../StudentDashboard.php" class="btn-custom btn-primary">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
            <button onclick="exportToPDF()" class="btn-custom btn-secondary">
                <i class="fas fa-file-pdf"></i>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Generating PDF...</p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function exportToPDF() {
            const id = encodeURIComponent("<?= $event_id ?>"); // handle the slash in Ev_ID like "01/25"
            window.open("../../components/pdf/generate_pdf.php?id=" + id, "_blank");
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
                    border-left: 4px solid #00b894;
                    color: #00b894;
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

        // Add some interactive effects
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