<?php
include('../../db/dbconfig.php');
session_start();

if ($_GET['mode'] !== 'create') {
    die("Invalid request mode.");
}

$stu_id = $_SESSION['Stu_ID'];

// Generate Event ID: NN/YY
$year_suffix = date('y');
$lastEventQuery = "SELECT MAX(Ev_ID) AS last_id FROM events";
$result = $conn->query($lastEventQuery);
$row = $result->fetch_assoc();

if ($row['last_id']) {
    preg_match('/^(\d+)/', $row['last_id'], $matches);
    $last_num = isset($matches[1]) ? (int) $matches[1] : 0;
    $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
} else {
    $new_num = '01';
}
$event_id = $new_num . '/' . $year_suffix;

// Upload Poster
$poster = null;
if (!empty($_FILES["eventPoster"]["name"])) {
    $target_dir = "../../uploads/posters/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $original_name = basename($_FILES["eventPoster"]["name"]);
    $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_name);
    $target_file = $target_dir . time() . '_' . $safe_name;
    if (move_uploaded_file($_FILES["eventPoster"]["tmp_name"], $target_file)) {
        $poster = $target_file;
    } else {
        die("Poster upload failed.");
    }
}

// Upload Additional Info
$additional_info_path = null;
if (!empty($_FILES["additionalDocument"]["name"])) {
    $target_dir = "../../uploads/additional/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $original_name = basename($_FILES["additionalDocument"]["name"]);
    $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $original_name);
    $unique_name = time() . '_' . $safe_name;
    $target_path = $target_dir . $unique_name;


    if (move_uploaded_file($_FILES["additionalDocument"]["tmp_name"], $target_path)) {
        $additional_info_path = $target_path;
    } else {
        die("Additional info upload failed.");
    }
}

// === Insert into events ===
$club_id = $_POST['club'];
$ev_name = $_POST['eventName'];
$ev_nature = $_POST['eventNature'];
$ev_objectives = $_POST['eventObjectives'];
$ev_intro = $_POST['eventIntroduction'];
$ev_details = $_POST['eventPurpose'];
$ev_date = $_POST['eventDate'];
$ev_start_time = $_POST['startTime'];
$ev_end_time = $_POST['endTime'];
$ev_pax = $_POST['estimatedParticipants'];
$ev_venue_id = $_POST['venue'];
$ev_alt_venue_id = !empty($_POST['altVenue']) ? $_POST['altVenue'] : null;
$ev_alt_date = !empty($_POST['alternativeDate']) ? $_POST['alternativeDate'] : null;

$stmt = $conn->prepare("INSERT INTO events (
    Ev_ID, Stu_ID, Club_ID, Ev_Name, Ev_ProjectNature, Ev_Objectives, Ev_Intro, Ev_Details,
    Ev_Date, Ev_StartTime, Ev_EndTime, Ev_Pax,
    Ev_VenueID, Ev_AltVenueID, Ev_AlternativeDate, Ev_AdditionalInfo, Ev_Poster, Status_ID
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

$stmt->bind_param(
    "sssssssssssisisss",
    $event_id,
    $stu_id,
    $club_id,
    $ev_name,
    $ev_nature,
    $ev_objectives,
    $ev_intro,
    $ev_details,
    $ev_date,
    $ev_start_time,
    $ev_end_time,
    $ev_pax,
    $ev_venue_id,
    $ev_alt_venue_id,
    $ev_alt_date,
    $additional_info_path,
    $poster
);
if (!$stmt->execute()) {
    die("Error inserting event data: " . $stmt->error);
}
$stmt->close();

// === Insert into PersonInCharge ===
$pic_id = $_POST['picId'];
$pic_name = $_POST['picName'];
$pic_phone = $_POST['picPhone'];

$stmt = $conn->prepare("INSERT INTO PersonInCharge (PIC_ID, Ev_ID, PIC_Name, PIC_PhnNum) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $pic_id, $event_id, $pic_name, $pic_phone);
$stmt->execute();
$stmt->close();

// === Insert Committee Members ===
$committee_stmt = $conn->prepare("INSERT INTO Committee 
    (Com_ID, Ev_ID, Com_Position, Com_Name, Com_Department, Com_PhnNum, Com_JobScope, Com_COCUClaimers, Student_statement)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$cocu_dir = "../../uploads/cocustatement/";
if (!is_dir($cocu_dir))
    mkdir($cocu_dir, 0777, true);
$fileIndex = 0;

foreach ($_POST['committeeName'] as $index => $name) {
    $id = $_POST['committeeId'][$index];
    $position = $_POST['committeePosition'][$index];
    $department = $_POST['committeeDepartment'][$index];
    $phone = $_POST['committeePhone'][$index];
    $job = $_POST['committeeJobScope'][$index];
    $cocu_status = $_POST['cocuClaimer'][$index];
    $cocu_statement_path = null;

    if ($cocu_status === "yes") {
        $filename = $_FILES['cocuStatement']['name'][$fileIndex];
        $tmp = $_FILES['cocuStatement']['tmp_name'][$fileIndex];

        if (!empty($filename) && !empty($tmp)) {
            $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "", $filename);
            $ext = pathinfo($safe_filename, PATHINFO_EXTENSION);
            $unique = $id . '_cocu_' . time() . '.' . $ext;
            $dest = $cocu_dir . $unique;

            if (move_uploaded_file($tmp, $dest)) {
                $cocu_statement_path = $dest;
            } else {
                die("COCU Statement upload failed.");
            }
        }
        $fileIndex++;
    }

    $committee_stmt->bind_param("sssssssss", $id, $event_id, $position, $name, $department, $phone, $job, $cocu_status, $cocu_statement_path);
    $committee_stmt->execute();
}
$committee_stmt->close();

// === Insert Event Flow (Minutes) ===
foreach ($_POST['eventFlowDate'] as $index => $date) {
    $start = $_POST['eventFlowStart'][$index];
    $end = $_POST['eventFlowEnd'][$index];
    $hours = $_POST['eventFlowHours'][$index];
    $activity = $_POST['eventFlowActivity'][$index];
    $remarks = $_POST['eventFlowRemarks'][$index];

    $stmt = $conn->prepare("INSERT INTO eventminutes (Ev_ID, Date, Start_Time, End_Time, Hours, Activity, Remarks) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $event_id, $date, $start, $end, $hours, $activity, $remarks);
    $stmt->execute();
}
$stmt->close();

// === Insert Budget ===
$total_income = 0;
$total_expense = 0;

$stmt = $conn->prepare("INSERT INTO Budget (Ev_ID, Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks) VALUES (?, ?, ?, ?, ?)");

foreach ($_POST['budgetDescription'] as $index => $desc) {
    $amount = (float) $_POST['budgetAmount'][$index];
    $type = ucfirst(strtolower($_POST['budgetType'][$index])); // Normalize value
    $remarks = $_POST['budgetRemarks'][$index];

    if ($type == "Income")
        $total_income += $amount;
    else if ($type == "Expense")
        $total_expense += $amount;

    $stmt->bind_param("ssdss", $event_id, $desc, $amount, $type, $remarks);
    $stmt->execute();
}
$stmt->close();

$surplus = $total_income - $total_expense;
$prepared_by = $_POST['preparedBy'];

$stmt = $conn->prepare("INSERT INTO BudgetSummary (Ev_ID, Total_Income, Total_Expense, Surplus_Deficit, Prepared_By)
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sddds", $event_id, $total_income, $total_expense, $surplus, $prepared_by);
$stmt->execute();
$stmt->close();
?>

<!-- ✅ Success Page -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Proposal Submitted</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex justify-content-center align-items-center" style="height:100vh; background-color:#f0ffe6">
    <div class="text-center p-4 shadow rounded" style="background:#D2FF72;">
        <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" alt="Success" width="100">
        <h2 class="mt-3 text-success">Proposal Submitted!</h2>
        <p>Your event has been submitted successfully.</p>
        <p><strong>Event ID: <?= $event_id ?></strong></p>
        <a href="../StudentDashboard.php" class="btn btn-success">Back to Dashboard</a>
    </div>
</body>

</html>