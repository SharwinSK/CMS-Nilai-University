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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --accent-primary: #6366f1;
            --accent-secondary: #8b5cf6;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #475569;
            --glass: rgba(30, 41, 59, 0.8);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 198, 255, 0.2) 0%, transparent 50%);
            z-index: -1;
        }

        .container-fluid {
            padding: 2rem;
        }

        .main-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow:
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            padding: 2rem;
            border: none;
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .event-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            display: inline-block;
        }

        .card-body {
            padding: 2rem;
            background: var(--bg-secondary);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-group-modern {
            display: flex;
            gap: 0.75rem;
        }

        .btn-modern {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, var(--accent-success), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-success-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, var(--accent-danger), #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-danger-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.6);
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            width: 100%;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            margin-top: 1.5rem;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.6);
        }

        .table-container {
            background: var(--bg-tertiary);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .table-responsive {
            max-height: 70vh;
            overflow: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--accent-primary) var(--bg-tertiary);
        }

        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--accent-primary);
            border-radius: 4px;
        }

        .attendance-table {
            margin-bottom: 0;
            color: var(--text-primary);
        }

        .attendance-table thead th {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            color: var(--text-primary);
            border: none;
            padding: 1.5rem 1rem;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .attendance-table tbody td {
            vertical-align: middle;
            text-align: center;
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .attendance-table tbody tr:hover td {
            background: var(--bg-tertiary);
        }

        .sticky-column {
            position: sticky;
            left: 0;
            z-index: 5;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
            background: var(--bg-secondary) !important;
        }

        .attendance-table tbody tr:hover .sticky-column {
            background: var(--bg-tertiary) !important;
        }

        .student-info {
            text-align: left !important;
            min-width: 200px;
        }

        .student-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .student-id {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 400;
        }

        .meeting-header {
            min-width: 120px;
        }

        .meeting-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .meeting-date {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .attendance-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            font-size: 0.85rem;
            min-width: 90px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .attendance-btn-present {
            background: linear-gradient(135deg, var(--accent-success), #059669);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .attendance-btn-present:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .attendance-btn-absent {
            background: linear-gradient(135deg, var(--accent-danger), #dc2626);
            color: white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .attendance-btn-absent:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }

            .header-title {
                font-size: 2rem;
            }

            .btn-group-modern {
                flex-direction: column;
            }

            .btn-modern {
                width: 100%;
            }

            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="main-card animate-fade-in">
                    <div class="card-header">
                        <h1 class="header-title">
                            <i class="fas fa-users-cog me-3"></i>Attendance Dashboard
                        </h1>
                        <div class="event-badge">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span id="eventDisplay"><?= htmlspecialchars($event_id) ?></span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="attendanceSection" style="display: none">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-clipboard-list"></i>
                                    Mark Attendance
                                </h2>
                                <div class="btn-group-modern">
                                    <button class="btn-modern btn-success-modern" onclick="markAllPresent()">
                                        <i class="fas fa-check-double me-2"></i>All Present
                                    </button>
                                    <button class="btn-modern btn-danger-modern" onclick="markAllAbsent()">
                                        <i class="fas fa-user-times me-2"></i>All Absent
                                    </button>
                                </div>
                            </div>

                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table attendance-table" id="attendanceTable">
                                        <thead>
                                            <tr id="tableHeader">
                                                <th class="sticky-column">
                                                    <i class="fas fa-user me-2"></i>Student Details
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="attendanceBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <button class="btn-modern btn-primary-modern" onclick="submitFinalAttendance()">
                                <i class="fas fa-paper-plane me-2"></i>Submit Final Attendance
                            </button>
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
            tableHeader.innerHTML = '<th class="sticky-column"><i class="fas fa-user me-2"></i>Student Details</th>';
            attendanceBody.innerHTML = '';

            // Add meeting headers
            meetings.forEach((meeting, index) => {
                const th = document.createElement("th");
                th.className = "meeting-header";
                th.innerHTML = `
                    <div class="meeting-title">
                        <i class="fas fa-calendar-day me-1"></i>Meeting ${index + 1}
                    </div>
                    <div class="meeting-date">${meeting.date}</div>
                `;
                tableHeader.appendChild(th);
            });

            // Initialize attendanceData
            attendanceData = {};

            // Add student rows with staggered animation
            students.forEach((student, index) => {
                setTimeout(() => {
                    const row = document.createElement("tr");
                    row.style.animation = `fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.05}s both`;

                    // Sticky student info cell
                    const studentCell = document.createElement("td");
                    studentCell.className = "student-info sticky-column";
                    studentCell.innerHTML = `
                        <div class="student-name">
                            <i class="fas fa-user-circle me-2 text-primary"></i>${student.Com_Name}
                        </div>
                        <div class="student-id">ID: ${student.Com_ID}</div>
                    `;
                    row.appendChild(studentCell);

                    // Create attendance slots
                    attendanceData[student.Com_ID] = {};
                    meetings.forEach((meeting, i) => {
                        attendanceData[student.Com_ID][i] = "Absent"; // Default

                        const attendanceCell = document.createElement("td");
                        attendanceCell.innerHTML = `
                            <button class="attendance-btn attendance-btn-absent" onclick="toggleAttendance('${student.Com_ID}', ${i})">
                                <i class="fas fa-times me-1"></i>Absent
                            </button>
                        `;
                        row.appendChild(attendanceCell);
                    });

                    attendanceBody.appendChild(row);
                }, index * 50);
            });
        }

        function toggleAttendance(studentId, meetingId) {
            const currentStatus = attendanceData[studentId][meetingId];
            const newStatus = currentStatus === "Present" ? "Absent" : "Present";
            attendanceData[studentId][meetingId] = newStatus;

            // Update the specific button with smooth transition
            const table = document.getElementById("attendanceTable");
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(row => {
                const idText = row.querySelector(".student-id")?.textContent || "";
                if (idText.includes(studentId)) {
                    const buttons = row.querySelectorAll(".attendance-btn");
                    const targetBtn = buttons[meetingId];
                    if (targetBtn) {
                        targetBtn.style.transform = "scale(0.9)";
                        setTimeout(() => {
                            targetBtn.className = `attendance-btn ${newStatus === "Present" ? 'attendance-btn-present' : 'attendance-btn-absent'}`;
                            targetBtn.innerHTML = `
                                <i class="fas ${newStatus === "Present" ? 'fa-check' : 'fa-times'} me-1"></i>${newStatus}
                            `;
                            targetBtn.style.transform = "scale(1)";
                        }, 150);
                    }
                }
            });
        }

        function markAllPresent() {
            students.forEach(s => meetings.forEach((_, i) => {
                attendanceData[s.Com_ID][i] = "Present";
            }));
            updateButtons();
            Swal.fire({
                icon: "success",
                title: "All Present!",
                text: "All students marked as present",
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        function markAllAbsent() {
            students.forEach(s => meetings.forEach((_, i) => {
                attendanceData[s.Com_ID][i] = "Absent";
            }));
            updateButtons();
            Swal.fire({
                icon: "info",
                title: "All Absent!",
                text: "All students marked as absent",
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        function updateButtons() {
            students.forEach((stu, r) => {
                meetings.forEach((_, c) => {
                    const btn = document.querySelectorAll("#attendanceBody tr")[r]
                        ?.querySelectorAll(".attendance-btn")[c];
                    if (btn) {
                        const status = attendanceData[stu.Com_ID][c];
                        btn.style.transform = "scale(0.9)";
                        setTimeout(() => {
                            btn.className = `attendance-btn ${status === "Present" ? "attendance-btn-present" : "attendance-btn-absent"}`;
                            btn.innerHTML = `<i class="fas ${status === "Present" ? "fa-check" : "fa-times"} me-1"></i>${status}`;
                            btn.style.transform = "scale(1)";
                        }, c * 50);
                    }
                });
            });
        }

        function submitFinalAttendance() {
            Swal.fire({
                title: 'Submit Attendance?',
                text: 'This will finalize and submit all attendance data.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, Submit',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#ef4444',
                background: '#1e293b',
                color: '#f8fafc'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Submitting...',
                        html: 'Please wait while we process your attendance data.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('PostmortemSubmit.php?mode=create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ attendance: attendanceData })
                    })
                        .then(response => {
                            const contentType = response.headers.get("content-type");

                            if (response.redirected) {
                                window.location.href = response.url;
                                return;
                            }

                            if (contentType && contentType.includes("application/json")) {
                                return response.json();
                            } else {
                                return response.text().then(text => {
                                    throw new Error("Invalid JSON: " + text);
                                });
                            }
                        })
                        .then(data => {
                            if (data?.success === false) {
                                Swal.fire({
                                    icon: "error",
                                    title: "Submission Failed",
                                    text: data.error || "Something went wrong",
                                    background: '#1e293b',
                                    color: '#f8fafc'
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: err.message || "Unexpected error occurred",
                                background: '#1e293b',
                                color: '#f8fafc'
                            });
                        });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Add loading effect
            setTimeout(() => {
                createAttendanceTable();
                document.getElementById("attendanceSection").style.display = "block";
            }, 300);
        });
    </script>
</body>

</html>