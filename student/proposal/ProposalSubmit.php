<?php
include('dbconfig.php');
//include('sendMailTemplates.php');
session_start();

$stu_id = $_SESSION['Stu_ID'];
$query = "SELECT MAX(Ev_ID) AS last_id FROM events";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$year_suffix = date('y');

$query = "SELECT MAX(Ev_ID) AS last_id FROM events";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row['last_id']) {
    preg_match('/^(\d+)/', $row['last_id'], $matches);
    $last_num = isset($matches[1]) ? (int) $matches[1] : 0;

    $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
} else {

    $new_num = '01';
}

$event_id = $new_num . '/' . $year_suffix;


$poster = null;

if (!empty($_FILES["poster"]["name"])) {
    $target_dir = "uploads/posters/";
    $target_file = $target_dir . basename($_FILES["poster"]["name"]);

    if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
        $poster = $target_file;
    } else {
        die("File upload failed.");
    }
}

$additional_info_path = null;

if (!empty($_FILES["ev_additional_info"]["name"])) {
    $file_name = $_FILES["ev_additional_info"]["name"];
    $file_tmp = $_FILES["ev_additional_info"]["tmp_name"];
    $target_dir = "uploads/additional/";
    $unique_name = time() . '_' . basename($file_name);
    $target_path = $target_dir . $unique_name;

    if (move_uploaded_file($file_tmp, $target_path)) {
        $additional_info_path = $target_path;
    } else {
        die("Additional info file upload failed.");
    }
}


$stu_id = $_SESSION['Stu_ID'];
$club_id = $_POST['club_id'];
$ev_name = $_POST['ev_name'];
$ev_nature = $_POST['ev_nature'];
$ev_objectives = $_POST['ev_objectives'];
$ev_intro = $_POST['ev_intro'];
$ev_details = $_POST['ev_details'];
$ev_date = $_POST['ev_date'];
$ev_start_time = $_POST['ev_start_time'];
$ev_end_time = $_POST['ev_end_time'];
$ev_pax = $_POST['ev_pax'];
$ev_venue_id = $_POST['ev_venue_id'];
$ev_alt_venue_id = !empty($_POST['ev_alt_venue_id']) ? $_POST['ev_alt_venue_id'] : null;
$ev_alt_date = !empty($_POST['ev_alt_date']) ? $_POST['ev_alt_date'] : null;
$pic_name = $_POST['pic_name'];
$pic_id = $_POST['pic_id'];
$pic_phone = $_POST['pic_phone'];

$stmt = $conn->prepare("INSERT INTO events (
    Ev_ID, Stu_ID, Club_ID, Ev_Name, 
    Ev_ProjectNature, Ev_Objectives, Ev_Intro, Ev_Details, 
    Ev_Date, Ev_StartTime, Ev_EndTime, Ev_Pax, 
    Ev_VenueID, Ev_AltVenueID, Ev_AlternativeDate, Ev_AdditionalInfo, Ev_Poster
) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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


// Set initial status for proposal
$status_id = 1; // Pending Advisor Review
$stmt = $conn->prepare("UPDATE events SET Status_ID = ? WHERE Ev_ID = ?");
$stmt->bind_param("is", $status_id, $event_id);
$stmt->execute();
$stmt->close();



$stmt = $conn->prepare("INSERT INTO PersonInCharge (PIC_ID, Ev_ID, PIC_Name, PIC_PhnNum) 
                                VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $pic_id, $event_id, $pic_name, $pic_phone);
if (!$stmt->execute()) {
    die("Error inserting person in charge: " . $stmt->error);
}
$stmt->close();


$stmt = $conn->prepare("INSERT INTO Committee 
    (Com_ID, Ev_ID, Com_Position, Com_Name, Com_Department, Com_PhnNum, Com_JobScope, Com_COCUClaimers, Student_statement) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$fileIndex = 0; // to track cocu_statement file array

foreach ($_POST['student_name'] as $index => $name) {
    $id = trim($_POST['student_id'][$index]);
    $position = trim($_POST['student_position'][$index]);
    $department = trim($_POST['student_department'][$index]);
    $phone = trim($_POST['student_phone'][$index]);
    $job = trim($_POST['student_job'][$index]);
    $cocu_status = trim($_POST['cocu_claimers'][$index]);

    $cocu_statement_path = null;

    // Only process if cocu = 1 (Yes)
    if ($cocu_status == '1') {
        $cocuupload = $_FILES['cocu_statement']['name'][$fileIndex];
        $tmpPath = $_FILES['cocu_statement']['tmp_name'][$fileIndex];

        if (!empty($cocuupload) && !empty($tmpPath)) {
            $ext = pathinfo($cocuupload, PATHINFO_EXTENSION);
            $uniqueName = $id . '_cocu_' . time() . '.' . $ext;
            $target_dir = "uploads/cocustatement/";
            $target_file = $target_dir . $uniqueName;

            if (move_uploaded_file($tmpPath, $target_file)) {
                $cocu_statement_path = $target_file;
            } else {
                die("File upload failed for COCU statement.");
            }
        }

        $fileIndex++; // only increase when file input was active
    }

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
        die("Error inserting committee data: " . $stmt->error);
    }
}
$stmt->close();

foreach ($_POST['event_date'] as $index => $date) {
    $start_time = htmlspecialchars(trim($_POST['start_time'][$index]));
    $end_time = htmlspecialchars(trim($_POST['end_time'][$index]));
    $hours = htmlspecialchars(trim($_POST['hours'][$index]));
    $activity = htmlspecialchars(trim($_POST['activity'][$index]));
    $remarks = htmlspecialchars(trim($_POST['remarks'][$index]));

    $stmt = $conn->prepare("INSERT INTO eventminutes (Ev_ID, Date, Start_Time, End_Time, Hours, Activity, Remarks) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $event_id, $date, $start_time, $end_time, $hours, $activity, $remarks);
    $stmt->execute();
}

$stmt = $conn->prepare("INSERT INTO Budget (Ev_ID, Bud_Desc, Bud_Amount, Bud_Type, 
                                Bud_Remarks) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

foreach ($_POST['description'] as $index => $desc) {
    $desc = trim($desc);
    $amount = (float) trim($_POST['amount'][$index]);
    $type = trim($_POST['income_expense'][$index]);
    $remarks = trim($_POST['remarks'][$index]);

    $stmt->bind_param("ssdss", $event_id, $desc, $amount, $type, $remarks);
    if (!$stmt->execute()) {
        die("Error inserting budget data: " . $stmt->error);
    }
}
$stmt->close();

// Calculate total income and expense
$total_income = 0;
$total_expense = 0;

foreach ($_POST['description'] as $index => $desc) {
    $amount = (float) trim($_POST['amount'][$index]);
    $type = trim($_POST['income_expense'][$index]);

    if ($type === 'Income') {
        $total_income += $amount;
    } elseif ($type === 'Expense') {
        $total_expense += $amount;
    }
}

$surplus = $total_income - $total_expense;
$prepared_by = $_POST['prepared_by'];

$stmt = $conn->prepare("INSERT INTO BudgetSummary (Ev_ID, Total_Income, Total_Expense, Surplus_Deficit, Prepared_By)
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sddds", $event_id, $total_income, $total_expense, $surplus, $prepared_by);

if (!$stmt->execute()) {
    die("Error inserting into BudgetSummary: " . $stmt->error);
}
$stmt->close();




// Fetch advisor info
$advisorQuery = "
    SELECT a.Adv_Name, a.Adv_Email 
    FROM advisor a
    JOIN club c ON a.Club_ID = c.Club_ID 
    WHERE c.Club_ID = ?
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

    // Send email notification to advisor
    newProposalToAdvisor($studentName, $ev_name, $advisorName, $advisorEmail);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>
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
        <p>Your submission has been sent successfully.</p>
        <p><strong>Event ID: <?php echo $event_id; ?></strong></p>
        <a href="StudentDashboard.php" class="btn btn-return">Return to Dashboard</a>
        <a href="generate_pdf.php?id=<?php echo $event_id; ?>" class="btn btn-primary">Export to PDF</a>
        <p class="mt-4 text-muted">
            <small>
                <a href="https://www.flaticon.com/free-icons/success" title="success icons"></a>
            </small>
        </p>
    </div>
</body>

</html>