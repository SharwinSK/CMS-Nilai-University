<?php
session_start();
include('dbconfig.php');

$query = "SELECT MAX(Rep_ID) AS last_id FROM EventPostmortem";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row['last_id']) {
    $last_id = (int) $row['last_id'];
    $report_id = str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);
} else {
    $report_id = '0001';
}

if (!isset($_POST['event_id'])) {
    die("Event ID is required to submit the postmortem report.");
}

$event_id = $_POST['event_id'];

$photo_paths = [];
if (!empty($_FILES['event_photos']['name'][0])) {
    if (!is_dir('uploads/photos')) {
        mkdir('uploads/photos', 0777, true);
    }
    foreach ($_FILES['event_photos']['tmp_name'] as $key => $tmp_name) {
        $file_name = basename($_FILES['event_photos']['name'][$key]);
        $target_file = "uploads/photos/" . $file_name;
        if (move_uploaded_file($tmp_name, $target_file)) {
            $photo_paths[] = $target_file;
        }
    }
}

$statement_path = null;
if (!empty($_FILES["statement_pdf"]["name"])) {
    $target_dir = "uploads/statements/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["statement_pdf"]["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($file_type !== "pdf") {
        die("Only PDF files are allowed for the statement.");
    }

    if ($_FILES["statement_pdf"]["size"] > 5 * 1024 * 1024) {
        die("The statement file must be under 5MB.");
    }

    if (move_uploaded_file($_FILES["statement_pdf"]["tmp_name"], $target_file)) {
        $statement_path = $target_file;
    } else {
        die("Failed to upload the statement.");
    }
}

$photos = json_encode($photo_paths);

$receipt_paths = [];
if (!empty($_FILES['expense_receipts']['name'][0])) {
    if (!is_dir('uploads/receipts')) {
        mkdir('uploads/receipts', 0777, true);
    }
    foreach ($_FILES['expense_receipts']['tmp_name'] as $key => $tmp_name) {
        $file_name = basename($_FILES['expense_receipts']['name'][$key]);
        $target_file = "uploads/receipts/" . $file_name;
        if (move_uploaded_file($tmp_name, $target_file)) {
            $receipt_paths[] = $target_file;
        }
    }
}
$receipts = json_encode($receipt_paths);

$challenges = htmlspecialchars(trim($_POST['challenges']));
$conclusion = htmlspecialchars(trim($_POST['conclusion']));



$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO EventPostmortem (Rep_ID, Ev_ID, 
    Rep_ChallengesDifficulties, Rep_Conclusion, Rep_Photo, Rep_Receipt) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $report_id, $event_id, $challenges, $conclusion, $photos, $receipts);
    $stmt->execute();

    foreach ($_POST['indiv_duties'] as $com_id => $duty) {
        $attendance = htmlspecialchars(trim($_POST['indiv_attendance'][$com_id]));
        $experience = htmlspecialchars(trim($_POST['indiv_experience'][$com_id]));
        $indiv_challenges = htmlspecialchars(trim($_POST['indiv_challenges'][$com_id]));
        $benefits = htmlspecialchars(trim($_POST['indiv_benefits'][$com_id]));

        $stmt = $conn->prepare("INSERT INTO IndividualReport (Rep_ID, Com_ID, IRS_Duties, 
        IRS_Attendance, IRS_Experience, IRS_Challenges, IRS_Benefits) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $report_id, $com_id, $duty, $attendance, $experience, $indiv_challenges, $benefits);
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