<?php
session_start();
include('dbconfig.php');

$stu_id = $_SESSION['Stu_ID'];
$result = $conn->query("SELECT Stu_Name FROM Student WHERE Stu_ID='$stu_id'");
$student = $result->fetch_assoc();
$clubs = $conn->query("SELECT Club_ID, Club_Name FROM Club");
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Proposal Submission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f9;
        }

        .container {
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #15B392;
            color: white;
        }

        .btn {
            border-radius: 5px;
        }

        .btn-success,
        .btn-primary {
            background-color: #54C392;
            border-color: #54C392;
        }

        .btn-secondary {
            background-color: rgb(255, 0, 191);
            border-color: rgb(255, 0, 191);
        }

        .btn-success:hover,
        .btn-primary:hover {
            background-color: #06D001;
        }

        .btn-secondary:hover {
            background-color: rgb(253, 0, 0);
        }

        table thead th {
            text-align: center;
            white-space: nowrap;
        }

        table td input,
        table td select {
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h2>Event Proposal Submission</h2>
            </div>
            <div class="card-body">
                <form action="ProposalSubmit.php" method="POST" enctype="multipart/form-data">

                    <!-- Event Information Section -->
                    <h5 class="card-title mb-3">Event Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="inputStudentName" class="form-label">Student Name</label>
                            <input type="text" class="form-control" id="inputStudentName" name="stu_name"
                                value="<?php echo $student['Stu_Name']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="inputClub" class="form-label">Club</label>
                            <select class="form-select" id="inputClub" name="club_id" required>
                                <option selected disabled>Select Your Club</option>
                                <?php while ($club = $clubs->fetch_assoc()): ?>
                                    <option value="<?php echo $club['Club_ID']; ?>"><?php echo $club['Club_Name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="inputEventName" class="form-label">Event Name</label>
                            <input type="text" class="form-control" name="ev_name" id="inputEventName"
                                placeholder="Enter event name">
                        </div>
                        <div class="col-md-6">
                            <label for="inputEventNature" class="form-label">Event Nature</label>
                            <input type="text" class="form-control" name="ev_nature" id="inputEventNature"
                                placeholder="Enter event nature">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="inputIntroduction" class="form-label">Event Objectives</label>
                        <textarea class="form-control" id="inputObjectives" rows="3" name="ev_objectives"
                            placeholder="Enter Objectives"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="inputIntroduction" class="form-label">Introduction of Event</label>
                        <textarea class="form-control" id="inputIntroduction" rows="3" name="ev_intro"
                            placeholder="Enter introduction"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="inputDetails" class="form-label">Event Details</label>
                        <textarea class="form-control" id="inputDetails" rows="4" name="ev_details"
                            placeholder="Enter event details"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="range" class="form-label">Estimated Participants</label>
                        <input type="text" class="form-control" name="ev_pax" id="customRange1"
                            placeholder="Enter estimated participants">
                    </div>


                    <div class="mb-3">
                        <div class="row g-2">
                            <!-- Date Picker -->
                            <div class="col-auto">
                                <label for="dateInput" class="form-label">Date</label>
                                <input type="date" id="dateInput" name="ev_date" class="form-control form-control-sm"
                                    required>
                            </div>
                            <!-- Start Time Picker -->
                            <div class="col-auto">
                                <label for="startTime" class="form-label">From</label>
                                <select id="startTime" name="ev_start_time" class="form-select form-select-sm" required>
                                    <option value="" selected disabled>Start Time</option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                    <option value="17:00">05:00 PM</option>
                                    <option value="18:00">06:00 PM</option>
                                    <option value="19:00">07:00 PM</option>
                                    <option value="20:00">08:00 PM</option>
                                    <option value="21:00">09:00 PM</option>
                                    <option value="22:00">10:00 PM</option>
                                </select>
                            </div>
                            <!-- End Time Picker -->
                            <div class="col-auto">
                                <label for="endTime" class="form-label">To</label>
                                <select id="endTime" name="ev_end_time" class="form-select form-select-sm" required>
                                    <option value="" selected disabled>End Time</option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                    <option value="17:00">05:00 PM</option>
                                    <option value="18:00">06:00 PM</option>
                                    <option value="19:00">07:00 PM</option>
                                    <option value="20:00">08:00 PM</option>
                                    <option value="21:00">09:00 PM</option>
                                    <option value="22:00">10:00 PM</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="inputVenue" class="form-label">Venue</label>
                        <select class="form-select" id="inputVenue" name="ev_venue" required>
                            <option selected disabled>Select a venue</option>
                            <option value="Classroom R301">Classroom R301</option>
                            <option value="Classroom R302">Classroom R302</option>
                            <option value="Classroom R303">Classroom R303</option>
                            <option value="Classroom R304">Classroom R304</option>
                            <option value="Classroom R305">Classroom R305</option>
                            <option value="Classroom R306">Classroom R306</option>
                            <option value="Classroom T101">Classroom T101</option>
                            <option value="Classroom T102">Classroom T102</option>
                            <option value="Classroom T103">Classroom T103</option>
                            <option value="Classroom T104">Classroom T104</option>
                            <option value="Lecture Hall LH201">Lecture Hall LH201</option>
                            <option value="Lecture Hall LH202">Lecture Hall LH202</option>
                            <option value="Lecture Hall LH203">Lecture Hall LH203</option>
                            <option value="Lecture Hall LH204">Lecture Hall LH204</option>
                            <option value="Lecture Hall S501">Lecture Hall S501</option>
                            <option value="Lecture Hall S502">Lecture Hall S502</option>
                            <option value="President Hall(PH)">President Hall(PH)</option>
                            <option value="PH Foyer">PH Foyer</option>
                            <option value="PH VIP Room">PH VIP Room</option>
                            <option value="Gymnasium">Gymnasium</option>
                            <option value="PH WalkWay">PH WalkWay</option>
                            <option value="Resource Centre(RC)">Resource Centre(RC)</option>
                            <option value="RC WalkWay">RC WalkWay</option>
                            <option value="Common Room">Common Room</option>
                            <option value="Meeting of Minds(MOM)">Meeting of Minds(MOM)</option>
                            <option value="Cafeteria">Cafeteria</option>
                            <option value="Badminton Court">Badminton Court</option>
                            <option value="Tennis Court">Tennis Court</option>
                            <option value="Basketball Court">Basketball Court</option>
                            <option value="Volleyball Court">Volleyball Court</option>
                            <option value="Main Field">Main Field</option>
                            <option value="RC Field">RC Field</option>
                            <option value="Lakeside Field">Lakeside Field</option>
                            <option value="Netball Court">Netball Court</option>
                            <option value="4 in 1 Court">4 in 1 Court</option>

                        </select>
                    </div>

                    <!-- Upload Poster -->
                    <div class="mb-3">
                        <label for="inputPoster" class="form-label">Upload Poster</label>
                        <input type="file" class="form-control" id="inputPoster" name="poster">
                    </div>
                    <!-- Person in Charge Section -->
                    <h5 class="card-title mb-3">Person in Charge</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="inputPICName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="inputPICName" name="pic_name"
                                placeholder="Enter name">
                        </div>
                        <div class="col-md-6">
                            <label for="inputPICID" class="form-label">ID</label>
                            <input type="text" class="form-control" id="inputPICID" name="pic_id"
                                placeholder="Enter ID">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="inputPICPhone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="inputPICPhone" name="pic_phone"
                            placeholder="Enter phone number">
                    </div>
                    <!-- Event Flow Section -->
                    <h5>Event Flow</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Flow</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="Eventflow-table-body"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success mb-3" onclick="addEventRow()">Add Row</button>
                    <!-- Minutes of Meeting -->
                    <h5>Minutes of Meeting</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Location</th>
                                    <th>Discussion</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="meeting-table-body"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success mb-3" onclick="addMeetingRow()">Add Row</button>
                    <!-- Committee Section -->
                    <h5 class="card-title mb-3">Committee Members</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Phone</th>
                                    <th>Job Scope</th>
                                    <th>COCU Claimers</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="committee-table-body"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success mb-3" onclick="addCommitteeRow()">Add Member</button>
                    <!-- Budget Section -->
                    <h5 class="card-title mb-3">Budget</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Income/Expense</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="budget-table-body"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success mb-3" onclick="addBudgetRow()">Add Budget Item</button>
                    <!-- Submit Section -->
                    <div class="text-center">
                        <a href="StudentDashboard.php" class="btn btn-secondary mt-4">Back</a>
                        <button type="submit" class="btn btn-primary mt-4">Submit Proposal</button>
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
        function addCommitteeRow() {
            const tableBody = document.getElementById("committee-table-body");
            const newRow = `
                <tr>
                    <td><input type="text" class="form-control" name="student_id[]" placeholder="ID"></td>
                    <td><input type="text" class="form-control" name="student_name[]" placeholder="Name"></td>
                    <td><input type="text" class="form-control" name="student_position[]" placeholder="Position"></td>
                    <td>
                        <select class="form-select" name="student_department[]">
                            <option value="">Department</option>
                            <option value="Foundation in Business">Foundation in Business</option>
                            <option value="Foundation in Science">Foundation in Science</option>
                            <option value="DCS">Diploma in Computer Science</option>
                            <option value="DIA">Diploma in Accounting</option>
                            <option value="DAME">Diploma in Aircraft Maintenance Engineering</option>
                            <option value="DIT">Diploma in Information Technology</option>
                            <option value="DHM">Diploma in Hotel Management</option>
                            <option value="DCA">Diploma in Culinary Arts</option>
                            <option value="DBA">Diploma in Business Adminstration</option>
                            <option value="DIN">Diploma in Nursing</option>
                            <option value="BOF">Bachelor of Finance</option>
                            <option value="BAAF">Bachelor of Arts in Accounting & Finance</option>
                            <option value="BBAF">Bachelor of Business Adminstration in Finance</option>
                            <option value="BSB">Bachelor of Science Biotechonology</option>
                            <option value="BCSAI">Bachelor of Computer Science Artificial intelligence</option>
                            <option value="BITC">Bachelor of Information Technology Cybersecurity</option>
                            <option value="BSE">Bachelor of Software Engineering</option>
                            <option value="BCSDS">Bachelor of Computer Science Data Science</option>
                            <option value="BIT">Bachelor of Information Technology</option>
                            <option value="BITIECC">Bachelor of Information Technology Internet Engineering and Cloud Computing</option>
                            <option value="BEM">Bachelor of Event Management</option>
                            <option value="BHMBM">Bachelor of Hospitality Management with Business management</option>
                            <option value="BBAGL">Bachelor of Business Adminstration in Global Logistic</option>
                            <option value="BBADM">Bachelor of Business Adminstration in Digital Marketing</option>
                            <option value="BBAM">Bachelor of Business Adminstration in Marketing</option>
                            <option value="BBAMT">Bachelor of Business Adminstration in Management</option>
                            <option value="BBAIB">Bachelor of Business Adminstration in International Business</option>
                            <option value="BBAHRM">Bachelor of Business Adminstration in Human Resource Management</option>
                            <option value="BBA">Bachelor of Business Adminstration</option>
                            <option value="BSN">Bachelor of Science in Nursing</option>
                        </select>
                    </td>
                    <td><input type="text" class="form-control" name="student_phone[]" placeholder="Phone"></td>
                    <td><input type="text" class="form-control" name="student_job[]" placeholder="Job Scope"></td>
                    <td>
                        <select class="form-select" name="cocu_claimers[]">
                            <option value="">Claimers</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button>
                    </td>
                </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        }

        function addBudgetRow() {
            const tableBody = document.getElementById("budget-table-body");
            const newRow = `
                <tr>
                    <td><input type="text" class="form-control" name="description[]" placeholder="Enter Description"></td>
                    <td><input type="number" class="form-control" name="amount[]" placeholder="Enter Amount"></td>
                    <td>
                        <select class="form-select" name="income_expense[]">
                            <option value="">Select Option</option>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </td>
                    <td><input type="text" class="form-control" name="remarks[]" placeholder="Enter Remarks"></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">Delete</button>
                    </td>
                </tr>`;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        }
        function deleteRow(button) {
            const row = button.closest('tr');
            row.remove();
        }



        function validateRows(sectionName, rowSelector, requiredFields) {
            const rows = document.querySelectorAll(rowSelector);
            if (rows.length === 0) {
                return `Please fill the  ${sectionName} section.`;
            }

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                for (const fieldName of requiredFields) {
                    const field = row.querySelector(`[name="${fieldName}"]`);
                    if (!field || !field.value.trim()) {
                        return `Please fill the ${sectionName} section ${i + 1}.`;
                    }
                }
            }

            return null;
        }


        document.querySelector('form').addEventListener('submit', function (event) {
            const errorMessages = [];


            const posterInput = document.getElementById('inputPoster');
            if (!posterInput.files.length) {
                errorMessages.push('Please upload a poster before submitting.');
            }
            const committeeError = validateRows(
                'Committee',
                '#committee-table-body tr',
                [
                    'student_id[]', 'student_name[]', 'student_position[]',
                    'student_department[]', 'student_phone[]', 'student_job[]', 'cocu_claimers[]'
                ]
            );
            if (committeeError) {
                errorMessages.push(committeeError);
            }
            const budgetError = validateRows(
                'Budget',
                '#budget-table-body tr',
                ['description[]', 'amount[]', 'income_expense[]', 'remarks[]']
            );
            if (budgetError) {
                errorMessages.push(budgetError);
            }

            if (errorMessages.length > 0) {
                event.preventDefault();
                alert(errorMessages.join('\n'));
            }
        });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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