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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postmortem Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0ffe6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Roboto', sans-serif;
        }

        .confirmation-card {
            background-color: #D2FF72;
            border: 2px solid #D2FF72;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 90%;
        }

        .confirmation-card img {
            width: 120px;
            margin-bottom: 20px;
        }

        .confirmation-card h1 {
            font-size: 2.5em;
            color: #4caf50;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .confirmation-card p {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
        }

        .btn {
            border-radius: 25px;
            font-size: 1em;
            padding: 10px 25px;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }

        .btn-return {
            background-color: #15B392;
            color: white;
            border: 2px solid #15B392;
        }

        .btn-return:hover {
            background-color: white;
            color: #9BEC00;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
            border: 2px solid #007bff;
        }

        .btn-primary:hover {
            background-color: white;
            color: #007bff;
        }
    </style>
</head>

<body>
    <div class="confirmation-card">
        <!-- Checkmark Icon -->
        <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" alt="Success Icon">
        <h1>Thank You!</h1>
        <p>Your postmortem report has been submitted successfully.</p>
        <p><strong>Report ID: <?php echo $report_id; ?></strong></p>
        <a href="StudentDashboard.php" class="btn btn-return">Return to Dashboard</a>
        <a href="reportgeneratepdf.php?id=<?php echo $report_id; ?>" class="btn btn-primary">Export to
            PDF</a>
        <p class="mt-4 text-muted">
            <small>
                <a href="https://www.flaticon.com/free-icons/success" title="success icons"></a>
            </small>
        </p>
    </div>
</body>


</html>