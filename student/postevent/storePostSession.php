<?php
session_start();

$response = ['success' => false];

// Validate event ID
if (!isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing event ID']);
    exit;
}

$event_id = $_POST['event_id'];

// Save Event Flows
$eventFlows = [];
if (!empty($_POST['eventTime']) && !empty($_POST['eventDescription'])) {
    foreach ($_POST['eventTime'] as $i => $time) {
        $desc = $_POST['eventDescription'][$i];
        $eventFlows[] = [
            'time' => $time,
            'description' => $desc
        ];
    }
}

/* ---------- ②  MEETINGS  (fixed key) ---------- */
$meetings = [];
foreach ($_POST['meetingDate'] ?? [] as $i => $date) {
    $meetings[] = [
        'date' => $date,
        'start_time' => $_POST['meetingStartTime'][$i] ?? '',
        'end_time' => $_POST['meetingEndTime'][$i] ?? '',
        'location' => $_POST['meetingLocation'][$i] ?? '',
        'description' => $_POST['meetingDescription'][$i] ?? ''   // ✅ correct key
    ];
}

$photoNames = [];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

if (!empty($_FILES['eventPhotos']['name'][0])) {
    if (count($_FILES['eventPhotos']['name']) > 10) {
        die("Maximum 10 photos allowed.");
    }

    $uploadDir = __DIR__ . "/../../uploads/photos/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['eventPhotos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['eventPhotos']['error'][$key] !== 0)
            continue;

        $file_size = $_FILES['eventPhotos']['size'][$key];
        $mime_type = mime_content_type($tmp_name);

        if (!in_array($mime_type, $allowed_types))
            continue;

        $orig = pathinfo($_FILES['eventPhotos']['name'][$key], PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($_FILES['eventPhotos']['name'][$key], PATHINFO_EXTENSION));
        $slug = preg_replace('/[^A-Za-z0-9_-]/', '_', $orig);
        $new_file = uniqid() . '_' . $slug . '.' . $ext;
        $target = $uploadDir . $new_file;

        if ($file_size > 3 * 1024 * 1024) {
            switch ($mime_type) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($tmp_name);
                    imagejpeg($img, $target, 75);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($tmp_name);
                    imagepng($img, $target, 6);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($tmp_name);
                    imagegif($img, $target);
                    break;
            }
            if (isset($img))
                imagedestroy($img);
        } else {
            if (!move_uploaded_file($tmp_name, $target))
                continue;
        }

        $photoNames[] = $new_file;
    }
}


/* ---------- ②  SAVE BUDGET STATEMENT ---------- */
$budgetFileName = '';
if (isset($_FILES['budgetStatement']) && $_FILES['budgetStatement']['error'] === 0) {
    $targetDirBudget = '../../uploads/statements/';
    if (!is_dir($targetDirBudget)) {
        mkdir($targetDirBudget, 0777, true);
    }

    $original = basename($_FILES['budgetStatement']['name']);
    $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $original);
    $newName = time() . "_" . $safeName;
    $targetPath = $targetDirBudget . $newName;

    if (move_uploaded_file($_FILES['budgetStatement']['tmp_name'], $targetPath)) {
        $budgetFileName = $newName; // ✅ Store just the clean filename
    } else {
        die("Failed to upload budget statement.");
    }
}


/* ---------- ③ SAVE INDIVIDUAL REPORTS ---------- */
$individualReports = [];
$targetDir = "../../uploads/individualreports/";

if (isset($_FILES['individualReport']) && is_array($_FILES['individualReport']['name'])) {
    foreach ($_FILES['individualReport']['name'] as $comId => $name) {
        $tmpName = $_FILES['individualReport']['tmp_name'][$comId];
        $error = $_FILES['individualReport']['error'][$comId];

        if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmpName)) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $newFileName = $comId . "_" . time() . "." . $extension;
            $targetPath = $targetDir . $newFileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $individualReports[$comId] = $newFileName;
            }
        }
    }
}


// Save everything into session
$_SESSION['post_event_data'] = [
    'event_id' => $event_id,
    'event_flows' => $eventFlows,
    'meetings' => $meetings,
    'challenges' => $_POST['challenges'] ?? '',
    'recommendation' => $_POST['recommendations'] ?? '',
    'conclusion' => $_POST['conclusion'] ?? '',
    'photo_filenames' => $photoNames,
    'budget_statement' => $budgetFileName,
    'individual_reports' => $individualReports
];


// Fetch committee claimers from DB (for attendance marking)
include('../../db/dbconfig.php');
$committee = [];
$stmt = $conn->prepare("SELECT Com_ID, Com_Name, Com_Position FROM committee WHERE Ev_ID = ? AND Com_COCUClaimers = 1");
$stmt->bind_param("s", $event_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $committee[] = $row;
}
$stmt->close();
$conn->close();

$_SESSION['post_event_data']['committee'] = $committee;
$_SESSION['post_event_data']['photo_filenames'] = $photoNames;
$_SESSION['post_event_data']['budget_statement'] = $budgetFileName;
$_SESSION['post_event_data']['individual_reports'] = $individualReports;



$response['success'] = true;
echo json_encode($response);
exit;
