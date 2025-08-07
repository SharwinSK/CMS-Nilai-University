<?php
session_start();
include('../../db/dbconfig.php');
include('../model/sendMailTemplates.php');


// 1. Validate
$mode = $_POST['mode'] ?? '';
$rep_id = $_POST['rep_id'] ?? '';
$ev_id = $_POST['ev_id'] ?? '';

if (!in_array($mode, ['edit', 'modify']) || empty($rep_id) || empty($ev_id)) {
    die("Invalid request");
}

$status_id = 6; // Reset to Postmortem Pending Review
$conn->begin_transaction();

try {
    // 🟩 Handle photo uploads
    $existingPhotos = $_POST['existing_photos'] ?? [];
    $removedPhotos = $_POST['removed_photos'] ?? [];
    $finalPhotos = array_diff($existingPhotos, $removedPhotos);

    if (!empty($_FILES['eventPhotos']['name'][0])) {
        $uploadDir = '../../uploads/photos/';
        foreach ($_FILES['eventPhotos']['name'] as $i => $name) {
            $tmpName = $_FILES['eventPhotos']['tmp_name'][$i];
            if (!empty($tmpName)) {
                $newName = uniqid("photo_") . "_" . basename($name);
                $targetPath = $uploadDir . $newName;
                move_uploaded_file($tmpName, $targetPath);
                $finalPhotos[] = $newName;
            }
        }
    }

    $photosJSON = json_encode(array_values($finalPhotos));

    // 🟩 Handle budget statement
    $budgetPath = $_POST['existing_budget'] ?? '';
    if (isset($_POST['remove_existing_budget'])) {
        $budgetPath = '';
    }

    if (!empty($_FILES['budgetStatement']['name'])) {
        $uploadDir = '../../uploads/statements/'; // ✅ Match create mode folder
        $newName = uniqid("budget_") . "_" . basename($_FILES['budgetStatement']['name']);
        $targetPath = $uploadDir . $newName;
        move_uploaded_file($_FILES['budgetStatement']['tmp_name'], $targetPath);
        $budgetPath = $newName; // ✅ Only filename, not full path
    }


    // 🟩 Update eventpostmortem
    $stmt = $conn->prepare("UPDATE eventpostmortem 
        SET Rep_ChallengesDifficulties = ?, Rep_Conclusion = ?, Rep_recomendation = ?, rep_photo = ?, Status_ID = ? 
        WHERE Rep_ID = ?");
    $stmt->bind_param("ssssis", $_POST['challenges'], $_POST['conclusion'], $_POST['recommendations'], $photosJSON, $status_id, $rep_id);
    $stmt->execute();

    // 🟩 Update budget summary
    $stmt = $conn->prepare("UPDATE budgetsummary SET statement = ? WHERE Ev_ID = ?");
    $stmt->bind_param("ss", $budgetPath, $ev_id);
    $stmt->execute();

    // Add this BEFORE the foreach loop for event_flows
    $conn->query("DELETE FROM eventflows WHERE Rep_ID = '$rep_id'");

    // Then your existing insert code will work properly
    foreach ($_POST['event_flows'] as $flow) {
        $time = $flow['time'] ?? '';
        $desc = $flow['description'] ?? '';

        if (!empty($time) && !empty($desc)) {
            $stmt = $conn->prepare("INSERT INTO eventflows (Rep_ID, EvFlow_Time, EvFlow_Description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $rep_id, $time, $desc);
            $stmt->execute();
            $stmt->close();
        }
    }
    $meetingIDs = [];
    $newMeetingMap = [];

    $rawMeetingRows = $_POST['meetingDate'];
    $index = 0;

    foreach ($meetingDates as $i => $date) {
        $start = $startTimes[$i] ?? '';
        $end = $endTimes[$i] ?? '';
        $location = $locations[$i] ?? '';
        $desc = $_POST['meeting_descriptions'][$i] ?? '';

        $meetingRowId = $_POST['meetingRowIds'][$i] ?? 'new-' . $index; // fallback if not set

        $stmt = $conn->prepare("INSERT INTO posteventmeeting (Rep_ID, Meeting_Date, Start_Time, End_Time, Meeting_Description, Meeting_Location) 
                            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $rep_id, $date, $start, $end, $desc, $location);
        $stmt->execute();

        $newId = $conn->insert_id;
        $newMeetingMap[$meetingRowId] = $newId;
        $index++;
    }

    // 🟩 Attendance - Re-insert fresh values
    foreach ($_POST['attendance'] as $comId => $meetingStatuses) {
        foreach ($meetingStatuses as $meetingID => $status) {
            // If meeting ID is a "new-5" type, convert it
            if (isset($newMeetingMap[$meetingID])) {
                $meetingID = $newMeetingMap[$meetingID];
            }

            // Now insert only if it's a valid integer
            if (is_numeric($meetingID)) {
                $stmt = $conn->prepare("INSERT INTO committeeattendance (Rep_ID, Meeting_ID, Com_ID, Attendance_Status) 
                                    VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $rep_id, $meetingID, $comId, $status);
                $stmt->execute();
                $stmt->close();
            }
        }
    }



    // 🟩 Individual Reports - DELETE all first, then INSERT new ones
    $conn->query("DELETE FROM individualreport WHERE Rep_ID = '$rep_id'");
    $removedReports = $_POST['removed_individual_reports'] ?? [];

    if (!empty($_POST['committeeId'])) {
        foreach ($_POST['committeeId'] as $i => $comId) {
            // Skip if marked for removal
            if (in_array($comId, $removedReports)) {
                continue;
            }

            $fileName = '';

            // Check if new file uploaded
            if (!empty($_FILES['individualReport']['name'][$comId])) {
                $uploadDir = '../../uploads/individualreports/';
                $originalName = $_FILES['individualReport']['name'][$comId];
                $newName = uniqid("ir_") . "_" . basename($originalName);
                $targetPath = $uploadDir . $newName;

                if (move_uploaded_file($_FILES['individualReport']['tmp_name'][$comId], $targetPath)) {
                    $fileName = $newName;
                }
            } else {
                // Keep existing file if no new file uploaded
                $existingFile = $_POST['existing_individual_report'][$comId] ?? '';
                if (!empty($existingFile)) {
                    $fileName = basename($existingFile);
                }
            }

            // Insert record (safe now because we deleted all records for this Rep_ID first)
            if (!empty($fileName)) {
                $stmt = $conn->prepare("INSERT INTO individualreport (Rep_ID, Com_ID, IR_File) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $rep_id, $comId, $fileName);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    if ($mode === 'modify') {
        // 🟩 Fetch event info
        $stmt = $conn->prepare("SELECT Ev_Name, Club_ID, Stu_ID FROM events WHERE Ev_ID = ?");
        $stmt->bind_param("s", $ev_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $eventRow = $result->fetch_assoc();
        $eventName = $eventRow['Ev_Name'];
        $clubID = $eventRow['Club_ID'];
        $studentID = $eventRow['Stu_ID'];
        $stmt->close();

        // 🟩 Fetch student name
        $stmt = $conn->prepare("SELECT Stu_Name FROM student WHERE Stu_ID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $studentName = $result->fetch_assoc()['Stu_Name'];
        $stmt->close();

        // 🟩 Fetch coordinator info
        $stmt = $conn->prepare("SELECT coordinator.Coor_Name, coordinator.Coor_Email 
                            FROM club 
                            JOIN coordinator ON club.Coordinator_ID = coordinator.Coor_ID 
                            WHERE club.Club_ID = ?");
        $stmt->bind_param("s", $clubID);
        $stmt->execute();
        $result = $stmt->get_result();
        $coorRow = $result->fetch_assoc();
        $coordinatorName = $coorRow['Coor_Name'];
        $coordinatorEmail = $coorRow['Coor_Email'];
        $stmt->close();

        // ✅ Send email
        postEventSubmitted($coordinatorName, $eventName, $studentName, $coordinatorEmail);
    }

    // ✅ Commit all changes
    $conn->commit();
    header("Location: ../../model/confirmationPage.php?rep_id=$rep_id&event_id=$ev_id");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
?>