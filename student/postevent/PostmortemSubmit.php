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

// 1. Upload Event Photos
$photo_paths = [];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!empty($_FILES['eventPhotos']['name'][0])) {
    if (count($_FILES['eventPhotos']['name']) > 10) {
        die("Maximum 10 photos allowed.");
    }
    $uploadDir = "../../uploads/photos/";
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    foreach ($_FILES['eventPhotos']['tmp_name'] as $key => $tmp_name) {
        $original = pathinfo($_FILES['eventPhotos']['name'][$key], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($_FILES['eventPhotos']['name'][$key], PATHINFO_EXTENSION));
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', $original);
        $new_file_name = uniqid() . "_" . $slug . "." . $extension;
        $target_file = $uploadDir . $new_file_name;

        if (!move_uploaded_file($tmp_name, $target_file)) {
            die("Failed to upload: $original");
        }

        $photo_paths[] = $new_file_name;
    }
}
$photos = json_encode($photo_paths);

// 2. Upload Budget Statement
$statementFilePath = null;
if (isset($_FILES['budgetStatement']) && $_FILES['budgetStatement']['error'] == 0) {
    $targetDir = "../../uploads/statements/";
    if (!is_dir($targetDir))
        mkdir($targetDir, 0777, true);

    $fileName = basename($_FILES['budgetStatement']['name']);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    if (move_uploaded_file($_FILES['budgetStatement']['tmp_name'], $targetFilePath)) {
        $statementFilePath = $targetFilePath;
    }
}

// Start Transaction
$conn->begin_transaction();

try {
    // Step 4: Insert into EventPostmortem
    $stmt = $conn->prepare("
        INSERT INTO eventpostmortem (
            Rep_ID, Ev_ID, Rep_ChallengesDifficulties,
            Rep_Conclusion, Rep_recommendation, rep_photo, Status_ID
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

    // Step 8: Upload Individual Reports
    $targetDir = "../../uploads/individual_reports/";
    if (!is_dir($targetDir))
        mkdir($targetDir, 0777, true);

    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'individualReport') === 0 && $file['error'] === 0) {
            $com_id = str_replace(['individualReport', '[', ']'], '', $key);
            $originalName = basename($file["name"]);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $newFileName = $com_id . "_" . time() . "." . $extension;
            $targetPath = $targetDir . $newFileName;

            if (move_uploaded_file($file["tmp_name"], $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO individualreport (Rep_ID, Com_ID, IR_File) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $report_id, $com_id, $newFileName);
                $stmt->execute();
            }
        }
    }

    // Step 9: Update Budget Statement path (if uploaded)
    if ($statementFilePath) {
        $stmt = $conn->prepare("UPDATE budgetsummary SET statement = ? WHERE Ev_ID = ?");
        $stmt->bind_param("ss", $statementFilePath, $event_id);
        $stmt->execute();
    }

    $conn->commit();
    unset($_SESSION['post_event_data']);

    // Return JSON
    header("Location: ../model/confirmationPage.php?rep_id=$report_id&event_id=$event_id");
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