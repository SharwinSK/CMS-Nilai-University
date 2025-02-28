<?php
session_start();
if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}

include('dbconfig.php');

if (!isset($_GET['event_id'])) {
    die("Event ID is required to create a postmortem report.");
}

$event_id = $_GET['event_id'];
$stmt = $conn->prepare("SELECT e.Ev_Name, e.Ev_ProjectNature, e.Ev_Objectives, s.Stu_Name, c.Club_Name
                               FROM events e LEFT JOIN Student s ON e.Stu_ID = s.Stu_ID
                               LEFT JOIN Club c ON e.Club_ID = c.Club_ID
                               WHERE e.Ev_ID = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    die("Event not found or unauthorized access.");
}

$committee_query = "SELECT Com_ID, Com_Name, Com_Position FROM Committee WHERE Ev_ID = ? AND Com_COCUClaimers = 1";
$committee_stmt = $conn->prepare($committee_query);
$committee_stmt->bind_param("i", $event_id);
$committee_stmt->execute();
$committee_result = $committee_stmt->get_result();

$mom_query = "SELECT * FROM meeting WHERE Ev_ID = ?";
$mom_stmt = $conn->prepare($mom_query);
$mom_stmt->bind_param("i", $event_id);
$mom_stmt->execute();
$mom_details = $mom_stmt->get_result();

$event_flow_query = "SELECT * FROM eventflow WHERE Ev_ID = ?";
$event_flow_stmt = $conn->prepare($event_flow_query);
$event_flow_stmt->bind_param("i", $event_id);
$event_flow_stmt->execute();
$event_flows = $event_flow_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #15B392;
            color: white;
            text-align: center;
        }

        .btn-success {
            background-color: #54C392;
            border-color: #54C392;
        }

        .btn-success:hover {
            background-color: #06D001;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .btn-secondary {
            background-color: rgb(255, 0, 191);
        }

        .btn-secondary:hover {
            background-color: rgb(253, 0, 0);
        }

        .btn-primary {
            background-color: #54C392;

        }

        .btn-primary:hover {
            background-color: #06D001;
        }
    </style>

</head>

<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <h2>Event Report</h2>
            </div>
            <div class="card-body">
                <form action="PostmortemSubmit.php" method="POST" enctype="multipart/form-data"
                    onsubmit="return validateForm()">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    <!-- Event Information Section -->
                    <h5>Event Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="showproposalname" class="form-label">Proposal Name</label>
                            <input type="text" class="form-control" id="showproposalname" name="proposal_name"
                                value="<?php echo $event['Stu_Name']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="showeventname" class="form-label">Event Name</label>
                            <input type="text" class="form-control" id="showeventname" name="event_name"
                                value="<?php echo $event['Ev_Name']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="showclubname" class="form-label">Club Name</label>
                            <input type="text" class="form-control" id="showclubname" name="club_name"
                                value="<?php echo $event['Club_Name']; ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label for="showEventObjectives" class="form-label">Event Objectives</label>
                            <textarea class="form-control" id="showEventObjectives" name="event_objectives" rows="3"
                                readonly><?php echo $event['Ev_Objectives']; ?></textarea>
                        </div>
                    </div>
                    <!-- Event Flow -->
                    <div class="section-header">Event Flow</div>
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
                                    <td><?php echo date("H:i A", strtotime($flow['Flow_Time'])); ?></td>
                                    <td><?php echo $flow['Flow_Description']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>


                    <!-- Meeting of Minutes Flow -->
                    <div class="section-header">Event Minutes of Meeting</div>
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
                                    <td><?php echo date("d/m/Y", strtotime($mom['Meeting_Date'])); ?></td>
                                    <td><?php echo date("h:i A", strtotime($mom['Meeting_StartTime'])); ?></td>
                                    <td><?php echo date("h:i A", strtotime($mom['Meeting_EndTime'])); ?></td>
                                    <td><?php echo $mom['Meeting_Location']; ?></td>
                                    <td><?php echo $mom['Meeting_Discussion']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Uploads -->
                    <div class="mb-3">
                        <label for="inputPhoto" class="form-label">Upload Event Photos</label>
                        <input type="file" class="form-control" id="inputPhoto" name="event_photos[]" multiple>
                    </div>
                    <div class="mb-3">
                        <label for="inputReceipt" class="form-label">Upload Expense Receipts</label>
                        <input type="file" class="form-control" id="inputReceipt" name="expense_receipts[]" multiple>
                    </div>

                    <!-- Challenges and Conclusion -->
                    <div class="mb-3">
                        <label for="inputChallenges" class="form-label">Challenges and Difficulties</label>
                        <textarea class="form-control" id="inputChallenges" name="challenges" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="inputConclusion" class="form-label mt-3">Conclusion</label>
                        <textarea class="form-control" id="inputConclusion" name="conclusion" rows="4"></textarea>
                    </div>
                    <!-- Individual Reports -->
                    <h5>Individual Reports</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Committee Name</th>
                                    <th>Committee ID</th>
                                    <th>Position</th>
                                    <th>Duties</th>
                                    <th>Attendance</th>
                                    <th>Experience</th>
                                    <th>Challenges</th>
                                    <th>Benefits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($committee = $committee_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($committee['Com_Name']); ?></td>
                                        <td>
                                            <input type="text" class="form-control" name="committee_id[]"
                                                value="<?php echo $committee['Com_ID']; ?>" readonly>
                                        </td>
                                        <td><?php echo htmlspecialchars($committee['Com_Position']); ?></td>
                                        <td><textarea class="form-control"
                                                name="indiv_duties[<?php echo $committee['Com_ID']; ?>]"></textarea></td>
                                        <td><textarea class="form-control"
                                                name="indiv_attendance[<?php echo $committee['Com_ID']; ?>]"></textarea>
                                        </td>
                                        <td><textarea class="form-control"
                                                name="indiv_experience[<?php echo $committee['Com_ID']; ?>]"></textarea>
                                        </td>
                                        <td><textarea class="form-control"
                                                name="indiv_challenges[<?php echo $committee['Com_ID']; ?>]"></textarea>
                                        </td>
                                        <td><textarea class="form-control"
                                                name="indiv_benefits[<?php echo $committee['Com_ID']; ?>]"></textarea></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Submit Button -->
                    <div class="text-center">
                        <a href="StudentDashboard.php" class="btn btn-secondary mt-4">Back</a>
                        <button type="submit" class="btn btn-primary mt-4">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function addEventRow() {
            const tableBody = document.getElementById("Eventflow-table-body");
            const newRow = `
            <tr>
                <td><input type="time" class="form-control" name="event_time[]" required></td>
                <td><input type="text" class="form-control" name="event_flow[]" placeholder="Describe flow" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button></td>
            </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        }

        function addMeetingRow() {
            const tableBody = document.getElementById("meeting-table-body");
            const newRow = `
            <tr>
                <td><input type="date" class="form-control" name="meeting_date[]" required></td>
                <td><input type="time" class="form-control" name="meeting_start_time[]" required></td>
                <td><input type="time" class="form-control" name="meeting_end_time[]" required></td>
                <td><input type="text" class="form-control" name="meeting_location[]" placeholder="Location" required></td>
                <td><textarea class="form-control" name="meeting_discussion[]" placeholder="Discussion" required></textarea></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button></td>
            </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        }

        function removeRow(button) {
            const row = button.parentElement.parentElement;
            row.remove();
        }

        document.querySelector("form").addEventListener("submit", function (e) {
            let isValid = true;
            const errorMessages = [];

            const challenges = document.getElementById("inputChallenges").value.trim();
            if (challenges === "") {
                isValid = false;
                errorMessages.push("Please fill in the Challenges field.");
            }

            const conclusion = document.getElementById("inputConclusion").value.trim();
            if (conclusion === "") {
                isValid = false;
                errorMessages.push("Please fill in the Conclusion field.");
            }

            const eventRows = document.querySelectorAll("#Eventflow-table-body tr");
            if (eventRows.length === 0) {
                isValid = false;
                errorMessages.push("Please fill in the Event Flow.");
            } else {
                eventRows.forEach((row, index) => {
                    const timeInput = row.querySelector("input[name='event_time[]']");
                    const flowInput = row.querySelector("input[name='event_flow[]']");
                    if (!timeInput.value.trim() || !flowInput.value.trim()) {
                        isValid = false;
                        errorMessages.push(`Event Flow Row ${index + 1}: Both time and description are required.`);
                    }
                });
            }

            const meetingRows = document.querySelectorAll("#meeting-table-body tr");
            if (meetingRows.length === 0) {
                isValid = false;
                errorMessages.push("Please fill in the Minutes of Meeting.");
            } else {
                meetingRows.forEach((row, index) => {
                    const dateInput = row.querySelector("input[name='meeting_date[]']");
                    const discussionInput = row.querySelector("textarea[name='meeting_discussion[]']");
                    if (!dateInput.value.trim() || !discussionInput.value.trim()) {
                        isValid = false;
                        errorMessages.push(`Minutes of Meeting Row ${index + 1}: Date and Discussion are required.`);
                    }
                });
            }

            const eventPhotos = document.getElementById("inputPhoto").files.length;
            if (eventPhotos === 0) {
                isValid = false;
                errorMessages.push("Please upload the  Event Photo.");
            }

            const indivReports = document.querySelectorAll("textarea[name^='indiv_']");
            let indivReportFilled = true;
            indivReports.forEach((input) => {
                if (input.value.trim() === "") {
                    indivReportFilled = false;
                }
            });
            if (!indivReportFilled) {
                isValid = false;
                errorMessages.push("Please fill in the Individual Report .");
            }

            if (!isValid) {
                e.preventDefault();
                alert(errorMessages.join("\n"));
            }
        });
    </script>


</body>


</html>