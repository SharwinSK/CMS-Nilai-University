<?php
include('dbconfig.php');
include('sendMailTemplates.php');

session_start();
if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    die("Type and ID are required.");
}

$type = $_GET['type'];
$id = $_GET['id'];

if ($type === 'proposal') {
    $query = "SELECT e.*, s.Stu_Name, c.Club_Name, bs.Total_Income, bs.Total_Expense, bs.Surplus_Deficit, bs.Prepared_By 
              FROM events e
              LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
              LEFT JOIN club c ON e.Club_ID = c.Club_ID
              LEFT JOIN budgetsummary bs ON e.Ev_ID = bs.Ev_ID
              WHERE e.Ev_ID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();

    if (!$details) {
        die("Proposal not found.");
    }

    $pic_query = "SELECT * FROM personincharge WHERE Ev_ID = ?";
    $pic_stmt = $conn->prepare($pic_query);
    $pic_stmt->bind_param("i", $id);
    $pic_stmt->execute();
    $person_in_charge = $pic_stmt->get_result()->fetch_assoc();

    $committee_query = "SELECT * FROM committee WHERE Ev_ID = ?";
    $committee_stmt = $conn->prepare($committee_query);
    $committee_stmt->bind_param("i", $id);
    $committee_stmt->execute();
    $committee_members = $committee_stmt->get_result();

    $budget_query = "SELECT * FROM budget WHERE Ev_ID = ?";
    $budget_stmt = $conn->prepare($budget_query);
    $budget_stmt->bind_param("i", $id);
    $budget_stmt->execute();
    $budget_details = $budget_stmt->get_result();

    $event_flow_query = "SELECT * FROM eventflow WHERE Ev_ID = ?";
    $event_flow_stmt = $conn->prepare($event_flow_query);
    $event_flow_stmt->bind_param("i", $id);
    $event_flow_stmt->execute();
    $event_flows = $event_flow_stmt->get_result();

} elseif ($type === 'postmortem') {
    $query = "
    SELECT 
        ep.Rep_ID, ep.Rep_ChallengesDifficulties, ep.Rep_Photo, 
        ep.Rep_Conclusion, ep.created_at AS PostmortemDate,
        e.Ev_ID, e.Ev_Name, e.Ev_Poster, e.Ev_ProjectNature, e.Ev_Objectives, 
        e.Ev_Intro, e.Ev_Details, e.Ev_Date, e.Ev_StartTime, e.Ev_EndTime, e.Ev_Venue, e.Ev_Pax,
        s.Stu_Name, c.Club_Name
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    WHERE ep.Rep_ID = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc();

    if (!$details) {
        die("Postmortem report not found.");
    }
    $individual_query = "
    SELECT ir.Rep_ID, ir.Com_ID, ir.IR_File, 
           c.Com_Name, c.Com_Position,c.Com_COCUClaimers
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE ir.Rep_ID = ? 
    AND c.Ev_ID = (SELECT Ev_ID FROM eventpostmortem WHERE Rep_ID = ?)
";

    $individual_stmt = $conn->prepare($individual_query);
    $individual_stmt->bind_param("ii", $id, $id);
    $individual_stmt->execute();
    $individual_reports = $individual_stmt->get_result();

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';
    $comments = $_POST['comments'] ?? '';

    // Fetch Student + Advisor email info BEFORE decisions
    $emailFetch = $conn->prepare("
        SELECT s.Stu_Name, s.Stu_Email, a.Adv_Email AS AdvisorEmail, e.Ev_Name 
        FROM events e
        LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
        LEFT JOIN advisor a ON e.Club_ID = a.Club_ID
        WHERE e.Ev_ID = ?
    ");
    $emailFetch->bind_param("i", $id);
    $emailFetch->execute();
    $emailRow = $emailFetch->get_result()->fetch_assoc();

    $studentName = $emailRow['Stu_Name'];
    $studentEmail = $emailRow['Stu_Email'];
    $advisorEmail = $emailRow['AdvisorEmail'];
    $eventName = $emailRow['Ev_Name'];


    if ($type === 'proposal') {
        if ($decision === 'approve') {
            $event_type = $_POST['ev_type'] ?? '';

            if (empty($event_type)) {
                die("Please select an event type.");
            }

            // Step 1: Get current year
            $year = date('Y');
            $year_suffix = substr($year, -2); // '25' for 2025

            // Step 2: Get the latest type-based number from eventtyperef
            $query = "SELECT Last_Number FROM eventtyperef WHERE Type_Code = ? AND Year = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $event_type, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $next_type_number = ($row['Last_Number'] ?? 0) + 1;

            // Step 3: Update or insert into eventtyperef
            if ($row) {
                $update_ref = "UPDATE eventtyperef SET Last_Number = ? WHERE Type_Code = ? AND Year = ?";
                $update_stmt = $conn->prepare($update_ref);
                $update_stmt->bind_param("iss", $next_type_number, $event_type, $year);
                $update_stmt->execute();
            } else {
                $insert_ref = "INSERT INTO eventtyperef (Type_Code, Year, Last_Number) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_ref);
                $insert_stmt->bind_param("ssi", $event_type, $year, $next_type_number);
                $insert_stmt->execute();
            }

            // Step 4: Generate Ev_RefNum using max-based logic (like Event ID)
            $year_suffix = substr($year, -2); // Already declared

            $query = "SELECT Ev_RefNum FROM events 
          WHERE Ev_RefNum LIKE '%/$year_suffix' 
          ORDER BY Ev_RefNum DESC 
          LIMIT 1";
            $result = $conn->query($query);
            $row = $result->fetch_assoc();

            if ($row && preg_match('/^(\d{2})\/\d{2}$/', $row['Ev_RefNum'], $matches)) {
                $last_num = (int) $matches[1];
                $new_num = str_pad($last_num + 1, 2, '0', STR_PAD_LEFT);
            } else {
                $new_num = '01';
            }

            $Ev_RefNum = $new_num . '/' . $year_suffix; // e.g., 01/25, 02/25
            $Ev_TypeRef = $event_type . ' ' . str_pad($next_type_number, 2, '0', STR_PAD_LEFT) . '/' . $year_suffix;

            // Step 5: Update events table
            $update_event = "UPDATE events 
            SET Ev_TypeCode = ?, Ev_TypeRef = ?, Ev_RefNum = ? 
            WHERE Ev_ID = ?";
            $update_stmt = $conn->prepare($update_event);
            $update_stmt->bind_param("ssss", $event_type, $Ev_TypeRef, $Ev_RefNum, $id);
            $update_stmt->execute();

            // Step 6: Add eventcomment for approval
            $status_id = 5; // Approved by Coordinator

            $update_event = "UPDATE events 
        SET Ev_TypeCode = ?, Ev_TypeRef = ?, Ev_RefNum = ?, Status_ID = ?
        WHERE Ev_ID = ?";
            $update_stmt = $conn->prepare($update_event);
            $update_stmt->bind_param("sssis", $event_type, $Ev_TypeRef, $Ev_RefNum, $status_id, $id);
            $update_stmt->execute();

            // ✉️ Notify both student & advisor
            coordinatorApproved($studentName, $eventName, $studentEmail, $advisorEmail);


        } elseif ($decision === 'reject') {
            if (empty(trim($comments))) {
                die("Feedback is required when rejecting a proposal.");
            } elseif ($decision === 'reject') {
                if (empty(trim($comments))) {
                    die("Feedback is required when rejecting a proposal.");
                }

                $status_id = 4; // Rejected by Coordinator

                // Insert comment into eventcomment
                $comment_query = "INSERT INTO eventcomment (Ev_ID, Status_ID, Reviewer_Comment, Updated_By)
                      VALUES (?, ?, ?, 'Coordinator')";
                $comment_stmt = $conn->prepare($comment_query);
                $comment_stmt->bind_param("sis", $id, $status_id, $comments);
                $comment_stmt->execute();

                // Also update event status
                $update_event = "UPDATE events SET Status_ID = ? WHERE Ev_ID = ?";
                $update_stmt = $conn->prepare($update_event);
                $update_stmt->bind_param("is", $status_id, $id);
                $update_stmt->execute();

                // ✉️ Notify student only
                coordinatorRejected($studentName, $eventName, $studentEmail);
            }

        }
    } elseif ($type === 'postmortem' && $decision === 'approve') {
        $status = 'Accepted';
        $query = "UPDATE eventpostmortem SET Rep_PostStatus = ? WHERE Rep_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
    } else {
        die("Invalid decision or type.");
    }

    if (isset($stmt) && !$stmt->execute()) {
        die("Database Error: " . $stmt->error);
    }

    header("Location: CoordinatorDashboard.php");
    exit();
}

$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<style>
    .card-header {
        background-color: #15B392;
        color: white;
        text-align: center;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .table th,
    .table td,
    .table tr {
        text-align: center;
        padding: 10px;
        background-color: #D2FF72;
        border-color: black;
    }

    .table th {
        background-color: #54C392;
        color: white;
        font-weight: bold;
    }

    .btn-success {
        background-color: #32CD32;

        border: none;
        color: white;
    }

    .btn-success:hover {
        background-color: #15B392;
        transform: scale(1.05);
    }

    .btn-danger {
        background-color: rgb(255, 7, 90);
        border: none;
        color: white;
    }

    .btn-danger:hover {
        background-color: rgb(255, 2, 2);
        transform: scale(1.05);
    }

    .btn-secondary {
        background-color: rgb(50, 205, 148);
        border: none;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #32CD32;
        transform: scale(1.05);
    }

    .poster-container img {
        max-width: 100%;
        max-height: 300px;
        margin-bottom: 20px;
        border-radius: 5px;
    }


    .section-header {
        background-color: #73EC8B;
        color: #333;
        font-size: 1.25rem;
        font-weight: bold;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .postmortem-header {
        background-color: #73EC8B;
        color: #333;
        font-size: 1.25rem;
        font-weight: bold;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background-color: #15B392;
        color: white;
        border-bottom: 1px solid #ccc;
    }

    .modal-footer .btn {
        padding: 10px 15px;
        border-radius: 5px;
    }

    .modal-footer .btn-primary {
        background-color: #15B392;
        color: white;
        border: none;
    }

    .modal-footer .btn-primary:hover {
        background-color: #0E8669;
    }

    .modal-footer .btn-secondary {
        background-color: rgb(255, 7, 90);
        color: white;
        border: none;
    }

    .modal-footer .btn-secondary:hover {
        background-color: rgb(255, 2, 2);
    }

    .modal-lg {
        max-width: 80%;
    }

    .modal-body textarea {
        height: 200px;
    }
</style>

<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header text-center">
                <h2><?php echo ucfirst($type); ?> Review</h2>
            </div>
            <div class="card-body">
                <!-- Shared Details -->
                <div class="poster-container text-center mb-4">
                    <?php if (!empty($details['Ev_Poster'])): ?>
                        <img src="<?php echo $details['Ev_Poster']; ?>" alt="Event Poster" class="img-fluid">
                    <?php else: ?>
                        <p class="text-muted">No poster uploaded for this event.</p>
                    <?php endif; ?>
                </div>
                <div class="section-header">Event Details</div>
                <p><strong>Event ID:</strong> <?php echo $details['Ev_ID']; ?></p>
                <p><strong>Student Name:</strong> <?php echo $details['Stu_Name']; ?></p>
                <p><strong>Club Name:</strong> <?php echo $details['Club_Name']; ?></p>
                <div class="mb-3">
                    <label for="ev_name" class="form-label">Event Name</label>
                    <input type="text" class="form-control" id="ev_name" name="ev_name"
                        value="<?php echo $details['Ev_Name']; ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="ev_nature" class="form-label">Event Nature</label>
                    <input type="text" class="form-control" id="ev_nature" name="ev_nature"
                        value="<?php echo $details['Ev_ProjectNature']; ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="ev_objectives" class="form-label">Event Objectives</label>
                    <textarea class="form-control" id="ev_objectives" name="ev_objectives" rows="3" required
                        readonly><?php echo $details['Ev_Objectives']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="ev_intro" class="form-label">Event Introduction</label>
                    <textarea class="form-control" id="ev_intro" name="ev_intro" rows="3" required
                        readonly><?php echo $details['Ev_Intro']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="ev_details" class="form-label">Event Details</label>
                    <textarea class="form-control" id="ev_details" name="ev_details" rows="5" required
                        readonly><?php echo $details['Ev_Details']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="ev_pax" class="form-label">Event Participants</label>
                    <input type="number" class="form-control" id="ev_pax" name="ev_pax"
                        value="<?php echo $details['Ev_Pax']; ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="ev_venue" class="form-label">Event Venue</label>
                    <input type="text" class="form-control" id="ev_venue" name="ev_venue"
                        value="<?php echo $details['Ev_Venue']; ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="ev_date" class="form-label">Event Date</label>
                    <input type="date" class="form-control" id="ev_date" name="ev_date"
                        value="<?php echo $details['Ev_Date']; ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="ev_start_time" class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="ev_start_time" name="ev_start_time"
                        value="<?php echo $details['Ev_StartTime']; ?>" required readonly>
                </div>
                <div class="mb-3">
                    <label for="ev_end_time" class="form-label">End Time</label>
                    <input type="time" class="form-control" id="ev_end_time" name="ev_end_time"
                        value="<?php echo $details['Ev_EndTime']; ?>" required readonly>
                </div>


                <!-- Proposal-Specific Details -->
                <?php if ($type === 'proposal'): ?>
                    <!-- Person in Charge -->
                    <div class="section-header">Person in Charge</div>
                    <div class="mb-3">
                        <label for="pic_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="pic_name" name="pic_name"
                            value="<?php echo $person_in_charge['PIC_Name']; ?>" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="pic_id" class="form-label">ID</label>
                        <input type="text" class="form-control" id="pic_id" name="pic_id"
                            value="<?php echo $person_in_charge['PIC_ID']; ?>" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="pic_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="pic_phone" name="pic_phone"
                            value="<?php echo $person_in_charge['PIC_PhnNum']; ?>" required readonly>
                    </div>

                    <!-- Event Flow / Minutes of Meeting -->
                    <div class="postmortem-header">Event Flow / Minutes of Meeting</div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th>Activity</th>
                                <th>Remarks / Meeting Minutes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($flow = $event_flows->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date("d-m-Y", strtotime($flow['Date'])); ?></td>
                                    <td><?php echo date("H:i A", strtotime($flow['Start_Time'])); ?></td>
                                    <td><?php echo date("H:i A", strtotime($flow['End_Time'])); ?></td>
                                    <td><?php echo htmlspecialchars($flow['Hours']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($flow['Activity'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($flow['Remarks'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>


                    <!-- Budget -->
                    <div class="section-header">Budget</div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Income/Expense</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($budget = $budget_details->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $budget['Bud_Desc']; ?></td>
                                    <td><?php echo $budget['Bud_Amount']; ?></td>
                                    <td><?php echo $budget['Bud_Type']; ?></td>
                                    <td><?php echo $budget['Bud_Remarks']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Income</strong></td>
                                <td><?php echo $details['Total_Income']; ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Expense</strong></td>
                                <td><?php echo $details['Total_Expense']; ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Surplus/Deficit</strong></td>
                                <td><?php echo $details['Surplus_Deficit']; ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Prepared By</strong></td>
                                <td><?php echo $details['Prepared_By']; ?></td>
                        </tbody>
                    </table>

                    <!-- Commitee Member -->

                    <div class="section-header">Committee Members</div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Phone</th>
                                <th>Job Scope</th>
                                <th>Cocu Claimers</th>
                                <th>COCU Statement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $committee_members->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $member['Com_Name']; ?></td>
                                    <td><?php echo $member['Com_Position']; ?></td>
                                    <td><?php echo $member['Com_Department']; ?></td>
                                    <td><?php echo $member['Com_PhnNum']; ?></td>
                                    <td><?php echo $member['Com_JobScope']; ?></td>
                                    <td><?php echo ($member['Com_COCUClaimers'] == '1') ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <?php if (!empty($member['student_statement'])): ?>
                                            <a href="viewpdf.php?file=<?php echo urlencode($member['student_statement']); ?>"
                                                target="_blank" class="btn btn-sm btn-primary">View</a>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Postmortem-Specific Details -->
                <?php elseif ($type === 'postmortem'): ?>

                    <div class="mb-3">
                        <label for="ev_ChallengesDifficulties" class="form-label">Challenges and Difficulties</label>
                        <textarea class="form-control" id="ev_Challenges and Difficulties" name="ev_ChallengesDifficulties"
                            rows="3" required readonly><?php echo $details['Rep_ChallengesDifficulties']; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="ev_Conclusion" class="form-label">Conclusion</label>
                        <textarea class="form-control" id="ev_Conclusion" name="ev_Conclusion" rows="3" required
                            readonly><?php echo $details['Rep_Conclusion']; ?></textarea>
                    </div>
                    <?php if (!empty($details['Rep_Photo'])): ?>
                        <div class="postmortem-header">Event Photo</div>
                        <div class="poster-container text-center mb-4 d-flex flex-wrap gap-3 justify-content-center">
                            <?php
                            $photos = json_decode($details['Rep_Photo'], true);
                            if (!empty($photos)) {
                                foreach ($photos as $photo) {
                                    echo '<img src="' . htmlspecialchars($photo) .
                                        '" alt="Event Photo" class="img-fluid" style="max-width: 
                                    200px; max-height: 150px; margin: 10px; border-radius: 5px;">';
                                }
                            } else {
                                echo '<p class="text-muted">No valid event photos uploaded.</p>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No event photos uploaded for this event.</p>
                    <?php endif; ?>

                    <!-- Individual report -->
                    <div class="section-header">Individual Report For Cocu Claimers</div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Committee Name</th>
                                <th>Committee ID</th>
                                <th>Position</th>
                                <th>Report File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Re-run result to show files
                            $stmt = $conn->prepare($individual_query);
                            $stmt->bind_param("ii", $id, $id);
                            $stmt->execute();
                            $files_result = $stmt->get_result();

                            while ($row = $files_result->fetch_assoc()):
                                if ($row['Com_COCUClaimers'] == 1):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['Com_Name']); ?></td>
                                        <td><?= htmlspecialchars($row['Com_ID']); ?></td>
                                        <td><?= htmlspecialchars($row['Com_Position']); ?></td>
                                        <td>
                                            <?php if (!empty($row['IR_File'])): ?>
                                                <a href="viewpdf.php?file=<?= urlencode($row['IR_File']); ?>" target="_blank"
                                                    class="btn btn-sm btn-primary">View</a>
                                            <?php else: ?>
                                                <span class="text-muted">No file</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; endwhile; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="decision" id="decision" value="approve">
                    <!-- Feedback Modal -->
                    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="feedbackModalLabel">Provide Feedback</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <textarea class="form-control" id="Coor_Comments" name="comments" rows="6"
                                        placeholder="Enter your feedback"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" onclick="submitFeedback()">Submit
                                        Feedback</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <?php if ($type === 'proposal'): ?>
                            <div class="mb-3">
                                <label for="ev_type" class="form-label">Select Event Type</label>
                                <select class="form-control" id="ev_type" name="ev_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="USR">USR</option>
                                    <option value="SDG">SDG</option>
                                    <option value="CSR">CSR</option>
                                </select>
                            </div>

                            <div class="text-center">
                                <button type="submit" name="decision" value="approve"
                                    class="btn btn-success">Approve</button>
                                <button type="button" data-bs-toggle="modal" data-bs-target="#feedbackModal"
                                    class="btn btn-danger">Reject</button>
                                <a href="generate_pdf.php?id=<?php echo $id; ?>" class="btn btn-primary">Export to PDF</a>
                            </div>
                            <div class="text-center mt-4">
                                <a href="CoordinatorDashboard.php" class="btn btn-secondary">Return to Dashboard</a>
                            </div>
                        <?php elseif ($type === 'postmortem'): ?>
                            <div class="text-center">
                                <button type="submit" name="decision" value="approve"
                                    class="btn btn-success">Approve</button>
                                <a href="reportgeneratepdf.php?id=<?php echo $id; ?>" class="btn btn-primary">Export to
                                    PDF</a>
                                <a href="CoordinatorDashboard.php" class="btn btn-secondary">Return to Dashboard</a>
                            </div>
                        <?php endif; ?>
                    </form>

            </div>
        </div>
    </div>


    <script>
        function submitFeedback() {
            const feedback = document.getElementById('Coor_Comments').value.trim();

            if (!feedback) {
                alert('Please provide feedback before rejecting the proposal.');
                return;
            }

            const decisionField = document.getElementById('decision');
            decisionField.value = 'reject';

            document.forms[0].submit();
        }

    </script>
    <?php
    $end_time = microtime(true);
    $page_load_time = round(($end_time - $start_time) * 1000, 2);

    echo "<p style='color: green; font-weight: bold; text-align: center;'>
      Page Load Time: " . $page_load_time . " ms
      </p>";
    ?>

</body>

</html>