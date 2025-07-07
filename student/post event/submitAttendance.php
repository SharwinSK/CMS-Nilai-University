<?php
session_start();
include('dbconfig.php');

if (!isset($_POST['rep_id']) || !isset($_POST['attendance'])) {
    die("Missing required data.");
}

$rep_id = $_POST['rep_id'];
$attendance = $_POST['attendance'];

$conn->begin_transaction();

try {
    foreach ($attendance as $meeting_id => $memberList) {
        foreach ($memberList as $com_id => $status) {
            $stmt = $conn->prepare("INSERT INTO committeeattendance (Rep_ID, Meeting_ID, Com_ID, Attendance_Status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $rep_id, $meeting_id, $com_id, $status);
            $stmt->execute();
        }
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Error saving attendance: " . $e->getMessage());
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
        <p><strong>Report ID: <?php echo htmlspecialchars($rep_id); ?></strong></p>
        <a href="StudentDashboard.php" class="btn btn-return">Return to Dashboard</a>
        <a href="reportgeneratepdf.php?id=<?php echo urlencode($rep_id); ?>" class="btn btn-primary"
            target="_blank">Export to PDF</a>

    </div>
</body>

</html>