<?php
session_start();
include('../../db/dbconfig.php');
include('../../model/sendMailTemplates.php');

// Step 1: Validate mode and event ID
$mode = $_GET['mode'] ?? '';
$ev_id = $_GET['id'] ?? '';
if (!in_array($mode, ['edit', 'modify']) || empty($ev_id)) {
    die("Invalid request.");
}

// Step 2: Fetch current event status
$stmt = $conn->prepare("SELECT Status_ID FROM events WHERE Ev_ID = ?");
$stmt->bind_param("s", $ev_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if (!$row)
    die("Event not found.");

$current_status = (int) $row['Status_ID'];
$new_status = $current_status;

// Step 3: Apply mode rules
if ($mode === 'edit') {
    if (!in_array($current_status, [1, 3])) {
        die("Editing not allowed. Event already reviewed.");
    }
    // Keep status as-is
} elseif ($mode === 'modify') {
    if ($current_status === 2) {
        $new_status = 1; // advisor rejected → re-review by advisor
    } elseif ($current_status === 4) {
        $new_status = 3; // coordinator rejected → re-review by coordinator
    } else {
        die("Modification not allowed. Invalid status.");
    }
}


$conn->begin_transaction();

try {
    // Collect fields from POST
    $ev_name = $_POST['Ev_Name'];
    $ev_objectives = $_POST['Ev_Objectives'];
    $ev_nature = $_POST['Ev_ProjectNature']; // dropdown
    $ev_intro = $_POST['Ev_Intro'];
    $ev_details = $_POST['Ev_Details'];
    $ev_pax = $_POST['Ev_Pax'];
    $ev_date = $_POST['Ev_Date'];
    $ev_start = $_POST['Ev_StartTime'];
    $ev_end = $_POST['Ev_EndTime'];
    $venue_id = $_POST['Ev_VenueID'];
    $alt_venue = $_POST['altVenue'];
    $alt_date = $_POST['Ev_AlternativeDate'];
    $club_id = $_POST['Club_ID'];
    $prepared_by = $_POST['preparedBy'];

    // PIC
    $pic_name = $_POST['picName'];
    $pic_id = $_POST['picid'];
    $pic_phone = $_POST['picPhone'];

    // File handling
    $poster_path = null;
    if (isset($_FILES['eventPoster']) && $_FILES['eventPoster']['error'] == 0) {
        $ext = pathinfo($_FILES['eventPoster']['name'], PATHINFO_EXTENSION);
        $poster_path = '../../uploads/posters/' . uniqid("poster_") . "." . $ext;
        move_uploaded_file($_FILES['eventPoster']['tmp_name'], $poster_path);
    }

    $additional_path = null;
    if (isset($_FILES['additionalDocument']) && $_FILES['additionalDocument']['error'] == 0) {
        $ext = pathinfo($_FILES['additionalDocument']['name'], PATHINFO_EXTENSION);
        $additional_path = '../../uploads/additional/' . uniqid("addinfo_") . "." . $ext;
        move_uploaded_file($_FILES['additionalDocument']['tmp_name'], $additional_path);
    }

    // Update event
    $update_sql = "
        UPDATE events SET 
        Ev_Name = ?, Ev_ProjectNature = ?, Ev_Intro = ?, Ev_Details = ?, 
        Ev_Objectives = ?, Ev_Pax = ?, Ev_Date = ?, Ev_StartTime = ?, Ev_EndTime = ?, 
        Ev_VenueID = ?, Ev_AltVenueID = ?, Ev_AlternativeDate = ?, Club_ID = ?, 
        Status_ID = ?
        " . ($poster_path ? ", Ev_Poster = ?" : "") .
        ($additional_path ? ", Ev_AdditionalInfo = ?" : "") .
        " WHERE Ev_ID = ?";

    $params = [
        $ev_name,
        $ev_nature,
        $ev_intro,
        $ev_details,
        $ev_objectives,
        $ev_pax,
        $ev_date,
        $ev_start,
        $ev_end,
        $venue_id,
        $alt_venue,
        $alt_date,
        $club_id,
        $new_status
    ];
    if ($poster_path)
        $params[] = $poster_path;
    if ($additional_path)
        $params[] = $additional_path;
    $params[] = $ev_id;

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();

    // Update PIC
    $stmt = $conn->prepare("UPDATE personincharge SET PIC_Name = ?, PIC_PhnNum = ?, PIC_ID = ? WHERE Ev_ID = ?");
    $stmt->bind_param("ssss", $pic_name, $pic_phone, $pic_id, $ev_id);
    $stmt->execute();



    $existing_committee = [];
    $res = $conn->query("SELECT Com_ID, student_statement FROM committee WHERE Ev_ID = '$ev_id'");
    while ($row = $res->fetch_assoc()) {
        $existing_committee[$row['Com_ID']] = $row['student_statement'];
    }
    $used_committee_ids = [];



    // Delete old rows
    $conn->query("DELETE FROM committee WHERE Ev_ID = '$ev_id'");
    $conn->query("DELETE FROM eventminutes WHERE Ev_ID = '$ev_id'");
    $conn->query("DELETE FROM budget WHERE Ev_ID = '$ev_id'");
    // Reinsert committee
    $com_ids = $_POST['committeeId'];
    $com_names = $_POST['committeeName'];
    $com_positions = $_POST['committeePosition'];
    $com_departments = $_POST['committeeDepartment'];
    $com_phones = $_POST['committeePhone'];
    $com_jobscopes = $_POST['committeeJobScope'];
    $cocu_claimers = $_POST['cocuClaimer'];

    foreach ($com_ids as $i => $com_id) {
        $name = $com_names[$i];
        $pos = $com_positions[$i];
        $dept = $com_departments[$i];
        $phone = $com_phones[$i];
        $jobscope = $com_jobscopes[$i];
        $is_cocu = $cocu_claimers[$i];
        $cocu_pdf_path = null;

        // Try to reuse old file if no new upload
        if ($is_cocu === 'yes') {
            if (isset($_FILES['cocuStatement']['error'][$i]) && $_FILES['cocuStatement']['error'][$i] === 0) {
                $ext = pathinfo($_FILES['cocuStatement']['name'][$i], PATHINFO_EXTENSION);
                $cocu_pdf_path = "../../uploads/cocustatement/{$com_id}_cocu_" . time() . ".{$ext}";
                move_uploaded_file($_FILES['cocuStatement']['tmp_name'][$i], $cocu_pdf_path);
            } else {
                $cocu_pdf_path = $existing_committee[$com_id] ?? null;
            }
        }

        // Insert or update
        $stmt = $conn->prepare("REPLACE INTO committee 
        (Com_ID, Ev_ID, Com_Position, Com_Name, Com_Department, Com_PhnNum, Com_JobScope, Com_COCUClaimers, student_statement) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $com_id, $ev_id, $pos, $name, $dept, $phone, $jobscope, $is_cocu, $cocu_pdf_path);
        $stmt->execute();

        $used_committee_ids[] = $com_id;
    }

    if (!empty($used_committee_ids)) {
        $placeholders = implode(',', array_fill(0, count($used_committee_ids), '?'));
        $types = str_repeat('s', count($used_committee_ids));
        $params = $used_committee_ids;

        $stmt = $conn->prepare("DELETE FROM committee WHERE Ev_ID = ? AND Com_ID NOT IN ($placeholders)");
        $stmt->bind_param("s" . $types, $ev_id, ...$params);
        $stmt->execute();
    }



    // Reinsert event flow
    $flow_dates = $_POST['eventFlowDate'];
    $flow_starts = $_POST['eventFlowStart'];
    $flow_ends = $_POST['eventFlowEnd'];
    $flow_hours = $_POST['eventFlowHours'];
    $flow_activities = $_POST['eventFlowActivity'];
    $flow_remarks = $_POST['eventFlowRemarks'];

    foreach ($flow_dates as $i => $date) {
        $start = $flow_starts[$i];
        $end = $flow_ends[$i];
        $hours = $flow_hours[$i];
        $activity = $flow_activities[$i];
        $remarks = $flow_remarks[$i];

        $stmt = $conn->prepare("INSERT INTO eventminutes (Date, Start_Time, End_Time, Hours, Activity, Remarks, Ev_ID)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsss", $date, $start, $end, $hours, $activity, $remarks, $ev_id);
        $stmt->execute();
    }

    // Reinsert budget
    $bud_descs = $_POST['budgetDescription'];
    $bud_amounts = $_POST['budgetAmount'];
    $bud_types = $_POST['budgetType'];
    $bud_remarks = $_POST['budgetRemarks'];

    foreach ($bud_descs as $i => $desc) {
        $amount = $bud_amounts[$i];
        $type = ucfirst($bud_types[$i]); // Capitalize to match ENUM
        $remarks = $bud_remarks[$i];

        $stmt = $conn->prepare("INSERT INTO budget (Ev_ID, Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $ev_id, $desc, $amount, $type, $remarks);
        $stmt->execute();
    }

    // Update budget summary
    $income = 0;
    $expense = 0;
    foreach ($bud_descs as $i => $desc) {
        $amount = floatval($bud_amounts[$i]);
        $type = strtolower($bud_types[$i]);
        if ($type === "income")
            $income += $amount;
        else if ($type === "expense")
            $expense += $amount;
    }
    $surplus = $income - $expense;

    $stmt = $conn->prepare("UPDATE budgetsummary SET Total_Income = ?, Total_Expense = ?, Surplus_Deficit = ?, Prepared_By = ?, statement = NULL WHERE Ev_ID = ?");
    $stmt->bind_param("dddss", $income, $expense, $surplus, $prepared_by, $ev_id);
    $stmt->execute(); 

    
    if ($mode === 'modify' && $new_status == 3) {
        $stu_id = $_SESSION['Stu_ID'];
        $stmt = $conn->prepare("SELECT Stu_Name FROM student WHERE Stu_ID = ?");
        $stmt->bind_param("s", $stu_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stuRow = $result->fetch_assoc();
        $studentName = $stuRow['Stu_Name'];
        $stmt->close();

        // ✅ Get Club Name based on Club_ID
        $stmt = $conn->prepare("SELECT Club_Name FROM club WHERE Club_ID = ?");
        $stmt->bind_param("i", $club_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $clubRow = $result->fetch_assoc();
        $clubName = $clubRow['Club_Name'];
        $stmt->close();

        // ✅ Hardcoded coordinator info (until your DB structure is updated to include Coor_ID in club)
        $coordinatorName = "Coordinator";
        $coordinatorEmail = "oppoa3s9879@gmail.com"; // from your SQL dump

        modifiedProposalToCoordinator($coordinatorName, $ev_name, $clubName, $studentName, $coordinatorEmail);
    }



    $conn->commit();
    header("Location: ../StudentDashboard.php?updated=1");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    die("Update failed: " . $e->getMessage());
}
?>