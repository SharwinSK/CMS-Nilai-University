<?php
session_start();
include('dbconfig.php');

// Generate new Report ID
$query = "SELECT MAX(Rep_ID) AS last_id FROM EventPostmortem";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row['last_id']) {
    $last_id = (int) $row['last_id'];
    $report_id = str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);
} else {
    $report_id = '0001';
}

// Validate event ID
if (!isset($_POST['event_id'])) {
    die("Event ID is required to submit the postmortem report.");
}

$event_id = $_POST['event_id'];

// Upload Photos
$photo_paths = [];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

if (!empty($_FILES['event_photos']['name'][0])) {
    if (count($_FILES['event_photos']['name']) > 10) {
        die("You can upload a maximum of 10 photos.");
    }

    if (!is_dir('uploads/photos')) {
        mkdir('uploads/photos', 0777, true);
    }

    foreach ($_FILES['event_photos']['tmp_name'] as $key => $tmp_name) {
        $mime_type = mime_content_type($tmp_name);
        $file_size = $_FILES['event_photos']['size'][$key];

        if (!in_array($mime_type, $allowed_types)) {
            die("Only JPG, PNG, and GIF images are allowed.");
        }

        $original = pathinfo($_FILES['event_photos']['name'][$key], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($_FILES['event_photos']['name'][$key], PATHINFO_EXTENSION));
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', $original);
        $new_file_name = uniqid() . "_" . $slug . "." . $extension;

        $target_file = "uploads/photos/" . $new_file_name;

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
                die("Failed to upload: $original_name");
            }
        }

        $photo_paths[] = $target_file;
    }
}

$statementFilePath = null;
if (isset($_FILES['statement_pdf']) && $_FILES['statement_pdf']['error'] == 0) {
    $targetDir = "uploads/statements/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = basename($_FILES['statement_pdf']['name']);
    $targetFilePath = $targetDir . time() . "_" . $fileName;

    if (move_uploaded_file($_FILES['statement_pdf']['tmp_name'], $targetFilePath)) {
        $statementFilePath = $targetFilePath;
    } else {
        die("Failed to upload statement file.");
    }
}

$photos = json_encode($photo_paths);
$challenges = htmlspecialchars(trim($_POST['challenges']));
$conclusion = htmlspecialchars(trim($_POST['conclusion']));
$recommendation = htmlspecialchars(trim($_POST['recommendation']));


$conn->begin_transaction();

try {
    // Insert into EventPostmortem 
    $stmt = $conn->prepare("
    INSERT INTO EventPostmortem (
        Rep_ID,
        Ev_ID,
        Rep_ChallengesDifficulties,
        Rep_Conclusion,
        Rep_recomendation,
        Rep_Photo
    ) VALUES (?, ?, ?, ?, ?, ?)
");
    $stmt->bind_param(
        "ssssss",
        $report_id,
        $event_id,
        $challenges,
        $conclusion,
        $recommendation,
        $photos
    );
    $stmt->execute();


    $targetDir = "uploads/individual_reports/";
    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'ir_file_') === 0 && $file['error'] === 0) {
            // Extract Com_ID from the field name
            $com_id = str_replace('ir_file_', '', $key);

            // Prepare filename
            $originalName = basename($file["name"]);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $newFileName = $com_id . "_" . time() . "." . $extension;
            $targetPath = $targetDir . $newFileName;

            // Move uploaded file
            if (move_uploaded_file($file["tmp_name"], $targetPath)) {
                // Insert into DB
                $stmt = $conn->prepare("INSERT INTO individualreport (Rep_ID, Com_ID, IR_File) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $report_id, $com_id, $newFileName);
                $stmt->execute();
            }
        }
    }

    if (!empty($_POST['evflow_time']) && !empty($_POST['evflow_desc'])) {
        $times = $_POST['evflow_time'];
        $descs = $_POST['evflow_desc'];

        for ($i = 0; $i < count($times); $i++) {
            $time = $conn->real_escape_string($times[$i]);
            $desc = $conn->real_escape_string($descs[$i]);

            if (!empty($time) && !empty($desc)) {
                // -------------------  event-flow insert  -------------------
                $insertFlow = $conn->prepare(
                    "INSERT INTO eventflows (Rep_ID, EvFlow_Time, EvFlow_Description) VALUES (?, ?, ?)"
                );
                $insertFlow->bind_param("sss", $report_id, $time, $desc);

                $insertFlow->execute();

            }

        }
    }

    // Update BudgetSummary with statement file
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
?>

<?php
session_start();
include('dbconfig.php');

// Generate new Report ID
$query = "SELECT MAX(Rep_ID) AS last_id FROM EventPostmortem";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$report_id = $row['last_id'] ? str_pad((int) $row['last_id'] + 1, 4, '0', STR_PAD_LEFT) : '0001';

// Validate event ID
if (!isset($_POST['event_id'])) {
    die("Event ID is required to submit the postmortem report.");
}
$event_id = $_POST['event_id'];

// Upload Photos
$photo_paths = [];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!empty($_FILES['event_photos']['name'][0])) {
    if (count($_FILES['event_photos']['name']) > 10) {
        die("You can upload a maximum of 10 photos.");
    }
    if (!is_dir('uploads/photos')) {
        mkdir('uploads/photos', 0777, true);
    }
    foreach ($_FILES['event_photos']['tmp_name'] as $key => $tmp_name) {
        $mime_type = mime_content_type($tmp_name);
        $file_size = $_FILES['event_photos']['size'][$key];
        $original = pathinfo($_FILES['event_photos']['name'][$key], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($_FILES['event_photos']['name'][$key], PATHINFO_EXTENSION));
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', $original);
        $new_file_name = uniqid() . "_" . $slug . "." . $extension;
        $target_file = "uploads/photos/" . $new_file_name;

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

$statementFilePath = null;
if (isset($_FILES['statement_pdf']) && $_FILES['statement_pdf']['error'] == 0) {
    $targetDir = "uploads/statements/";
    if (!is_dir($targetDir))
        mkdir($targetDir, 0777, true);
    $fileName = basename($_FILES['statement_pdf']['name']);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    if (move_uploaded_file($_FILES['statement_pdf']['tmp_name'], $targetFilePath)) {
        $statementFilePath = $targetFilePath;
    } else {
        die("Failed to upload statement file.");
    }
}

$photos = json_encode($photo_paths);
$challenges = htmlspecialchars(trim($_POST['challenges']));
$conclusion = htmlspecialchars(trim($_POST['conclusion']));
$recommendation = htmlspecialchars(trim($_POST['recommendation']));

$conn->begin_transaction();

try {
    // Insert into EventPostmortem
    $stmt = $conn->prepare("
        INSERT INTO EventPostmortem (
            Rep_ID, Ev_ID, Rep_ChallengesDifficulties,
            Rep_Conclusion, Rep_recomendation, Rep_Photo
        ) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $report_id, $event_id, $challenges, $conclusion, $recommendation, $photos);
    $stmt->execute();

    // Insert Event Flow
    if (!empty($_POST['evflow_time']) && !empty($_POST['evflow_desc'])) {
        $times = $_POST['evflow_time'];
        $descs = $_POST['evflow_desc'];
        for ($i = 0; $i < count($times); $i++) {
            $time = $conn->real_escape_string($times[$i]);
            $desc = $conn->real_escape_string($descs[$i]);
            if (!empty($time) && !empty($desc)) {
                $insertFlow = $conn->prepare("INSERT INTO eventflows (Rep_ID, EvFlow_Time, EvFlow_Description) VALUES (?, ?, ?)");
                $insertFlow->bind_param("sss", $report_id, $time, $desc);
                $insertFlow->execute();
            }
        }
    }

    // Insert Individual Reports
    $targetDir = "uploads/individual_reports/";
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

    // Update Budget Summary
    if ($statementFilePath) {
        $stmt = $conn->prepare("UPDATE BudgetSummary SET statement = ? WHERE Ev_ID = ?");
        $stmt->bind_param("ss", $statementFilePath, $event_id);
        $stmt->execute();
    }

    // Insert Post-Event Meeting
    if (!empty($_POST['meeting_date']) && !empty($_POST['start_time']) && !empty($_POST['end_time'])) {
        $meetingDates = $_POST['meeting_date'];
        $startTimes = $_POST['start_time'];
        $endTimes = $_POST['end_time'];
        $descriptions = $_POST['meeting_description'];
        $locations = $_POST['meeting_location'];

        for ($i = 0; $i < count($meetingDates); $i++) {
            $date = $conn->real_escape_string($meetingDates[$i]);
            $start = $conn->real_escape_string($startTimes[$i]);
            $end = $conn->real_escape_string($endTimes[$i]);
            $desc = $conn->real_escape_string($descriptions[$i]);
            $loc = $conn->real_escape_string($locations[$i]);

            if (!empty($date) && !empty($start) && !empty($end) && !empty($desc) && !empty($loc)) {
                $insertMeeting = $conn->prepare("
                INSERT INTO posteventmeeting 
                (Rep_ID, Meeting_Date, Start_Time, End_Time, Meeting_Description, Meeting_Location)
                VALUES (?, ?, ?, ?, ?, ?)");
                $insertMeeting->bind_param("ssssss", $report_id, $date, $start, $end, $desc, $loc);
                $insertMeeting->execute();
            }
        }
    }

    $conn->commit();




} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}

$conn->close();  // ✅ Close the connection properly

// Redirect to mark attendance page
header("Location: markAttendance.php?rep_id=$report_id&event_id=$event_id");
exit();

?>