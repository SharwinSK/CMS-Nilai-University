<?php
include('dbconfig.php');
session_start();

if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}

$advisor_id = $_SESSION['Adv_ID'];


if (!isset($_GET['event_id'])) {
    die("Event ID is required.");
}
$event_id = $_GET['event_id'];

$query = "SELECT e.*, s.Stu_Name, c.Club_Name
          FROM events e
          LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
          LEFT JOIN club c ON e.Club_ID = c.Club_ID
         
          WHERE e.Ev_ID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    die("Proposal not found.");
}

$pic_query = "SELECT * FROM personincharge WHERE Ev_ID = ?";
$pic_stmt = $conn->prepare($pic_query);
$pic_stmt->bind_param("i", $event_id);
$pic_stmt->execute();
$person_in_charge = $pic_stmt->get_result()->fetch_assoc();

$committee_query = "SELECT * FROM committee WHERE Ev_ID = ?";
$committee_stmt = $conn->prepare($committee_query);
$committee_stmt->bind_param("i", $event_id);
$committee_stmt->execute();
$committee_members = $committee_stmt->get_result();

$budget_query = "SELECT * FROM budget WHERE Ev_ID = ?";
$budget_stmt = $conn->prepare($budget_query);
$budget_stmt->bind_param("i", $event_id);
$budget_stmt->execute();
$budget_details = $budget_stmt->get_result();


$event_flow_query = "SELECT * FROM eventflow WHERE Ev_ID = ?";
$event_flow_stmt = $conn->prepare($event_flow_query);
$event_flow_stmt->bind_param("i", $event_id);
$event_flow_stmt->execute();
$event_flows = $event_flow_stmt->get_result();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'];
    $advisor_feedback = isset($_POST['Ev_AdvisorComments']) ? $_POST['Ev_AdvisorComments'] : null;

    if ($decision === 'send_back') {
        $update_query = "UPDATE events SET Ev_Status = 'Sent Back by Advisor', Ev_AdvisorComments = ? WHERE Ev_ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $advisor_feedback, $event_id);

        if ($stmt->execute()) {
        } else {
            die("Error updating feedback: " . $stmt->error);
        }
    } elseif ($decision === 'approve') {
        $update_query = "UPDATE events SET Ev_Status = 'Approved by Advisor' WHERE Ev_ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $event_id);

        if ($stmt->execute()) {
        } else {
            die("Error approving event: " . $stmt->error);
        }
    }

    header("Location: AdvisorDashboard.php");
    exit();
}
$start_time = microtime(true);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Review Proposal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .readonly {
            background-color: #f8f9fa !important;
            pointer-events: none;
            opacity: 1;
        }

        /* Card Header */
        .card-header {
            background-color: #15B392;
            /* Green */
            color: white;
            text-align: center;
            font-weight: bold;
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

        .poster-container img {
            max-width: 100%;
            max-height: 300px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .table-bordered th {
            background-color: #54C392;
            color: black;
            text-align: center;
            border-color: black;

        }

        .table-bordered td,
        .table-bordered tr {
            background-color: #D2FF72;
            padding: 10px;
            text-align: center;
            border-color: black;
            /* Light gray border */
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
            /* Light hover effect */
        }

        .btn-success {
            background-color: #32CD32;
            /* Green */
            color: white;
            border-radius: 5px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-success:hover {
            background-color: #15B392;
            /* Darker green */
            transform: scale(1.05);
            /* Slight zoom effect */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            /* Add shadow */
        }

        .btn-warning {
            background-color: rgb(255, 7, 90);
            /* Yellow */
            color: white;
            border-radius: 5px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-warning:hover {
            background-color: rgb(255, 2, 2);
            /* Darker yellow */
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
</head>

<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header text-center">
                <h2>Review Event Proposal</h2>
            </div>
            <div class="card-body">

                <!-- Event Poster Section -->
                <div class="poster-container text-center mb-4">
                    <?php if (!empty($proposal['Ev_Poster'])): ?>
                        <img src="<?php echo $proposal['Ev_Poster']; ?>" alt="Event Poster" class="img-fluid">
                    <?php else: ?>
                        <p class="text-muted">No poster uploaded for this event.</p>
                    <?php endif; ?>
                </div>

                <!-- Event Information Section -->
                <h5 class="section-header">Event Information</h5>
                <div class="mb-3">
                    <label for="studentName" class="form-label">Student Name</label>
                    <textarea class="form-control readonly" id="studentName"
                        readonly><?php echo $proposal['Stu_Name']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="clubName" class="form-label">Club Name</label>
                    <textarea class="form-control readonly" id="clubName"
                        readonly><?php echo $proposal['Club_Name']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventName" class="form-label">Event Name</label>
                    <textarea class="form-control readonly" id="eventName"
                        readonly><?php echo $proposal['Ev_Name']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventNature" class="form-label">Event Nature</label>
                    <textarea class="form-control readonly" id="eventNature"
                        readonly><?php echo $proposal['Ev_ProjectNature']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventObjectives" class="form-label">Event Objectives</label>
                    <textarea class="form-control readonly" id="eventObjectives" rows="3"
                        readonly><?php echo $proposal['Ev_Objectives']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventIntro" class="form-label">Event Introduction</label>
                    <textarea class="form-control readonly" id="eventIntro" rows="3"
                        readonly><?php echo $proposal['Ev_Intro']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventDetails" class="form-label">Event Details</label>
                    <textarea class="form-control readonly" id="eventDetails" rows="4"
                        readonly><?php echo $proposal['Ev_Details']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventDate" class="form-label">Event Date</label>
                    <textarea class="form-control readonly" id="eventDate"
                        readonly><?php echo $proposal['Ev_Date']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="submissionDate" class="form-label">Submission Date</label>
                    <textarea class="form-control readonly" id="submissionDate"
                        readonly><?php echo $proposal['created_at']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventpax" class="form-label">Event Participant</label>
                    <textarea class="form-control readonly" id="eventpax"
                        readonly><?php echo $proposal['Ev_Pax']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventvenue" class="form-label">Event Venue</label>
                    <textarea class="form-control readonly" id="eventvenue"
                        readonly><?php echo $proposal['Ev_Venue']; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="eventstarttime" class="form-label">Event Start Time</label>
                    <textarea class="form-control readonly" id="eventstarttime"
                        readonly><?php echo $proposal['Ev_StartTime']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="eventendtime" class="form-label">Event End Time</label>
                    <textarea class="form-control readonly" id="eventendtime"
                        readonly><?php echo $proposal['Ev_EndTime']; ?></textarea>
                </div>

                <!-- Person in Charge Section -->
                <h5 class="section-header">Person in Charge</h5>
                <div class="mb-3">
                    <label for="picName" class="form-label">Name</label>
                    <textarea class="form-control readonly" id="picName"
                        readonly><?php echo $person_in_charge['PIC_Name']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="picId" class="form-label">ID</label>
                    <textarea class="form-control readonly" id="picId"
                        readonly><?php echo $person_in_charge['PIC_ID']; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="picPhone" class="form-label">Phone</label>
                    <textarea class="form-control readonly" id="picPhone"
                        readonly><?php echo $person_in_charge['PIC_PhnNum']; ?></textarea>
                </div>
                <!-- Event Flow / Minutes of Meeting -->
                <div class="section-header">Event Flow / Minutes of Meeting</div>
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
                                <td><?php echo date("d/m/Y", strtotime($flow['Date'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($flow['Start_Time'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($flow['End_Time'])); ?></td>
                                <td><?php echo htmlspecialchars($flow['Hours']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($flow['Activity'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($flow['Remarks'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>


                <!-- Committee Section -->
                <h5 class="section-header">Committee Members</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Job Scope</th>
                            <th>COCU Claimers</th>
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

                <!-- Budget Section -->
                <h5 class="section-header">Budget Details</h5>
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

                <form method="POST">
                    <input type="hidden" name="decision" id="decision" value="">
                    <div class="text-center">
                        <button type="button" onclick="setDecision('approve')" class="btn btn-success">Approve</button>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#feedbackModal"
                            class="btn btn-warning">Send Back</button>
                        <div class="text-center mt-4">
                            <a href="AdvisorDashboard.php" class="btn btn-secondary">Return to Dashboard</a>
                        </div>
                    </div>

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
                                    <textarea class="form-control" id="Ev_AdvisorComments" name="Ev_AdvisorComments"
                                        rows="4" placeholder="Enter your feedback"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary"
                                        onclick="setDecision('send_back')">Submit Feedback</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <script>
                    function setDecision(decision) {
                        document.getElementById('decision').value = decision;
                        const feedback = document.getElementById('Ev_AdvisorComments').value.trim();

                        if (decision === 'send_back' && !feedback) {
                            alert('Please provide feedback before sending back.');
                            return;
                        }

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