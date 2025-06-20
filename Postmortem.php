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

$committee_query = "SELECT Com_ID, Com_Name, Com_Position, Com_COCUClaimers FROM Committee WHERE Ev_ID = ?";

$committee_stmt = $conn->prepare($committee_query);
$committee_stmt->bind_param("i", $event_id);
$committee_stmt->execute();
$committee_result = $committee_stmt->get_result();




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

                    <!-- New Event Flow Section -->
                    <div class="mb-3">
                        <label class="form-label">Event Flow</label>
                        <table class="table table-bordered" id="eventFlowTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Description</th>
                                    <th style="width: 50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="time" name="evflow_time[]" class="form-control" required></td>
                                    <td><input type="text" name="evflow_desc[]" class="form-control" required></td>
                                    <td><button type="button" class="btn btn-danger btn-sm"
                                            onclick="removeRow(this)">✕</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success btn-sm" onclick="addRow()">Add Row</button>
                    </div>


                    <!--uploads Statement-->
                    <div class="mb-3">
                        <label for="statementPdf" class="form-label">Upload Statement and Receipt </label>
                        <input type="file" class="form-control" id="statementPdf" name="statement_pdf" accept=".pdf"
                            required>
                        <small class="text-danger" style="display: block; margin-bottom: 10px;">
                            * Students shall upload PDF files only. Maximum file size: 5mb.
                        </small>
                    </div>

                    <!-- Uploads -->
                    <div class="mb-3">
                        <label for="repPhoto" class="form-label">Upload Event Photo</label>
                        <input type="file" class="form-control" id="inputPhoto" name="event_photos[]" accept="image/*"
                            multiple>
                        <small class="text-danger" style="display: block; margin-bottom: 10px;">
                            Maximum 10 Photos.
                        </small>

                        <!-- Challenges, recommendations and Conclusion -->
                        <div class="mb-3">
                            <label for="inputChallenges" class="form-label">Challenges and Difficulties</label>
                            <textarea class="form-control" id="inputChallenges" name="challenges" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="inputRecommendation" class="form-label mt-3">Recommendation</label>
                            <textarea class="form-control" id="inputRecommendation" name="recommendation"
                                rows="4"></textarea>
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
                                        <th>Upload Individual Report</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($committee = $committee_result->fetch_assoc()): ?>
                                        <?php if (isset($committee['Com_COCUClaimers']) && $committee['Com_COCUClaimers'] == '1'): ?>

                                            <tr>
                                                <td><?php echo htmlspecialchars($committee['Com_Name']); ?></td>
                                                <td>
                                                    <input type="text" class="form-control" name="committee_id[]"
                                                        value="<?php echo $committee['Com_ID']; ?>" readonly>
                                                </td>
                                                <td><?php echo htmlspecialchars($committee['Com_Position']); ?></td>
                                                <td>
                                                    <input type="file" class="form-control"
                                                        name="ir_file_<?php echo $committee['Com_ID']; ?>"
                                                        accept=".pdf,.doc,.docx" required>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
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
            const recommendation = document.getElementById("inputRecommendation").value.trim();
            if (recommendation === "") {
                isValid = false;
                errorMessages.push("Please fill in the Recommendation field.");
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
        function addRow() {
            const table = document.getElementById("eventFlowTable").getElementsByTagName("tbody")[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
            <td><input type="time" name="evflow_time[]" class="form-control" required></td>
            <td><input type="text" name="evflow_desc[]" class="form-control" required></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">✕</button></td>
        `;
        }

        function removeRow(btn) {
            btn.closest("tr").remove();
        }
    </script>

</body>

</html>