<?php

session_start();
include('../db/dbconfig.php'); // Adjust path if needed
//include('sendMailTemplates.php');


if (!isset($_GET['mode']) || $_GET['mode'] !== 'create') {
    die("Invalid access mode.");
}

// 🌟 Step 1: Generate new Event ID (format: NN/YY)
$year_suffix = date('y');
$query = "SELECT MAX(Ev_ID) AS last_id FROM events";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row && $row['last_id']) {
    preg_match('/^(\d+)/', $row['last_id'], $matches);
    $last_num = isset($matches[1]) ? (int) $matches[1] : 0;
    $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
} else {
    $new_num = '01';
}

$event_id = $new_num . '/' . $year_suffix;

// 🌟 Step 2: Handle Poster Upload
$poster = null;
if (!empty($_FILES["eventPoster"]["name"])) {
    $target_dir = "uploads/posters/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $poster_name = time() . '_' . basename($_FILES["eventPoster"]["name"]);
    $target_file = $target_dir . $poster_name;

    if (move_uploaded_file($_FILES["eventPoster"]["tmp_name"], $target_file)) {
        $poster = $target_file;
    } else {
        die("Poster upload failed.");
    }
}

// 🌟 Step 3: Handle Additional Document Upload
$additional_info_path = null;
if (!empty($_FILES["additionalDocument"]["name"])) {
    $target_dir = "uploads/additional/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $filename = time() . '_' . basename($_FILES["additionalDocument"]["name"]);
    $target_path = $target_dir . $filename;

    if (move_uploaded_file($_FILES["additionalDocument"]["tmp_name"], $target_path)) {
        $additional_info_path = $target_path;
    } else {
        die("Additional document upload failed.");
    }
}

// 🌟 Step 4: Read POST values
$stu_id = $_SESSION['Stu_ID']; // From session

$ev_name = $_POST['eventName'];
$ev_nature = $_POST['eventNature'];
$ev_objectives = $_POST['eventObjectives'];
$ev_intro = $_POST['eventIntroduction'];
$ev_details = $_POST['eventPurpose'];
$ev_date = $_POST['eventDate'];
$ev_start_time = $_POST['startTime'];
$ev_end_time = $_POST['endTime'];
$ev_pax = $_POST['estimatedParticipants'];
$ev_alt_date = $_POST['alternativeDate'];
$prepared_by = $_POST['preparedBy'];

$pic_name = $_POST['picName'];
$pic_id = $_POST['picId'];
$pic_phone = $_POST['picPhone'];

// 🌟 Step 5: Convert Venue Names to IDs (main + alt)
function getVenueId($conn, $venue_name)
{
    $stmt = $conn->prepare("SELECT Venue_ID FROM venue WHERE Venue_Name = ?");
    $stmt->bind_param("s", $venue_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $venue_id = $result->fetch_assoc()['Venue_ID'] ?? null;
    $stmt->close();
    return $venue_id;
}

$ev_venue_id = getVenueId($conn, $_POST['venue']);
$ev_alt_venue_id = getVenueId($conn, $_POST['alternativeVenue']);

// 🌟 Step 6: Get Club ID from Club Name
function getClubId($conn, $club_name)
{
    $stmt = $conn->prepare("SELECT Club_ID FROM club WHERE Club_Name = ?");
    $stmt->bind_param("s", $club_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $club_id = $result->fetch_assoc()['Club_ID'] ?? null;
    $stmt->close();
    return $club_id;
}

$club_id = getClubId($conn, $_POST['club']);

// 🌟 Step 7: Insert into events table
$status_id = 1; // Pending Advisor Review

$stmt = $conn->prepare("INSERT INTO events (
    Ev_ID, Stu_ID, Club_ID, Ev_Name,
    Ev_ProjectNature, Ev_Objectives, Ev_Intro, Ev_Details,
    Ev_Date, Ev_StartTime, Ev_EndTime, Ev_Pax,
    Ev_VenueID, Ev_AltVenueID, Ev_AlternativeDate,
    Ev_AdditionalInfo, Ev_Poster, Status_ID
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssisssssssiiissssi",
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
    $poster,
    $status_id
);

if (!$stmt->execute()) {
    die("❌ Error inserting into events: " . $stmt->error);
}
$stmt->close();

// 🌟 Step 8: Insert into PersonInCharge
$stmt = $conn->prepare("INSERT INTO personincharge (PIC_ID, Ev_ID, PIC_Name, PIC_PhnNum)
                        VALUES (?, ?, ?, ?)");

$stmt->bind_param("ssss", $pic_id, $event_id, $pic_name, $pic_phone);

if (!$stmt->execute()) {
    die("❌ Error inserting Person in Charge: " . $stmt->error);
}
$stmt->close();

// 🌟 Step 9: Insert Committee Members
$stmt = $conn->prepare("INSERT INTO committee 
    (Com_ID, Ev_ID, Com_Position, Com_Name, Com_Department, Com_PhnNum, Com_JobScope, Com_COCUClaimers, Student_statement) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("❌ Prepare failed for committee: " . $conn->error);
}

// Index for cocu_statement file input
$fileIndex = 0;

foreach ($_POST['committeeName'] as $index => $name) {
    $id = trim($_POST['committeeId'][$index]);
    $position = trim($_POST['committeePosition'][$index]);
    $department = trim($_POST['committeeDepartment'][$index]);
    $phone = trim($_POST['committeePhone'][$index]);
    $job = trim($_POST['committeeJob'][$index]);
    $cocu_status = trim($_POST['cocuClaim'][$index]);

    $cocu_statement_path = null;

    // Only upload PDF if cocu is "Yes" (value = 1)
    if ($cocu_status == '1') {
        $cocu_file_name = $_FILES['cocuStatement']['name'][$fileIndex];
        $cocu_tmp_path = $_FILES['cocuStatement']['tmp_name'][$fileIndex];

        if (!empty($cocu_file_name) && !empty($cocu_tmp_path)) {
            $ext = pathinfo($cocu_file_name, PATHINFO_EXTENSION);
            $unique_name = $id . '_cocu_' . time() . '.' . $ext;

            $target_dir = "uploads/cocustatement/";
            if (!is_dir($target_dir))
                mkdir($target_dir, 0777, true);

            $target_path = $target_dir . $unique_name;

            if (move_uploaded_file($cocu_tmp_path, $target_path)) {
                $cocu_statement_path = $target_path;
            } else {
                die("❌ File upload failed for COCU statement.");
            }
        }

        $fileIndex++; // Only increment if cocu = Yes
    }

    // Insert committee member
    $stmt->bind_param(
        "sssssssss",
        $id,
        $event_id,
        $position,
        $name,
        $department,
        $phone,
        $job,
        $cocu_status,
        $cocu_statement_path
    );

    if (!$stmt->execute()) {
        die("❌ Error inserting committee: " . $stmt->error);
    }
}
$stmt->close();

// 🌟 Step 10: Insert Event Flow (eventminutes)
$stmt = $conn->prepare("INSERT INTO eventminutes (
    Ev_ID, Date, Start_Time, End_Time, Hours, Activity, Remarks
) VALUES (?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("❌ Prepare failed for eventminutes: " . $conn->error);
}

foreach ($_POST['eventFlowDate'] as $index => $date) {
    $start_time = trim($_POST['eventFlowStart'][$index]);
    $end_time = trim($_POST['eventFlowEnd'][$index]);
    $hours = trim($_POST['eventFlowHours'][$index]);
    $activity = trim($_POST['eventFlowActivity'][$index]);
    $remarks = trim($_POST['eventFlowRemarks'][$index]);

    $stmt->bind_param("sssssss", $event_id, $date, $start_time, $end_time, $hours, $activity, $remarks);

    if (!$stmt->execute()) {
        die("❌ Error inserting event flow: " . $stmt->error);
    }
}
$stmt->close();

// 🌟 Step 11: Insert Budget Items
$stmt = $conn->prepare("INSERT INTO budget (
    Ev_ID, Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks
) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    die("❌ Prepare failed for budget: " . $conn->error);
}

foreach ($_POST['budgetDescription'] as $index => $desc) {
    $amount = (float) trim($_POST['budgetAmount'][$index]);
    $type = trim($_POST['budgetType'][$index]); // "Income" or "Expense"
    $remarks = trim($_POST['budgetRemarks'][$index]);

    $stmt->bind_param("ssdss", $event_id, $desc, $amount, $type, $remarks);

    if (!$stmt->execute()) {
        die("❌ Error inserting budget: " . $stmt->error);
    }
}
$stmt->close();

// 🌟 Step 12: Calculate Budget Summary
$total_income = 0;
$total_expense = 0;

foreach ($_POST['budgetAmount'] as $index => $amount) {
    $type = $_POST['budgetType'][$index];
    $amount = (float) trim($amount);

    if (strtolower($type) === 'income') {
        $total_income += $amount;
    } elseif (strtolower($type) === 'expense') {
        $total_expense += $amount;
    }
}

$surplus = $total_income - $total_expense;

// 🌟 Step 13: Insert Budget Summary
$stmt = $conn->prepare("INSERT INTO budgetsummary (
    Ev_ID, Total_Income, Total_Expense, Surplus_Deficit, Prepared_By
) VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("sddds", $event_id, $total_income, $total_expense, $surplus, $prepared_by);

if (!$stmt->execute()) {
    die("❌ Error inserting budget summary: " . $stmt->error);
}
$stmt->close();

/*🌟 Step 14: Notify Advisor via Email
$advisorQuery = "
    SELECT a.Adv_Name, a.Adv_Email 
    FROM advisor a
    JOIN club c ON a.Club_ID = c.Club_ID 
    WHERE c.Club_ID = ?
";
";
$stmt = $conn->prepare($advisorQuery);
$stmt->bind_param("s", $club_id);
$stmt->execute();
$result = $stmt->get_result();
$advisorData = $result->fetch_assoc();
$stmt->close();

if ($advisorData) {
    $advisorName = $advisorData['Adv_Name'];
    $advisorEmail = $advisorData['Adv_Email'];

    // Get student name
    $studentQuery = $conn->prepare("SELECT Stu_Name FROM student WHERE Stu_ID = ?");
    $studentQuery->bind_param("s", $stu_id);
    $studentQuery->execute();
    $studentResult = $studentQuery->get_result();
    $studentName = $studentResult->fetch_assoc()['Stu_Name'];
    $studentQuery->close();

    // ✨ Send the email
    newProposalToAdvisor($studentName, $ev_name, $advisorName, $advisorEmail);
}*/
