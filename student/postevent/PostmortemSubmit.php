<?php
session_start();
include('../../db/dbconfig.php');

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


    $conn->commit();
    unset($_SESSION['post_event_data']);

    // Return JSON
    header("Location: ../../model/confirmationPage.php?rep_id=$report_id&event_id=$event_id");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit;
}


?>