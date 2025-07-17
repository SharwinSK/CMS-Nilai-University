<?php
session_start();
include('../../db/dbconfig.php');

// Get the mode from URL
$mode = $_GET['mode'] ?? '';

// Validate mode
if ($mode !== 'create') {
    die("Invalid mode.");
}

// Step 1: Generate new Rep_ID
$query = "SELECT MAX(Rep_ID) AS last_id FROM EventPostmortem";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$report_id = $row['last_id'] ? str_pad((int) $row['last_id'] + 1, 4, '0', STR_PAD_LEFT) : '0001';

// Step 2: Validate event ID
if (!isset($_POST['event_id'])) {
    die("Event ID is required.");
}
$event_id = $_POST['event_id'];

// Step 3: Upload event photos
$photo_paths = [];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!empty($_FILES['event_photos']['name'][0])) {
    if (count($_FILES['event_photos']['name']) > 10) {
        die("Maximum 10 photos allowed.");
    }
    if (!is_dir('../../uploads/photos')) {
        mkdir('../../uploads/photos', 0777, true);
    }
    foreach ($_FILES['event_photos']['tmp_name'] as $key => $tmp_name) {
        $mime_type = mime_content_type($tmp_name);
        $file_size = $_FILES['event_photos']['size'][$key];
        $original = pathinfo($_FILES['event_photos']['name'][$key], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($_FILES['event_photos']['name'][$key], PATHINFO_EXTENSION));
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', $original);
        $new_file_name = uniqid() . "_" . $slug . "." . $extension;
        $target_file = "../../uploads/photos/" . $new_file_name;

        if ($file_size > 3 * 1024 * 1024) {
            switch ($mime_type) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($tmp_name);
                    imagejpeg($image, $target_file, 75);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($tmp_name);
                    imagepng($image, $target_file, 6);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($tmp_name);
                    imagegif($image, $target_file);
                    break;
                default:
                    die("Unsupported image type.");
            }
            imagedestroy($image);
        } else {
            if (!move_uploaded_file($tmp_name, $target_file)) {
                die("Failed to upload: $original");
            }
        }
        $photo_paths[] = $target_file;
    }
}

$photos = json_encode($photo_paths);

// Step 4: Upload budget statement
$statementFilePath = null;
if (isset($_FILES['statement_pdf']) && $_FILES['statement_pdf']['error'] == 0) {
    $targetDir = "../../uploads/statements/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = basename($_FILES['statement_pdf']['name']);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    if (move_uploaded_file($_FILES['statement_pdf']['tmp_name'], $targetFilePath)) {
        $statementFilePath = $targetFilePath;
    } else {
        die("Failed to upload statement.");
    }
}

// Step 5: Sanitize form inputs
$challenges = htmlspecialchars(trim($_POST['challenges']));
$conclusion = htmlspecialchars(trim($_POST['conclusion']));
$recommendation = htmlspecialchars(trim($_POST['recommendation']));

// Start Transaction
$conn->begin_transaction();

try {
    // Insert into EventPostmortem
    $status_id = 6; // Pending Review
    $stmt = $conn->prepare("
        INSERT INTO EventPostmortem (
            Rep_ID, Ev_ID, Rep_ChallengesDifficulties,
            Rep_Conclusion, Rep_recomendation, Rep_Photo, Status_ID
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssssi", $report_id, $event_id, $challenges, $conclusion, $recommendation, $photos, $status_id);
    $stmt->execute();

    // Insert event flow
    if (!empty($_POST['evflow_time']) && !empty($_POST['evflow_desc'])) {
        foreach ($_POST['evflow_time'] as $i => $time) {
            $desc = $_POST['evflow_desc'][$i];
            if (!empty($time) && !empty($desc)) {
                $stmt = $conn->prepare("INSERT INTO eventflows (Rep_ID, EvFlow_Time, EvFlow_Description) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $report_id, $time, $desc);
                $stmt->execute();
            }
        }
    }

    // Insert meetings
    if (!empty($_POST['meeting_date']) && !empty($_POST['start_time']) && !empty($_POST['end_time'])) {
        foreach ($_POST['meeting_date'] as $i => $date) {
            $start = $_POST['start_time'][$i];
            $end = $_POST['end_time'][$i];
            $desc = $_POST['meeting_description'][$i];
            $loc = $_POST['meeting_location'][$i];

            if (!empty($date) && !empty($start) && !empty($end) && !empty($desc) && !empty($loc)) {
                $stmt = $conn->prepare("
                    INSERT INTO posteventmeeting 
                    (Rep_ID, Meeting_Date, Start_Time, End_Time, Meeting_Description, Meeting_Location)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssss", $report_id, $date, $start, $end, $desc, $loc);
                $stmt->execute();
            }
        }
    }

    // Insert individual reports
    $targetDir = "../../uploads/individual_reports/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'ir_file_') === 0 && $file['error'] === 0) {
            $com_id = str_replace('ir_file_', '', $key);
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

    // Update budget statement
    if ($statementFilePath) {
        $stmt = $conn->prepare("UPDATE BudgetSummary SET statement = ? WHERE Ev_ID = ?");
        $stmt->bind_param("ss", $statementFilePath, $event_id);
        $stmt->execute();
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

$conn->close();

// Redirect to mark attendance
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "rep_id" => $report_id,
    "event_id" => $event_id
]);
exit;

?>