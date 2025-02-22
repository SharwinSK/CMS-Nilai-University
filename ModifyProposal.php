<?php
include('dbconfig.php');
session_start();

if (!isset($_GET['event_id'])) {
    die("Event ID is required.");
}
$event_id = $_GET['event_id'];

$query = "SELECT e.*, v.Venue_Name, c.Club_Name 
          FROM events e
          LEFT JOIN venue v ON e.Ev_Venue = v.Venue_ID
          LEFT JOIN club c ON e.Club_ID = c.Club_ID
          WHERE e.Ev_ID = '$event_id'";
$result = $conn->query($query);
$event = $result->fetch_assoc();

if (!$event) {
    die("Event not found.");
}

$committee_query = "SELECT * FROM committee WHERE Ev_ID = '$event_id'";
$committee_result = $conn->query($committee_query);
$budget_query = "SELECT * FROM budget WHERE Ev_ID = '$event_id'";
$budget_result = $conn->query($budget_query);
$pic_query = "SELECT * FROM personincharge WHERE Ev_ID = '$event_id'";
$pic_result = $conn->query($pic_query);
$person_in_charge = $pic_result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ev_name = $_POST['ev_name'];
    $ev_nature = $_POST['ev_nature'];
    $ev_objectives = $_POST['ev_objectives'];
    $ev_intro = $_POST['ev_intro'];
    $ev_details = $_POST['ev_details'];

    $query = "UPDATE events SET 
                Ev_Name = '$ev_name', 
                Ev_ProjectNature = '$ev_nature', 
                Ev_Objectives = '$ev_objectives', 
                Ev_Intro = '$ev_intro', 
                Ev_Details = '$ev_details',
                Ev_Status = 'Pending Advisor Review' 
              WHERE Ev_ID = '$event_id'";
    $conn->query($query);
    $pic_name = $_POST['pic_name'];
    $pic_id = $_POST['pic_id'];
    $pic_phone = $_POST['pic_phone'];

    $update_pic_query = "UPDATE personincharge SET 
                            PIC_Name = '$pic_name', 
                            PIC_ID = '$pic_id', 
                            PIC_PhnNum = '$pic_phone' 
                          WHERE Ev_ID = '$event_id'";
    $conn->query($update_pic_query);

    foreach ($_POST['budget_id'] as $index => $bud_id) {
        $bud_desc = $_POST['budget_description'][$index];
        $bud_amount = $_POST['budget_amount'][$index];
        $bud_type = $_POST['budget_type'][$index];
        $bud_remarks = $_POST['budget_remarks'][$index];

        if (empty($bud_id)) {
            $query = "INSERT INTO budget (Ev_ID, Bud_Desc, Bud_Amount, Bud_Type, Bud_Remarks)
                      VALUES ('$event_id', '$bud_desc', '$bud_amount', '$bud_type', '$bud_remarks')";
        } elseif ($_POST['budget_delete'][$index] == "1") {
            $query = "DELETE FROM budget WHERE Bud_ID = '$bud_id' AND Ev_ID = '$event_id'";
        } else {
            $query = "UPDATE budget SET 
                        Bud_Desc = '$bud_desc', 
                        Bud_Amount = '$bud_amount', 
                        Bud_Type = '$bud_type', 
                        Bud_Remarks = '$bud_remarks' 
                      WHERE Bud_ID = '$bud_id' AND Ev_ID = '$event_id'";
        }
        $conn->query($query);
    }


    if (!empty($_FILES['poster']['name'])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["poster"]["name"]);

        if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
            $query = "UPDATE events SET Ev_Poster = '$target_file' WHERE Ev_ID = '$event_id'";
            $conn->query($query);
        }
    }
    header("Location: StudentDashboard.php?success=1");
    exit();
}
?>
<?php
if (isset($_GET['success']) && $_GET['success'] == '1') {
    echo '<script>
        alert("Proposal submitted successfully!");
        window.location.href = "StudentDashboard.php"; // Redirect to dashboard after the message
    </script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Proposal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f7f9fc;
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
    }

    /* Form container styling */
    .container {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin: 30px auto;
        max-width: 900px;
    }

    /* Header styling */
    h1 {
        font-size: 2rem;
        color: #ffffff;
        background-color: #15B392;
        text-align: center;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    /* Section header styling */
    h5 {
        font-size: 1.25rem;
        color: #ffffff;
        background-color: #32CD32;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    /* Form label styling */
    .form-label {
        font-weight: bold;
        color: #555555;
    }

    /* Input and textarea styling */
    .form-control {
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 10px;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    /* Table styling */
    .table {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .table th {
        background-color: #54C392;
        color: white;
        text-align: center;
        font-weight: bold;
    }

    .table td {
        text-align: center;
        padding: 10px;
    }

    /* Button styling */
    button,
    .btn {

        padding: 5px 20px;
        font-size: 14px;
        transition: all 0.3s ease-in-out;
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .btn-danger:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #4e555b;
    }

    /* Additional spacing */
    .mb-3 {
        margin-bottom: 15px;
    }

    .mt-5 {
        margin-top: 30px;
    }
</style>

<body>
    <div class="container mt-5">
        <h1>Modify Proposal</h1>
        <form method="POST" enctype="multipart/form-data" action="ModifyProposal.php?event_id=<?php echo $event_id; ?>">

            <!-- Event Details -->
            <div class="mb-3">
                <label for="ev_name" class="form-label">Event Name</label>
                <input type="text" class="form-control" id="ev_name" name="ev_name"
                    value="<?php echo $event['Ev_Name']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="ev_nature" class="form-label">Event Nature</label>
                <input type="text" class="form-control" id="ev_nature" name="ev_nature"
                    value="<?php echo $event['Ev_ProjectNature']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="ev_objectives" class="form-label">Event Objectives</label>
                <textarea class="form-control" id="ev_objectives" name="ev_objectives"
                    rows="3"><?php echo $event['Ev_Objectives']; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="ev_intro" class="form-label">Event Introduction</label>
                <textarea class="form-control" id="ev_intro" name="ev_intro"
                    rows="3"><?php echo $event['Ev_Intro']; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="ev_details" class="form-label">Event Details</label>
                <textarea class="form-control" id="ev_details" name="ev_details"
                    rows="5"><?php echo $event['Ev_Details']; ?></textarea>
            </div>
            <!-- Club Name -->
            <div class="mb-3">
                <label class="form-label">Club Name</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($event['Club_Name']); ?>"
                    readonly>
            </div>

            <!-- Venue Name -->
            <div class="mb-3">
                <label class="form-label">Venue Name</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($event['Venue_Name']); ?>"
                    readonly>
            </div>

            <!-- Event Date -->
            <div class="mb-3">
                <label class="form-label">Event Date</label>
                <input type="date" class="form-control" value="<?php echo htmlspecialchars($event['Ev_Date']); ?>"
                    readonly>
            </div>

            <!-- Estimated Participants -->
            <div class="mb-3">
                <label class="form-label">Estimated Participants</label>
                <input type="number" class="form-control" value="<?php echo htmlspecialchars($event['Ev_Pax']); ?>"
                    readonly>
            </div>

            <!-- Start Time -->
            <div class="mb-3">
                <label class="form-label">Start Time</label>
                <input type="time" class="form-control" value="<?php echo htmlspecialchars($event['Ev_StartTime']); ?>"
                    readonly>
            </div>

            <!-- End Time -->
            <div class="mb-3">
                <label class="form-label">End Time</label>
                <input type="time" class="form-control" value="<?php echo htmlspecialchars($event['Ev_EndTime']); ?>"
                    readonly>
            </div>

            <!-- Person in Charge -->
            <h5>Person in Charge</h5>
            <div class="mb-3">
                <label for="pic_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="pic_name" name="pic_name"
                    value="<?php echo $person_in_charge['PIC_Name']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="pic_id" class="form-label">ID</label>
                <input type="text" class="form-control" id="pic_id" name="pic_id"
                    value="<?php echo $person_in_charge['PIC_ID']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="pic_phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="pic_phone" name="pic_phone"
                    value="<?php echo $person_in_charge['PIC_PhnNum']; ?>" required>
            </div>


            <!-- Committee Members -->
            <h5>Committee Members</h5>
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
                    <?php while ($committee = $committee_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $committee['Com_Name']; ?></td>
                            <td><?php echo $committee['Com_Position']; ?></td>
                            <td><?php echo $committee['Com_Department']; ?></td>
                            <td><?php echo $committee['Com_PhnNum']; ?></td>
                            <td><?php echo $committee['Com_JobScope']; ?></td>
                            <td><?php echo $committee['Com_COCUClaimers'] == '1' ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Budget -->
            <h5>Budget</h5>
            <table class="table table-bordered" id="budgetTable">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($budget = $budget_result->fetch_assoc()): ?>
                        <tr>
                            <input type="hidden" name="budget_id[]" value="<?php echo $budget['Bud_ID']; ?>">
                            <td><input type="text" name="budget_description[]" value="<?php echo $budget['Bud_Desc']; ?>"
                                    class="form-control"></td>
                            <td><input type="number" step="0.01" name="budget_amount[]"
                                    value="<?php echo $budget['Bud_Amount']; ?>" class="form-control"></td>
                            <td>
                                <select name="budget_type[]" class="form-control">
                                    <option value="Income" <?php echo $budget['Bud_Type'] == 'Income' ? 'selected' : ''; ?>>
                                        Income</option>
                                    <option value="Expense" <?php echo $budget['Bud_Type'] == 'Expense' ? 'selected' : ''; ?>>
                                        Expense</option>
                                </select>
                            </td>
                            <td><input type="text" name="budget_remarks[]" value="<?php echo $budget['Bud_Remarks']; ?>"
                                    class="form-control"></td>
                            <td>
                                <input type="hidden" name="budget_delete[]" value="0">
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="deleteRow(this)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="button" class="btn btn-success btn-sm" onclick="addRow('budgetTable')">Add Row</button>

            <div class="container mt-5">
                <!-- Poster -->
                <h5>Event Poster</h5>
                <div class="mb-3">
                    <?php if (!empty($event['Ev_Poster'])): ?>
                        <img src="<?php echo $event['Ev_Poster']; ?>" alt="Event Poster" class="img-thumbnail mb-3"
                            width="200">
                    <?php endif; ?>
                    <input type="file" class="form-control" name="poster">
                </div>

            </div>
            <!-- Submit -->
            <button type="submit" class="btn btn-primary" onclick="showConfirmation()">Submit Proposal</button>
            <a href="StudentDashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <script>
        function addRow(tableId) {
            const table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            const row = table.insertRow(-1);
            row.innerHTML = `
                <input type="hidden" name="budget_id[]" value="">
                <td><input type="text" name="budget_description[]" class="form-control" required></td>
                <td><input type="number" step="0.01" name="budget_amount[]" class="form-control" required></td>
                <td>
                    <select name="budget_type[]" class="form-control">
                        <option value="Income">Income</option>
                        <option value="Expense">Expense</option>
                    </select>
                </td>
                <td><input type="text" name="budget_remarks[]" class="form-control"></td>
                <td>
                    <input type="hidden" name="budget_delete[]" value="0">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button>
                </td>`;
        }

        function deleteRow(button) {
            const row = button.closest('tr');
            row.querySelector('input[name="budget_delete[]"]').value = "1";
            row.style.display = "none"; //
        }
    </script>
</body>

</html>