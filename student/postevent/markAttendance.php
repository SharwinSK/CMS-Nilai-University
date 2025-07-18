<?php
session_start();

// Check if post-event session data is available
if (!isset($_SESSION['post_event_data'])) {
    die("Post-event session data is missing.");
}

$data = $_SESSION['post_event_data'];
$event_id = $data['event_id'];
$committee = $data['committee'];
$meetings = $data['meetings'];

// Just in case: assign default empty arrays
$committee = is_array($committee) ? $committee : [];
$meetings = is_array($meetings) ? $meetings : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Attendance Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-medium: #9de5ff;
            --primary-dark: #aca8ff;
            --primary-purple: #ac73ff;
        }

        body {
            background: linear-gradient(135deg,
                    var(--primary-medium) 0%,
                    var(--primary-dark) 50%,
                    var(--primary-purple) 100%);
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            padding: 20px;
        }

        .card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: none;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card-header {
            background: linear-gradient(135deg,
                    var(--primary-medium) 0%,
                    var(--primary-dark) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg,
                    var(--primary-dark) 0%,
                    var(--primary-purple) 100%);
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            transition: all 0.3s ease;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            transition: all 0.3s ease;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            transition: all 0.3s ease;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg,
                    var(--primary-dark) 0%,
                    var(--primary-purple) 100%);
            color: white;
            border: none;
            padding: 15px;
            text-align: center;
        }

        .table tbody td {
            vertical-align: middle;
            text-align: center;
            padding: 12px;
        }

        .attendance-cell {
            min-width: 100px;
        }

        .student-info {
            text-align: left !important;
        }

        .student-name {
            font-weight: bold;
            color: #333;
        }

        .student-id {
            color: #666;
            font-size: 0.9em;
        }

        .meeting-header {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            min-width: 80px;
            font-size: 0.9em;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .form-control,
        .form-select {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 0.2rem rgba(172, 115, 255, 0.25);
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .meeting-date {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }

        .export-btn {
            background: linear-gradient(135deg,
                    var(--primary-medium) 0%,
                    var(--primary-dark) 100%);
        }

        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        .sticky-column {
            position: sticky;
            left: 0;
            background: white;
            z-index: 10;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h2 class="mb-0">
                            <i class="fas fa-users me-2"></i>Student Attendance Tracker
                        </h2>
                        <div class="mt-2">
                            <span id="eventDisplay" class="badge bg-light text-dark fs-6"></span>
                        </div>
                    </div>
                    <div class="card-body">


                        <!-- Attendance Table -->
                        <div id="attendanceSection" style="display: none">
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <h4>
                                        <i class="fas fa-clipboard-check me-2"></i>Mark Attendance
                                    </h4>
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group w-100">
                                        <button class="btn btn-success" onclick="markAllPresent()">
                                            <i class="fas fa-check-double me-2"></i>All Present
                                        </button>
                                        <button class="btn btn-danger" onclick="markAllAbsent()">
                                            <i class="fas fa-times me-2"></i>All Absent
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <button class="btn btn-primary w-100" onclick="submitFinalAttendance()">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Final Attendance
                                    </button>
                                </div>

                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped" id="attendanceTable">
                                    <thead>
                                        <tr id="tableHeader">
                                            <th class="sticky-column">Student Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attendanceBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.all.min.js"></script>

    <script>
        const committee = <?= json_encode($committee) ?>;
        const meetings = <?= json_encode($meetings) ?>;
        const eventName = "<?= htmlspecialchars($event_id) ?>";

        let attendanceData = {};
        let students = committee;


        function createAttendanceTable() {
            const tableHeader = document.getElementById("tableHeader");
            const attendanceBody = document.getElementById("attendanceBody");

            // Clear previous data
            tableHeader.innerHTML = '<th class="sticky-column">Student Details</th>';
            attendanceBody.innerHTML = '';

            // Add meeting headers
            meetings.forEach((meeting, index) => {
                const th = document.createElement("th");
                th.className = "meeting-header";
                th.innerHTML = `
            <div>Meeting ${index + 1}</div>
            <div class="meeting-date">${meeting.date}</div>
        `;
                tableHeader.appendChild(th);
            });

            // Initialize attendanceData
            attendanceData = {};

            // Add student rows
            students.forEach((student) => {
                const row = document.createElement("tr");

                // Sticky student info cell
                const studentCell = document.createElement("td");
                studentCell.className = "student-info sticky-column";
                studentCell.innerHTML = `
            <div class="student-name">${student.Com_Name}</div>
            <div class="student-id">ID: ${student.Com_ID}</div>
        `;
                row.appendChild(studentCell);

                // Create attendance slots
                attendanceData[student.Com_ID] = {};
                meetings.forEach((meeting, i) => {
                    attendanceData[student.Com_ID][i] = "Absent"; // Default

                    const attendanceCell = document.createElement("td");
                    attendanceCell.className = "attendance-cell";
                    attendanceCell.innerHTML = `
                <button class="btn btn-danger btn-sm" onclick="toggleAttendance('${student.Com_ID}', ${i})">
                    <i class="fas fa-times me-1"></i>Absent
                </button>
            `;
                    row.appendChild(attendanceCell);
                });

                attendanceBody.appendChild(row);
            });
        }

        function toggleAttendance(studentId, meetingId) {
            const currentStatus = attendanceData[studentId][meetingId];
            const newStatus = currentStatus === "Present" ? "Absent" : "Present";
            attendanceData[studentId][meetingId] = newStatus;

            // Update the specific button only (faster and smoother)
            const table = document.getElementById("attendanceTable");
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(row => {
                const idText = row.querySelector(".student-id")?.textContent || "";
                if (idText.includes(studentId)) {
                    const buttons = row.querySelectorAll("button");
                    const targetBtn = buttons[meetingId];
                    if (targetBtn) {
                        targetBtn.className = `btn btn-sm ${newStatus === "Present" ? 'btn-success' : 'btn-danger'}`;
                        targetBtn.innerHTML = `
                    <i class="fas ${newStatus === "Present" ? 'fa-check' : 'fa-times'} me-1"></i>${newStatus}
                `;
                    }
                }
            });
        }


        function markAllPresent() {
            students.forEach((student) => {
                meetings.forEach((_, i) => {
                    attendanceData[student.Com_ID][i] = "Present";
                });
            });
            createAttendanceTable();
            Swal.fire({ icon: "success", title: "All Marked Present!", timer: 1500, showConfirmButton: false });
        }

        function markAllAbsent() {
            students.forEach((student) => {
                meetings.forEach((_, i) => {
                    attendanceData[student.Com_ID][i] = "Absent";
                });
            });
            createAttendanceTable();
            Swal.fire({ icon: "info", title: "All Marked Absent!", timer: 1500, showConfirmButton: false });
        }




        function submitFinalAttendance() {
            Swal.fire({
                icon: 'question',
                title: 'Are you sure?',
                text: 'This will submit the full attendance data and post-event form.',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Prepare data
                    const payload = {
                        attendance: attendanceData
                    };

                    fetch('PostmortemSubmit.php?mode=create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })

                }
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            createAttendanceTable();
            document.getElementById("attendanceSection").style.display = "block";
        });



    </script>
</body>

</html>