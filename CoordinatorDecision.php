<?php
include('dbconfig.php');
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
    $query = "SELECT e.*, s.Stu_Name, c.Club_Name, v.Venue_Name 
              FROM events e
              LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
              LEFT JOIN club c ON e.Club_ID = c.Club_ID
              LEFT JOIN venue v ON e.Ev_Venue = v.Venue_ID
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
} elseif ($type === 'postmortem') {
    $query = "
    SELECT 
        ep.Rep_ID, ep.Rep_ChallengesDifficulties, ep.Rep_Photo, ep.Rep_Receipt, 
        ep.Rep_Conclusion, ep.created_at AS PostmortemDate,
        e.Ev_ID, e.Ev_Name, e.Ev_Poster, e.Ev_ProjectNature, e.Ev_Objectives, 
        e.Ev_Intro, e.Ev_Details, e.Ev_Date, e.Ev_StartTime, e.Ev_EndTime, v.Venue_Name, e.Ev_Pax,
        s.Stu_Name, c.Club_Name
    FROM eventpostmortem ep
    JOIN events e ON ep.Ev_ID = e.Ev_ID
    LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
    LEFT JOIN club c ON e.Club_ID = c.Club_ID
    LEFT JOIN venue v ON e.Ev_Venue = v.Venue_ID
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
    SELECT 
        ir.Rep_ID, ir.Com_ID, ir.IRS_Duties, ir.IRS_Attendance, 
        ir.IRS_Experience, ir.IRS_Challenges, ir.IRS_Benefits, 
        c.Com_Name, c.Com_Position 
    FROM individualreport ir
    JOIN committee c ON ir.Com_ID = c.Com_ID
    WHERE ir.Rep_ID = ?
    ";
    $individual_stmt = $conn->prepare($individual_query);
    $individual_stmt->bind_param("i", $id);
    $individual_stmt->execute();
    $individual_reports = $individual_stmt->get_result();

    $event_flow_query = "SELECT * FROM eventflow WHERE Rep_ID = ?";
    $event_flow_stmt = $conn->prepare($event_flow_query);
    $event_flow_stmt->bind_param("i", $id);
    $event_flow_stmt->execute();
    $event_flows = $event_flow_stmt->get_result();

    $mom_query = "SELECT * FROM meeting WHERE Rep_ID = ?";
    $mom_stmt = $conn->prepare($mom_query);
    $mom_stmt->bind_param("i", $id);
    $mom_stmt->execute();
    $mom_details = $mom_stmt->get_result();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';
    $comments = $_POST['comments'] ?? '';

    if ($type === 'proposal') {

        if ($decision === 'approve') {
            $status = 'Approved by Coordinator';
            $query = "UPDATE events SET Ev_Status = ?, Coor_Comments = NULL WHERE Ev_ID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $status, $id);
        } elseif ($decision === 'reject') {
            if (empty(trim($comments))) {
                die("Feedback is required when rejecting a proposal.");
            }
            $status = 'Rejected by Coordinator';
            $query = "UPDATE events SET Ev_Status = ?, Coor_Comments = ? WHERE Ev_ID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $status, $comments, $id);
        }
    } elseif ($type === 'postmortem') {
        // Handle postmortem logic
        if ($decision === 'approve') {
            $status = 'Accepted';
            $reference_number = "REF-" . str_pad($id, 4, "0", STR_PAD_LEFT);
            $query = "UPDATE eventpostmortem SET Rep_PostStatus = ?, Rep_RefNum = ? WHERE Rep_ID = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $status, $reference_number, $id);
        }
    }

    // Execute the query and check for errors
    if (isset($stmt) && !$stmt->execute()) {
        die("Database Error: " . $stmt->error);
    }

    // Redirect to the dashboard after processing
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
                        value="<?php echo $details['Venue_Name']; ?>" required readonly>
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


                    <?php if (!empty($details['Rep_Receipt'])): ?>
                        <div class="postmortem-header">Receipt</div>
                        <div class="poster-container text-center mb-4 d-flex flex-wrap gap-3 justify-content-center">
                            <?php
                            $receipts = json_decode($details['Rep_Receipt'], true);
                            if (!empty($receipts)) {
                                foreach ($receipts as $receipt) {
                                    echo '<img src="' . htmlspecialchars($receipt) .
                                        '" alt="Receipt" class="img-fluid" style="max-width: 200px; 
                                    max-height: 150px; margin: 10px; border-radius: 5px;">';
                                }
                            } else {
                                echo '<p class="text-muted">No valid receipts uploaded.</p>';
                            }
                            ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No receipt uploaded for this event.</p>
                    <?php endif; ?>

                    <!-- Event Flow -->
                    <div class="postmortem-header">Event Flow</div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($flow = $event_flows->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $flow['Flow_Time']; ?></td>
                                    <td><?php echo $flow['Flow_Description']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Meeting of Minutes Flow -->
                    <div class="postmortem-header">Event Minutes of Meeting</div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Location</th>
                                <th>Discussion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($mom = $mom_details->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $mom['Meeting_Date']; ?></td>
                                    <td><?php echo $mom['Meeting_StartTime']; ?></td>
                                    <td><?php echo $mom['Meeting_EndTime']; ?></td>
                                    <td><?php echo $mom['Meeting_Location']; ?></td>
                                    <td><?php echo $mom['Meeting_Discussion']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <!-- Individual report -->
                    <div class="section-header">Individual Reports</div>
                    <table class="table table-bordered">
                        <thead class="table-section-header">
                            <tr>
                                <th>Committee Name</th>
                                <th>Position</th>
                                <th>Duties</th>
                                <th>Attendance</th>
                                <th>Experience</th>
                                <th>Challenges</th>
                                <th>Benefits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($report = $individual_reports->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['Com_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($report['Com_Position']); ?></td>
                                    <td><?php echo htmlspecialchars($report['IRS_Duties']); ?></td>
                                    <td><?php echo htmlspecialchars($report['IRS_Attendance']); ?></td>
                                    <td><?php echo htmlspecialchars($report['IRS_Experience']); ?></td>
                                    <td><?php echo htmlspecialchars($report['IRS_Challenges']); ?></td>
                                    <td><?php echo htmlspecialchars($report['IRS_Benefits']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>



                <form method="POST" action="">
                    <input type="hidden" name="decision" id="decision" value="approve">
                    <!-- Feedback Modal -->
                    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="feedbackModalLabel">Provide Feedback</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <textarea class="form-control" id="Coor_Comments" name="comments" rows="4"
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
                    <?php if ($type === 'proposal'): ?>
                        <div class="text-center">
                            <button type="submit" name="decision" value="approve" class="btn btn-success">Approve</button>
                            <button type="button" data-bs-toggle="modal" data-bs-target="#feedbackModal"
                                class="btn btn-danger">Reject</button>
                        </div>
                        <div class="text-center mt-4">
                            <a href="CoordinatorDashboard.php" class="btn btn-secondary">Return to Dashboard</a>
                        </div>
                    <?php elseif ($type === 'postmortem'): ?>
                        <div class="text-center">
                            <button type="submit" name="decision" value="approve" class="btn btn-success">Approve</button>
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

            // Set decision to "reject" and include the feedback in the form
            const decisionField = document.getElementById('decision');
            decisionField.value = 'reject';

            // Submit the form
            document.forms[0].submit();
        }

    </script>
    <?php
    // End time after processing the page
    $end_time = microtime(true);
    $page_load_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
    
    echo "<p style='color: green; font-weight: bold; text-align: center;'>
      Page Load Time: " . $page_load_time . " ms
      </p>";
    ?>

</body>

</html>