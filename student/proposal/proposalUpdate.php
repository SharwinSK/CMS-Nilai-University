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
    $proposal_position = $_POST['Proposal_Position'];
    $ev_pax = $_POST['Ev_Pax'];
    $ev_date = $_POST['Ev_Date'];
    $ev_start = $_POST['Ev_StartTime'];
    $ev_end = $_POST['Ev_EndTime'];
    $venue_id = $_POST['Ev_VenueID'];
    $alt_venue = $_POST['altVenue'];
    $alt_date = $_POST['Ev_AlternativeDate'];
    $club_id = $_POST['Club_ID'];
    $ev_category = $_POST['Ev_Category'];
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
    Proposal_Position = ?, Ev_Category = ?, Status_ID = ?";

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
        $proposal_position,
        $ev_category,
        $new_status
    ];

    // Add poster and additional document fields if they exist
    if ($poster_path) {
        $update_sql .= ", Ev_Poster = ?";
        $params[] = $poster_path;
    }

    if ($additional_path) {
        $update_sql .= ", Ev_AdditionalInfo = ?";
        $params[] = $additional_path;
    }

    // Add WHERE clause
    $update_sql .= " WHERE Ev_ID = ?";
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
    $com_emails = $_POST['committeeEmail'];  // Add this line
    $com_positions = $_POST['committeePosition'];
    $com_departments = $_POST['committeeDepartment'];
    $com_phones = $_POST['committeePhone'];
    $com_jobscopes = $_POST['committeeJobScope'];
    $cocu_claimers = $_POST['cocuClaimer'];
    $com_registers = $_POST['committeeRegister'];  // Add this line

    foreach ($com_ids as $i => $com_id) {
        $name = $com_names[$i];
        $email = $com_emails[$i];
        $pos = $com_positions[$i];
        $dept = $com_departments[$i];
        $phone = $com_phones[$i];
        $jobscope = $com_jobscopes[$i];
        $is_cocu = $cocu_claimers[$i];
        $register = $com_registers[$i];

        // Initialize COCU PDF path
        $cocu_pdf_path = null;

        // Check if this committee member has existing COCU statement
        if (isset($existing_committee[$com_id])) {
            $cocu_pdf_path = $existing_committee[$com_id]; // Keep existing file
        }

        // Handle COCU statement file upload
        if (
            $is_cocu === 'yes' && isset($_FILES['cocuStatement']['name'][$i]) &&
            !empty($_FILES['cocuStatement']['name'][$i]) &&
            $_FILES['cocuStatement']['error'][$i] == 0
        ) {

            $file_tmp = $_FILES['cocuStatement']['tmp_name'][$i];
            $file_name = $_FILES['cocuStatement']['name'][$i];
            $file_size = $_FILES['cocuStatement']['size'][$i];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file
            if ($file_ext === 'pdf' && $file_size <= 2 * 1024 * 1024) { // 2MB limit
                // Create unique filename
                $new_filename = uniqid("cocu_statement_") . "_" . $com_id . ".pdf";
                $upload_path = '../../uploads/cocu_statements/' . $new_filename;

                // Create directory if it doesn't exist
                if (!file_exists('../../uploads/cocu_statements/')) {
                    mkdir('../../uploads/cocu_statements/', 0777, true);
                }

                // Move uploaded file
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $cocu_pdf_path = $upload_path;

                    // Delete old file if exists and different
                    if (
                        isset($existing_committee[$com_id]) &&
                        $existing_committee[$com_id] &&
                        $existing_committee[$com_id] !== $upload_path &&
                        file_exists($existing_committee[$com_id])
                    ) {
                        unlink($existing_committee[$com_id]);
                    }
                }
            }
        }

        // If COCU claimer is 'no', remove any existing file
        if (
            $is_cocu === 'no' && isset($existing_committee[$com_id]) &&
            $existing_committee[$com_id] && file_exists($existing_committee[$com_id])
        ) {
            unlink($existing_committee[$com_id]);
            $cocu_pdf_path = null;
        }

        // Insert/Update committee record
        $stmt = $conn->prepare("REPLACE INTO committee 
        (Com_ID, Ev_ID, Com_Position, Com_Name, Com_Email, Com_Department, Com_PhnNum, Com_JobScope, Com_COCUClaimers, Com_Register, student_statement) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $com_id, $ev_id, $pos, $name, $email, $dept, $phone, $jobscope, $is_cocu, $register, $cocu_pdf_path);
        $stmt->execute();

        $used_committee_ids[] = $com_id;
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