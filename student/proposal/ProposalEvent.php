<?php
session_start();
include('../../db/dbconfig.php');

// Guard session
if (empty($_SESSION['Stu_ID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$stu_id = $_SESSION['Stu_ID'];

// Get student name (prepared)
$student_stmt = $conn->prepare("SELECT Stu_Name FROM student WHERE Stu_ID = ?");
$student_stmt->bind_param("s", $stu_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
if (!$student) {
    die("Student not found.");
}

// Get clubs (ordered) & venues (ordered)
$club_result = $conn->query("SELECT Club_ID, Club_Name FROM club ORDER BY Club_Name");
$venue_result = $conn->query("SELECT Venue_ID, Venue_Name FROM venue ORDER BY Venue_Name");
$venues = [];
while ($v = $venue_result->fetch_assoc()) {
    $venues[] = $v;
}

// CREATE mode only
$action = 'ProposalHandler.php?mode=create';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Proposal Form </title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #aca8ff 0%, #e8e6ff 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #ac73ff, #8b5cf6);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9ff;
            border-radius: 10px;
            border-left: 5px solid #ac73ff;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }




        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-col {
            flex: 1;
            min-width: 250px;
        }

        .view-modal .modal-content {
            max-width: 700px;
            width: 90%;
        }

        .view-modal textarea {
            width: 100%;
            height: 200px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            line-height: 1.5;
            background-color: #f8f9fa;
            resize: none;
        }

        .view-modal .modal-header {
            padding: 20px 20px 10px 20px;
            border-bottom: 2px solid #ac73ff;
            margin-bottom: 15px;
        }

        .view-modal .modal-header h3 {
            color: #ac73ff;
            margin: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        /* Ensure all section titles have the line */
        .section-title {
            color: #ac73ff;
            font-size: 1.8em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ac73ff !important;
            /* Added !important to ensure it shows */
            display: flex;
            align-items: center;
            width: 100%;
            /* Ensure full width */
        }

        .section-title::before {
            content: "";
            width: 20px;
            height: 20px;
            background: #ac73ff;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
            /* Prevent shrinking */
        }

        /* Make sure the container doesn't interfere with the line */
        .section-title-container {
            width: 100%;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #ac73ff;
            box-shadow: 0 0 0 3px rgba(172, 168, 255, 0.1);
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Sample button styling */
        .sample-download-container {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .sample-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .sample-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a67d8 0%, #6b5b95 100%);
            color: white;
            text-decoration: none;
        }

        .sample-button:active {
            transform: translateY(0px);
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
        }

        .sample-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            color: inherit;
        }

        .sample-text {
            font-weight: 500;
            color: inherit;
        }

        /* Tooltip styling improvements */
        .sample-tooltip {
            position: relative;
            display: inline-block;
        }

        .sample-tooltip .tooltiptext {
            visibility: hidden;
            width: 180px;
            background-color: #2d3748;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px 12px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -90px;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sample-tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #2d3748 transparent transparent transparent;
        }

        .sample-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sample-button {
                padding: 8px 12px;
                font-size: 12px;
            }

            .sample-icon {
                width: 16px;
                height: 16px;
            }

            .sample-tooltip .tooltiptext {
                width: 140px;
                margin-left: -70px;
                font-size: 11px;
            }
        }

        .upload-area {
            border: 2px dashed #ac73ff;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .upload-area:hover {
            background: #aca8ff;
            color: white;
            transform: translateY(-2px);
        }

        .upload-area.dragover {
            background: #ac73ff;
            color: white;
            transform: scale(1.02);
        }

        .file-info {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #ac73ff;
            color: white;
            font-weight: 600;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            margin: 5px;
        }

        .btn-primary {
            background: #ac73ff;
            color: white;
        }

        .btn-primary:hover {
            background: #8b5cf6;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        /* Consistent button styling for Add/View buttons */
        .btn-sm {
            padding: 4px 8px !important;
            font-size: 11px !important;
            font-weight: 500;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 40px;
            height: 26px;
        }



        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .btn-secondary.btn-sm {
            background: #6c757d;
            color: white;
        }

        .btn-secondary.btn-sm:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .btn-primary.btn-sm {
            background: #ac73ff;
            color: white;
        }

        .btn-primary.btn-sm:hover {
            background: #8b5cf6;
            transform: translateY(-1px);
        }

        /* Button container styling */
        .button-container {
            display: flex;
            gap: 3px;
            align-items: center;
            justify-content: center;
        }

        /* Committee table specific styles */
        #committeeTable {
            min-width: 1200px;
            /* Ensure minimum width */
        }

        #committeeTable th:nth-child(1),
        /* Student ID */
        #committeeTable td:nth-child(1) {
            min-width: 120px;
            width: 120px;
        }

        #committeeTable th:nth-child(2),
        /* Name */
        #committeeTable td:nth-child(2) {
            min-width: 150px;
            width: 150px;
        }

        #committeeTable th:nth-child(3),
        /* Position */
        #committeeTable td:nth-child(3) {
            min-width: 130px;
            width: 130px;
        }

        #committeeTable th:nth-child(4),
        /* Department */
        #committeeTable td:nth-child(4) {
            min-width: 250px;
            /* Increased significantly */
            width: 250px;
        }

        #committeeTable th:nth-child(5),
        /* Phone */
        #committeeTable td:nth-child(5) {
            min-width: 140px;
            /* Increased */
            width: 140px;
        }

        #committeeTable th:nth-child(6),
        /* Job Scope */
        #committeeTable td:nth-child(6) {
            min-width: 120px;
            width: 120px;
        }

        #committeeTable th:nth-child(7),
        /* COCU Claimer */
        #committeeTable td:nth-child(7) {
            min-width: 80px;
            width: 80px;
        }

        #committeeTable th:nth-child(8),
        /* Upload COCU */
        #committeeTable td:nth-child(8) {
            min-width: 100px;
            /* Reduced */
            width: 100px;
        }

        #committeeTable th:nth-child(9),
        /* Actions */
        #committeeTable td:nth-child(9) {
            min-width: 60px;
            width: 60px;
        }

        /* Delete icon button */
        .btn-delete-icon {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-delete-icon:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }

        /* Sample format tooltip */
        .sample-tooltip {
            position: relative;
            display: inline-block;
        }

        .sample-tooltip .tooltiptext {
            visibility: hidden;
            width: 140px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -70px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }

        .sample-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-add {
            background: #27ae60;
            color: white;
            margin-bottom: 20px;
        }

        .btn-add:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 10px;
        }

        .form-actions .btn {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .budget-summary {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .budget-summary h4 {
            color: #27ae60;
            margin-bottom: 10px;
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .form-col {
                min-width: 100%;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .table-container {
                font-size: 14px;
            }
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: #e74c3c;
        }

        .form-group.error .error-message {
            display: block;
        }

        /* add once */
        .error-field {
            border-color: #e74c3c !important;
        }

        /* Scroll buttons */
        .scroll-buttons {
            position: fixed;
            right: 20px;
            bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 999;
        }

        .scroll-btn {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ac73ff, #8b5cf6);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(172, 115, 255, 0.3);
            transition: all 0.3s ease;
        }

        .scroll-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(172, 115, 255, 0.4);
        }

        .scroll-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .scroll-buttons {
                right: 15px;
                bottom: 15px;
            }

            .scroll-btn {
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body>


    <div class="container">
        <div class="header">
            <h1>Proposal Form</h1>
            <p>Nilai University Content Management System</p>
        </div>

        <div class="form-container">
            <form id="proposalForm" method="POST" enctype="multipart/form-data" action="<?= $action ?>">

                <!-- Section 1: Student Information -->
                <div class="section">
                    <h2 class="section-title">Student Information</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="studentName" class="required">Student Name</label>
                                <input id="studentName" type="text" name="student_name"
                                    value="<?= htmlspecialchars($student['Stu_Name'], ENT_QUOTES, 'UTF-8') ?>"
                                    readonly />
                                <div class="error-message">Please enter student name</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="club" class="required">Club</label>
                                <select id="club" name="club" class="form-select" required>
                                    <option value="">-- Select Club --</option>
                                    <?php while ($club = $club_result->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($club['Club_ID'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($club['Club_Name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="error-message">Please select a club</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="studentId" class="required">Student ID</label>
                                <input type="text" name="student_id" value="<?= $stu_id ?>" readonly />
                                <div class="error-message">Please enter student ID</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Event Details -->
                <div class="section">
                    <h2 class="section-title">Event Details</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventName" class="required">Event Name</label>
                                <input type="text" id="eventName" name="eventName" placeholder="Enter event name"
                                    required />
                                <div class="error-message">Please enter event name</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="eventNature" class="required">Event Nature</label>
                                <select id="eventNature" name="eventNature" required>
                                    <option value="">Select Category</option>
                                    <option value="Category A: Games/Sports & Martial Arts">
                                        Category A: Games/Sports & Martial Arts
                                    </option>
                                    <option value="Category B: Club/Societies/Uniformed Units">
                                        Category B: Club/Societies/Uniformed Units
                                    </option>
                                    <option value="Category C: Community Service">
                                        Category C: Community Service
                                    </option>
                                </select>
                                <div class="error-message">Please select event nature</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventObjectives" class="required">Event Objectives</label>
                        <textarea id="eventObjectives" name="eventObjectives" required
                            placeholder="• Enter first objective&#10;• Enter second objective&#10;• Enter third objective"></textarea>
                        <div class="error-message">Please enter event objectives</div>
                        <div class="file-info" style="color: #666; font-size: 12px; margin-top: 5px;">
                            💡 Tip: Start each objective with a bullet point (-)
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventIntroduction" class="required">Introduction Event</label>
                        <textarea id="eventIntroduction" name="eventIntroduction" style="min-height: 200px"
                            placeholder="Enter event introduction" required></textarea>
                        <div class="error-message">Please enter event introduction</div>
                    </div>

                    <div class="form-group">
                        <label for="eventPurpose" class="required">Purpose of Event</label>
                        <textarea id="eventPurpose" name="eventPurpose" placeholder="Enter event purpose"
                            required></textarea>
                        <div class="error-message">Please enter event purpose</div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="estimatedParticipants" class="required">Estimated Participants</label>
                                <input type="number" id="estimatedParticipants" name="estimatedParticipants" min="1"
                                    placeholder="Example: 100" required />
                                <div class="error-message">
                                    Please enter estimated participants
                                </div>
                            </div>

                        </div>
                        <div class="form-group">
                            <label for="eventDate" class="required">Event Date</label>
                            <input type="date" id="eventDate" name="eventDate" required />
                            <div class="file-info" style="color: #666; font-size: 12px; margin-top: 5px;">
                                📅 The date can be chosen after 14 days from today
                            </div>
                            <div class="error-message">
                                Please select event date (minimum 14 days from today)
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="startTime" class="required">Start Time</label>
                                <select id="startTime" name="startTime" class="form-select" required>
                                    <option value="">-- Select Start Time --</option>
                                    <?php
                                    for ($h = 8; $h <= 22; $h++) {
                                        $timeValue = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00";
                                        $labelHour = ($h == 12) ? 12 : ($h % 12);
                                        $ampm = ($h < 12) ? "AM" : "PM";
                                        echo "<option value='$timeValue'>$labelHour:00 $ampm</option>";
                                    }
                                    ?>
                                </select>
                                <div class="error-message">Please select start time</div>
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <label for="endTime" class="required">End Time</label>
                                <select id="endTime" name="endTime" class="form-select" required>
                                    <option value="">-- Select End Time --</option>
                                    <?php
                                    for ($h = 8; $h <= 22; $h++) {
                                        $timeValue = str_pad($h, 2, '0', STR_PAD_LEFT) . ":00";
                                        $labelHour = ($h == 12) ? 12 : ($h % 12);
                                        $ampm = ($h < 12) ? "AM" : "PM";
                                        echo "<option value='$timeValue'>$labelHour:00 $ampm</option>";
                                    }
                                    ?>
                                </select>
                                <div class="error-message">Please select end time</div>
                            </div>
                        </div>

                        <div class="form-col">
                            <div class="form-group">
                                <label for="venue" class="required">Venue</label>
                                <select id="venue" name="venue" class="form-select" required>
                                    <option value="">-- Select Main Venue --</option>
                                    <?php foreach ($venues as $v): ?>
                                        <option value="<?= htmlspecialchars($v['Venue_ID'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($v['Venue_Name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">Please select venue</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="eventPoster" class="required">Event Poster</label>
                        <div class="upload-area" id="posterUpload">
                            <div>
                                <p>📁 Drag and drop your poster here or click to browse</p>
                                <input type="file" id="eventPoster" name="eventPoster" accept=".jpg,.jpeg,.png"
                                    style="display: none" required />
                            </div>
                        </div>
                        <div class="file-info">
                            Maximum file size: 20MB | Accepted formats: JPEG, PNG
                        </div>
                        <div class="preview-container" id="posterPreview"></div>
                        <div class="error-message">Please upload event poster</div>
                    </div>
                </div>

                <!-- Section 3: Person in Charge -->
                <div class="section">
                    <h2 class="section-title">Person in Charge</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picName" class="required">Name</label>
                                <input type="text" id="picName" name="picName" placeholder="Enter person in charge name"
                                    required />
                                <div class="error-message">
                                    Please enter person in charge name
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picId" class="required">ID</label>
                                <input type="text" id="picId" name="picId" placeholder="00020547" required />
                                <div class="error-message">
                                    Please enter person in charge ID
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="picPhone" class="required">Phone Number</label>
                                <input type="tel" id="picPhone" name="picPhone" placeholder="0123456789" required />
                                <div class="error-message">Please enter phone number</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Event Flow -->
                <div class="section">
                    <h2 class="section-title">Event Flow (Minutes of Meeting)</h2>
                    <div class="file-info"
                        style="margin-bottom: 15px; background: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                        📝 <strong>Instructions:</strong>
                        <ul style="margin: 5px 0 0 20px; color: #666;">
                            <li>Click "Add" button in Activity Description column to enter detailed activity description
                            </li>
                        </ul>
                    </div>
                    <div class="table-container">
                        <table id="eventFlowTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours</th>
                                    <th>Activity Description</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="eventFlowBody">
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>

                        <button type="button" class="btn btn-add" id="addEventFlowBtn">
                            + Add New Row
                        </button>
                    </div>
                    <div id="hoursStatus" style="margin-top: 10px; font-weight: bold; color: #2d4f2b">
                        🕒 40 hours remaining to reach minimum requirement
                    </div>
                </div>

                <!-- Section 5: Committee Members -->
                <div class="section">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <h2 class="section-title">Committee Members</h2>
                        </div>
                        <div class="sample-download-container">
                            <div class="sample-tooltip">
                                <a href="../../assets/file/sampleCocuStatement.pdf" download class="sample-button">
                                    <svg class="sample-icon" viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M3 7V17C3 18.1046 3.89543 19 5 19H19C20.1046 19 21 18.1046 21 17V9C21 7.89543 20.1046 7 19 7H13L11 5H5C3.89543 5 3 5.89543 3 7Z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M12 15L12 11M12 11L10 13M12 11L14 13" stroke="currentColor"
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="sample-text">Sample</span>
                                </a>
                                <span class="tooltiptext">Download Sample COCU Statement</span>
                            </div>
                        </div>
                    </div>

                    <div class="file-info"
                        style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
                        📋 <strong>Instructions and Notes:</strong>
                        <ul style="margin: 8px 0 0 20px; color: #666; line-height: 1.5;">
                            <li>Upload PDF files only, maximum file size: 2MB</li>
                            <li>Click "Add" button in Job Scope column to enter detailed job description</li>
                            <li>For COCU claimers, upload the COCU statement document</li>
                        </ul>
                    </div>

                    <div class="table-container">
                        <table id="committeeTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Phone</th>
                                    <th>Job Scope</th>
                                    <th>COCU Claimer</th>
                                    <th>Upload COCU</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="committeeBody">
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>

                        <button type="button" class="btn btn-add" id="addCommitteeBtn">
                            + Add New Row
                        </button>
                    </div>
                </div>
                <!-- Section 6: Budget -->
                <div class="section">
                    <h2 class="section-title">Budget</h2>

                    <div class="table-container">
                        <table id="budgetTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount (RM)</th>
                                    <th>Type</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="budgetBody">
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-add" id="addBudgetBtn">
                            + Add New Row
                        </button>
                    </div>
                    <div class="budget-summary">
                        <h4>Budget Summary</h4>
                        <div class="budget-row">
                            <span>Total Income:</span>
                            <span id="totalIncome">RM 0.00</span>
                        </div>
                        <div class="budget-row">
                            <span>Total Expense:</span>
                            <span id="totalExpense">RM 0.00</span>
                        </div>
                        <div class="budget-row">
                            <strong>
                                <span id="surplusDeficitLabel">Surplus/Deficit:</span>
                                <span id="surplusDeficit">RM 0.00</span>
                            </strong>
                        </div>
                        <div class="form-group" style="margin-top: 15px">
                            <label for="preparedBy" class="required">Prepared By:</label>
                            <input type="text" id="preparedBy" name="preparedBy" placeholder="Secretary/Treasure"
                                required />
                            <div class="error-message">Please enter prepared by</div>
                        </div>
                    </div>
                </div>

                <!-- Section 7: Additional Information -->
                <div class="section">
                    <h2 class="section-title">Additional Information</h2>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="alternativeDate" class="required">Alternative Date</label>
                                <input type="date" id="alternativeDate" name="alternativeDate" required />
                                <div class="file-info" style="color:#666;font-size:12px;margin-top:5px;">
                                    📅 Must be at least 14 days from today
                                </div>
                                <div class="error-message">Please select alternative date (min 14 days)</div>
                            </div>
                        </div>


                        <div class="form-col">
                            <div class="form-group">
                                <label for="alternativeVenue" class="required">Alternative Venue</label>
                                <select id="alternativeVenue" name="altVenue" class="form-select" required>
                                    <option value="">-- Select Alternative Venue --</option>
                                    <?php foreach ($venues as $v): ?>
                                        <option value="<?= htmlspecialchars($v['Venue_ID'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($v['Venue_Name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">
                                    Please select alternative venue
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="additionalDocument">Additional Document (Optional)</label>
                            <div class="upload-area" id="additionalDocUpload">
                                <div>
                                    <p>📁 Drag and drop additional document here or click to browse</p>
                                    <input type="file" id="additionalDocument" name="additionalDocument" accept=".pdf"
                                        style="display: none" />
                                </div>
                            </div>
                            <div class="file-info">Only upload PDF file</div>
                            <div class="preview-container" id="additionalDocPreview"></div>
                        </div>
                    </div>

                </div>
                <!-- View Modal -->
                <div id="viewModal" class="modal view-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="viewModalTitle">View Details</h3>
                            <span class="close">&times;</span>
                        </div>
                        <textarea id="viewModalContent" readonly></textarea>
                    </div>
                </div>
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="backBtn">
                        ← Back
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" id="previewBtn">
                            👁 Preview
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            📝 Submit Proposal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Description Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Activity Description</h3>
            <textarea id="activityDescription" placeholder="Enter activity description..."
                style="width: 100%; height: 150px; margin: 15px 0"></textarea>
            <button type="button" class="btn btn-primary" id="saveActivityBtn">
                Save
            </button>
        </div>
    </div>

    <!-- Job Scope Modal -->
    <div id="jobScopeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Job Scope</h3>
            <textarea id="jobScopeDescription" placeholder="Enter job scope..."
                style="width: 100%; height: 150px; margin: 15px 0"></textarea>
            <button type="button" class="btn btn-primary" id="saveJobScopeBtn">
                Save
            </button>
        </div>
    </div>

    <!-- Scroll Buttons -->
    <div class="scroll-buttons">
        <button class="scroll-btn" id="scrollTopBtn" onclick="scrollToTop()" title="Scroll to Top">
            ↑
        </button>
        <button class="scroll-btn" id="scrollBottomBtn" onclick="scrollToBottom()" title="Scroll to Bottom">
            ↓
        </button>
    </div>

    <script>
        // Initialize form
        document.addEventListener("DOMContentLoaded", function () {
            initializeForm();
            setupEventListeners();
            setMinimumDates();
        });

        function initializeForm() {
            // Add initial rows
            addEventFlowRow();
            addCommitteeRow();
            addBudgetRow();
            updateRemainingHours(); // 👉 Add this to calculate hours immediately
        }

        // Scroll functions
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

        // Show/hide scroll buttons based on scroll position
        window.addEventListener('scroll', function () {
            const scrollTopBtn = document.getElementById('scrollTopBtn');
            const scrollBottomBtn = document.getElementById('scrollBottomBtn');

            if (window.pageYOffset > 300) {
                scrollTopBtn.style.opacity = '1';
                scrollTopBtn.style.pointerEvents = 'auto';
            } else {
                scrollTopBtn.style.opacity = '0.5';
                scrollTopBtn.style.pointerEvents = 'none';
            }

            if ((window.innerHeight + window.pageYOffset) >= document.body.offsetHeight - 100) {
                scrollBottomBtn.style.opacity = '0.5';
                scrollBottomBtn.style.pointerEvents = 'none';
            } else {
                scrollBottomBtn.style.opacity = '1';
                scrollBottomBtn.style.pointerEvents = 'auto';
            }
        });


        function setupEventListeners() {
            // File upload handlers
            setupFileUpload("posterUpload", "eventPoster", handlePosterUpload);
            setupFileUpload("additionalDocUpload", "additionalDocument", handleAdditionalDocUpload);
            // Button handlers
            document
                .getElementById("addEventFlowBtn")
                .addEventListener("click", addEventFlowRow);
            document
                .getElementById("addCommitteeBtn")
                .addEventListener("click", addCommitteeRow);
            document
                .getElementById("addBudgetBtn")
                .addEventListener("click", addBudgetRow);

            // Modal handlers
            setupModalHandlers();

            // Form submission
            document
                .getElementById("previewBtn")
                .addEventListener("click", showPreviewMessage);
            document
                .getElementById("backBtn")
                .addEventListener("click", handleBack);

            // Budget calculation
            document
                .getElementById("budgetBody")
                .addEventListener("input", calculateBudget);
        }

        function setMinimumDates() {
            const minDate = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000)
                .toISOString().split("T")[0];
            const eventDate = document.getElementById("eventDate");
            const altDate = document.getElementById("alternativeDate");
            if (eventDate) eventDate.min = minDate;
            if (altDate) altDate.min = minDate;
        }


        function setupFileUpload(uploadAreaId, inputId, callback) {
            const uploadArea = document.getElementById(uploadAreaId);
            const fileInput = document.getElementById(inputId);

            uploadArea.addEventListener("click", () => fileInput.click());
            uploadArea.addEventListener("dragover", handleDragOver);
            uploadArea.addEventListener("dragleave", handleDragLeave);
            uploadArea.addEventListener("drop", (e) =>
                handleDrop(e, fileInput, callback)
            );

            fileInput.addEventListener("change", (e) => {
                if (callback) callback(e.target.files[0]);
            });
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add("dragover");
        }

        function handleDragLeave(e) {
            e.currentTarget.classList.remove("dragover");
        }

        function handleDrop(e, fileInput, callback) {
            e.preventDefault();
            e.currentTarget.classList.remove("dragover");

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                if (callback) callback(files[0]);
            }
        }

        function handlePosterUpload(file) {
            if (file) {
                const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
                const allowedExtensions = [".jpg", ".jpeg", ".png"];
                const extension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();

                if (!allowedTypes.includes(file.type) || !allowedExtensions.includes(extension)) {
                    Swal.fire("Invalid File", "Only JPG and PNG formats are allowed.", "error");
                    return;
                }

                if (file.size > 20 * 1024 * 1024) {
                    Swal.fire("File Too Large", "Max size is 20MB.", "error");
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = document.getElementById("posterPreview");
                    preview.innerHTML = `
                <img src="${e.target.result}" alt="Poster Preview" class="preview-image">
                <p>✅ Poster uploaded successfully</p>
            `;
                };
                reader.readAsDataURL(file);
            }
        }


        function addEventFlowRow() {
            const tbody = document.getElementById("eventFlowBody");
            const row = document.createElement("tr");
            const rowId = "eventFlow_" + Date.now();

            row.innerHTML = `
                <td><input type="date" name="eventFlowDate[]" required></td>
                <td><input type="time" name="eventFlowStart[]" required></td>
                <td><input type="time" name="eventFlowEnd[]" required></td>
                <td><input type="number" name="eventFlowHours[]" class="hours-input" step="0.5" min="0" readonly></td>
                <td>
                    <div class="button-container">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addActivityDescription('${rowId}')" title="Click to add activity description">Add</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="viewActivityDescription('${rowId}')" disabled>View</button>
                    </div>
                    <input type="hidden" name="eventFlowActivity[]" id="activity_${rowId}">
                </td>
                <td><input type="text" name="eventFlowRemarks[]" placeholder="Meeting/Eventflow" style="background-color: #f8f9fa;" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button></td>
            `;

            updateRemainingHours();
            tbody.appendChild(row);

            // Calculate hours automatically
            const startInput = row.querySelector('input[name="eventFlowStart[]"]');
            const endInput = row.querySelector('input[name="eventFlowEnd[]"]');
            const hoursInput = row.querySelector('input[name="eventFlowHours[]"]');

            function calculateHours() {
                if (startInput.value && endInput.value) {
                    const start = new Date(`2000-01-01T${startInput.value}`);
                    const end = new Date(`2000-01-01T${endInput.value}`);
                    const diff = (end - start) / (1000 * 60 * 60);
                    hoursInput.value = diff > 0 ? diff.toFixed(1) : 0;
                } else {
                    hoursInput.value = 0;
                }
                updateRemainingHours();
            }

            startInput.addEventListener("change", calculateHours);
            endInput.addEventListener("change", calculateHours);
        }

        function updateRemainingHours() {
            const totalHours = getTotalEventFlowHours();
            const hoursStatus = document.getElementById("hoursStatus");
            const remaining = 40 - totalHours;

            if (totalHours >= 40) {
                hoursStatus.textContent = `✅ Minimum requirement met: ${totalHours.toFixed(
                    1
                )} hours`;
                hoursStatus.style.color = "#27ae60"; // green
            } else {
                hoursStatus.textContent = `🕒 ${remaining.toFixed(
                    1
                )} hours remaining to reach minimum requirement`;
                hoursStatus.style.color = "#e67e22"; // orange
            }
        }

        function addCommitteeRow() {
            const tbody = document.getElementById("committeeBody");
            const row = document.createElement("tr");
            const rowId = "committee_" + Date.now();

            row.innerHTML = `
                <td><input type="text" style="width: 100%;" name="committeeId[]" placeholder="00020547" required></td>
                <td><input type="text" style="width: 100%;" name="committeeName[]" placeholder="Sharwin" required></td>
                <td><input type="text" style="width: 100%;" name="committeePosition[]" placeholder="Publicity" required></td>
                <td>
                    <select style="width: 100%;" name="committeeDepartment[]" required>
                        <option value="">Select Department</option>
                        <option value="Foundation in Business">Foundation in Business</option>
                        <option value="Foundation in Science">Foundation in Science</option>
                        <option value="DCS">Diploma in Computer Science</option>
                        <option value="DIA">Diploma in Accounting</option>
                        <option value="DAME">Diploma in Aircraft Maintenance Engineering</option>
                        <option value="DIT">Diploma in Information Technology</option>
                        <option value="DHM">Diploma in Hotel Management</option>
                        <option value="DCA">Diploma in Culinary Arts</option>
                        <option value="DBA">Diploma in Business Administration</option>
                        <option value="DIN">Diploma in Nursing</option>
                        <option value="BOF">Bachelor of Finance</option>
                        <option value="BAAF">Bachelor of Arts in Accounting & Finance</option>
                        <option value="BBAF">Bachelor of Business Administration in Finance</option>
                        <option value="BSB">Bachelor of Science Biotechnology</option>
                        <option value="BCSAI">Bachelor of Computer Science Artificial Intelligence</option>
                        <option value="BITC">Bachelor of Information Technology Cybersecurity</option>
                        <option value="BSE">Bachelor of Software Engineering</option>
                        <option value="BCSDS">Bachelor of Computer Science Data Science</option>
                        <option value="BIT">Bachelor of Information Technology</option>
                        <option value="BITIECC">Bachelor of Information Technology Internet Engineering and Cloud Computing</option>
                        <option value="BEM">Bachelor of Event Management</option>
                        <option value="BHMBM">Bachelor of Hospitality Management with Business Management</option>
                        <option value="BBAGL">Bachelor of Business Administration in Global Logistic</option>
                        <option value="BBADM">Bachelor of Business Administration in Digital Marketing</option>
                        <option value="BBAM">Bachelor of Business Administration in Marketing</option>
                        <option value="BBAMT">Bachelor of Business Administration in Management</option>
                        <option value="BBAIB">Bachelor of Business Administration in International Business</option>
                        <option value="BBAHRM">Bachelor of Business Administration in Human Resource Management</option>
                        <option value="BBA">Bachelor of Business Administration</option>
                        <option value="BSN">Bachelor of Science in Nursing</option>
                    </select>
                </td>
                <td><input type="tel" style="width: 100%;" name="committeePhone[]" placeholder="0123456789" required></td>
                <td>
                    <div class="button-container">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addJobScope('${rowId}')">Add</button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="viewJobScope('${rowId}')" disabled>View</button>
                    </div>
                    <input type="hidden" name="committeeJobScope[]" id="jobScope_${rowId}">
                </td>
                <td>
                    <select name="cocuClaimer[]" style="width: 100%;" onchange="toggleCocuUpload(this, '${rowId}')" required>
                        <option value="no" selected>No</option>
                        <option value="yes">Yes</option>
                    </select>
                </td>
                <td>
                    <input type="file" name="cocuStatement[]" id="cocuFile_${rowId}" accept=".pdf" disabled style="font-size: 11px; width: 100%;">
                </td>
                <td>
                    <button type="button" class="btn-delete-icon" onclick="deleteRow(this)" title="Delete row">
                        🗑️
                    </button>
                </td>
            `;

            tbody.appendChild(row);
        }

        function addBudgetRow() {
            const tbody = document.getElementById("budgetBody");
            const row = document.createElement("tr");

            row.innerHTML = `
                <td><input type="text" name="budgetDescription[]" placeholder="Enter budget description" required></td>
                <td><input type="number" name="budgetAmount[]" step="0.01" min="0" placeholder="enter amount = 250" required></td>
                <td>
                    <select name="budgetType[]" required>
                        <option value="">Select</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </td>
                <td><input type="text" name="budgetRemarks[]" placeholder="Enter remarks (optional)"></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button></td>
            `;

            tbody.appendChild(row);
        }

        function deleteRow(button) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this row?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    button.closest("tr").remove();
                    calculateBudget();
                    updateRemainingHours();
                    Swal.fire(
                        'Deleted!',
                        'Row has been deleted.',
                        'success'
                    )
                }
            });
        }

        function setupModalHandlers() {
            // Activity Description Modal
            const activityModal = document.getElementById("activityModal");
            const jobScopeModal = document.getElementById("jobScopeModal");

            // Close modals
            document.querySelectorAll(".close").forEach((closeBtn) => {
                closeBtn.addEventListener("click", function () {
                    this.closest(".modal").style.display = "none";
                });
            });



            // Save activity description
            document
                .getElementById("saveActivityBtn")
                .addEventListener("click", function () {
                    const description = document.getElementById(
                        "activityDescription"
                    ).value;
                    if (description.trim()) {
                        const rowId = activityModal.getAttribute("data-row-id");
                        document.getElementById("activity_" + rowId).value = description;

                        // Enable view button
                        const viewBtn = document.querySelector(
                            `button[onclick="viewActivityDescription('${rowId}')"]`
                        );
                        viewBtn.disabled = false;

                        activityModal.style.display = "none";
                        document.getElementById("activityDescription").value = "";
                    }
                });

            // Save job scope
            document
                .getElementById("saveJobScopeBtn")
                .addEventListener("click", function () {
                    const description = document.getElementById(
                        "jobScopeDescription"
                    ).value;
                    if (description.trim()) {
                        const rowId = jobScopeModal.getAttribute("data-row-id");
                        document.getElementById("jobScope_" + rowId).value = description;

                        // Enable view button
                        const viewBtn = document.querySelector(
                            `button[onclick="viewJobScope('${rowId}')"]`
                        );
                        viewBtn.disabled = false;

                        jobScopeModal.style.display = "none";
                        document.getElementById("jobScopeDescription").value = "";
                    }
                });
        }

        function addActivityDescription(rowId) {
            const modal = document.getElementById("activityModal");
            modal.setAttribute("data-row-id", rowId);
            modal.style.display = "block";

            // Load existing description if any
            const existingDescription = document.getElementById(
                "activity_" + rowId
            ).value;
            document.getElementById("activityDescription").value =
                existingDescription;
        }

        function viewActivityDescription(rowId) {
            const description = document.getElementById("activity_" + rowId).value;
            showViewModal("Activity Description", description || "No description has been added for this activity yet.");
        }

        function addJobScope(rowId) {
            const modal = document.getElementById("jobScopeModal");
            modal.setAttribute("data-row-id", rowId);
            modal.style.display = "block";

            // Load existing job scope if any
            const existingJobScope = document.getElementById(
                "jobScope_" + rowId
            ).value;
            document.getElementById("jobScopeDescription").value = existingJobScope;
        }
        function showViewModal(title, content) {
            document.getElementById("viewModalTitle").textContent = title;
            document.getElementById("viewModalContent").value = content;
            document.getElementById("viewModal").style.display = "block";
        }


        function viewJobScope(rowId) {
            const jobScope = document.getElementById("jobScope_" + rowId).value;
            showViewModal("Job Scope", jobScope || "No job scope has been added for this activity yet.");
        }

        function toggleCocuUpload(select, rowId) {
            const fileInput = document.getElementById("cocuFile_" + rowId);
            fileInput.disabled = select.value !== "yes";
            if (select.value !== "yes") {
                fileInput.value = "";
            }
        }

        function calculateBudget() {
            const amounts = document.querySelectorAll(
                'input[name="budgetAmount[]"]'
            );
            const types = document.querySelectorAll('select[name="budgetType[]"]');

            let totalIncome = 0;
            let totalExpense = 0;

            amounts.forEach((amountInput, index) => {
                const amount = parseFloat(amountInput.value) || 0;
                const type = types[index].value;

                if (type === "income") {
                    totalIncome += amount;
                } else if (type === "expense") {
                    totalExpense += amount;
                }
            });

            const surplusDeficit = totalIncome - totalExpense;

            document.getElementById(
                "totalIncome"
            ).textContent = `RM ${totalIncome.toFixed(2)}`;
            document.getElementById(
                "totalExpense"
            ).textContent = `RM ${totalExpense.toFixed(2)}`;
            document.getElementById(
                "surplusDeficit"
            ).textContent = `RM ${surplusDeficit.toFixed(2)}`;

            // Change color based on surplus/deficit
            const surplusDeficitElement = document.getElementById("surplusDeficit");
            if (surplusDeficit > 0) {
                surplusDeficitElement.style.color = "#27ae60";
            } else if (surplusDeficit < 0) {
                surplusDeficitElement.style.color = "#e74c3c";
            } else {
                surplusDeficitElement.style.color = "#333";
            }
        }

        function validateForm() {
            const requiredFields = document.querySelectorAll(
                "input[required], select[required], textarea[required]"
            );
            let isValid = true;

            requiredFields.forEach((field) => {
                const formGroup = field.closest(".form-group");
                if (!field.value.trim()) {
                    formGroup.classList.add("error");
                    isValid = false;
                } else {
                    formGroup.classList.remove("error");
                }
            });

            // Custom validations

            // Event date validation (minimum 14 days from today)
            const eventDate = document.getElementById("eventDate");
            const today = new Date();
            const minDate = new Date(today.getTime() + 14 * 24 * 60 * 60 * 1000);

            if (eventDate.value && new Date(eventDate.value) < minDate) {
                eventDate.closest(".form-group").classList.add("error");
                isValid = false;
            }

            // Time validation (end time should be after start time)
            const startTime = document.getElementById("startTime");
            const endTime = document.getElementById("endTime");

            if (
                startTime.value &&
                endTime.value &&
                startTime.value >= endTime.value
            ) {
                endTime.closest(".form-group").classList.add("error");
                isValid = false;
            }

            // File validation
            const eventPoster = document.getElementById("eventPoster");
            if (!eventPoster.files.length) {
                eventPoster.closest(".form-group").classList.add("error");
                isValid = false;
            }

            // Table validations
            const eventFlowRows = document.querySelectorAll("#eventFlowBody tr");
            const committeeRows = document.querySelectorAll("#committeeBody tr");
            const budgetRows = document.querySelectorAll("#budgetBody tr");

            if (eventFlowRows.length === 0) {
                alert("Please add at least one event flow entry.");
                isValid = false;
            }

            if (committeeRows.length === 0) {
                alert("Please add at least one committee member.");
                isValid = false;
            }

            if (budgetRows.length === 0) {
                alert("Please add at least one budget entry.");
                isValid = false;
            }

            return isValid;
        }
        function markError(field, show) {
            const group = field.closest(".form-group");
            if (group) group.classList.toggle("error", !!show);
            else field.classList.toggle("error-field", !!show);
        }

        function handleAdditionalDocUpload(file) {
            if (file) {
                if (file.type !== "application/pdf") {
                    Swal.fire("Invalid File", "Only PDF files are allowed.", "error");
                    document.getElementById("additionalDocument").value = "";
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    Swal.fire("File Too Large", "Maximum file size is 10MB.", "error");
                    document.getElementById("additionalDocument").value = "";
                    return;
                }

                const preview = document.getElementById("additionalDocPreview");
                preview.innerHTML = `
            <div style="margin-top: 10px; padding: 10px; background: #e8f5e8; border-radius: 5px;">
                <span>✅ ${file.name}</span>
                <button type="button" class="btn btn-primary btn-sm" style="margin-left: 10px;" onclick="viewPDF('${file.name}')">View PDF</button>
            </div>
        `;
            }
        }

        function viewPDF(fileName) {
            const fileInput = document.getElementById("additionalDocument");
            if (fileInput.files[0]) {
                const fileURL = URL.createObjectURL(fileInput.files[0]);
                window.open(fileURL, '_blank');
            }
        }

        function showPreviewMessage() {
            Swal.fire({
                icon: 'info',
                title: 'Feature Coming Soon!',
                text: 'Currently this feature hasn\'t been completed yet, for future it will be. Thank you #sharwinsk',
                confirmButtonText: 'Understood',
                confirmButtonColor: '#ac73ff'
            });
        }
        function handleBack() {
            Swal.fire({
                title: 'Are you sure?',
                text: "Any unsaved changes will be lost!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#ac73ff',
                confirmButtonText: 'Yes, go back',
                cancelButtonText: 'Stay here'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.history.back();
                }
            });
        }

        function getTotalEventFlowHours() {
            const hourInputs = document.querySelectorAll(
                'input[name="eventFlowHours[]"]'
            );
            let total = 0;
            hourInputs.forEach((input) => {
                total += parseFloat(input.value) || 0;
            });
            return total;
        }

        document
            .getElementById("proposalForm")
            .addEventListener("submit", function (e) {
                e.preventDefault(); // Prevent auto-submit

                const totalHours = calculateTotalHours(); // Use our own function
                if (totalHours < 40) {
                    Swal.fire({
                        icon: "error",
                        title: "Minimum 40 Hours Required",
                        text: `You only have ${totalHours} hours. Minimum 40 hours needed.`,
                    });
                    return; // Stop here if not enough hours
                }

                // SweetAlert confirmation
                Swal.fire({
                    title: "Submit Proposal?",
                    text: "Once submitted, you can only modify it if rejected.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Submit",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById("proposalForm").submit(); // Proceed to submit
                    }
                });
            });

        function calculateTotalHours() {
            let total = 0;
            document.querySelectorAll(".hours-input").forEach((input) => {
                const val = parseFloat(input.value);
                if (!isNaN(val)) {
                    total += val;
                }
            });
            return total;
        }
    </script>
</body>

</html>