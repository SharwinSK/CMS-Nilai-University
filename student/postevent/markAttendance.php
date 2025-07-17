<?php
session_start();
include('../../db/dbconfig.php'); // adjust path as needed

if (!isset($_GET['rep_id']) || !isset($_GET['event_id'])) {
    die("Missing required parameters.");
}

$rep_id = $_GET['rep_id'];
$event_id = $_GET['event_id'];

// Fetch all COCU Claimers from committee table
$claimers = [];
$com_query = $conn->prepare("SELECT Com_ID, Com_Name FROM committee WHERE Ev_ID = ? AND Com_COCUClaimers = 1");
$com_query->bind_param("s", $event_id);
$com_query->execute();
$com_result = $com_query->get_result();
while ($row = $com_result->fetch_assoc()) {
    $claimers[] = $row;
}

// Fetch all postmortem meetings for this report
$meetings = [];
$meet_query = $conn->prepare("SELECT * FROM posteventmeeting WHERE Rep_ID = ?");
$meet_query->bind_param("s", $rep_id);
$meet_query->execute();
$meet_result = $meet_query->get_result();
while ($row = $meet_result->fetch_assoc()) {
    $meetings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4" style="background-color: #f6fff0;">
    <div class="container">
        <h3 class="mb-4">Mark Committee Attendance</h3>
        <form action="submitAttendance.php" method="POST">
            <input type="hidden" name="rep_id" value="<?= $rep_id ?>">
            <?php foreach ($meetings as $meeting): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        Meeting on <?= $meeting['Meeting_Date'] ?> (<?= $meeting['Start_Time'] ?> -
                        <?= $meeting['End_Time'] ?>)
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="meeting_ids[]" value="<?= $meeting['Meeting_ID'] ?>">
                        <p><strong>Location:</strong> <?= $meeting['Meeting_Location'] ?></p>
                        <p><strong>Description:</strong> <?= $meeting['Meeting_Description'] ?></p>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Committee ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($claimers as $claimer): ?>
                                    <tr>
                                        <td><?= $claimer['Com_ID'] ?></td>
                                        <td><?= $claimer['Com_Name'] ?></td>
                                        <td>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="attendance[<?= $meeting['Meeting_ID'] ?>][<?= $claimer['Com_ID'] ?>]"
                                                    value="Present" required>
                                                <label class="form-check-label">Present</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio"
                                                    name="attendance[<?= $meeting['Meeting_ID'] ?>][<?= $claimer['Com_ID'] ?>]"
                                                    value="Absent">
                                                <label class="form-check-label">Absent</label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success">Submit Attendance</button>
        </form>
    </div>
</body>

</html>