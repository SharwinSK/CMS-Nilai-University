<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'usermanagement';

// Check if user is logged in and is an admin
if (!isset($_SESSION['Admin_ID']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/adminlogin.php");
    exit();
}

// Get admin details for navbar
$admin_query = "SELECT Admin_Name FROM admin WHERE Admin_ID = ?";
$admin_stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($admin_stmt, "i", $_SESSION['Admin_ID']);
mysqli_stmt_execute($admin_stmt);
$admin_result = mysqli_stmt_get_result($admin_stmt);
$admin_data = mysqli_fetch_assoc($admin_result);
$admin_name = $admin_data['Admin_Name'] ?? 'Admin';
mysqli_stmt_close($admin_stmt);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'get_students':
            $students = getStudents($conn);
            echo json_encode($students);
            exit();

        case 'get_student_events':
            $studentId = mysqli_real_escape_string($conn, $_POST['student_id']);
            $events = getStudentEvents($conn, $studentId);
            echo json_encode($events);
            exit();

        case 'add_student':
            $result = addStudent($conn, $_POST);
            echo json_encode($result);
            exit();

        case 'update_student':
            $result = updateStudent($conn, $_POST);
            echo json_encode($result);
            exit();

        case 'delete_student':
            $studentId = mysqli_real_escape_string($conn, $_POST['student_id']);
            $result = deleteStudent($conn, $studentId);
            echo json_encode($result);
            exit();

        case 'get_student_details':
            $studentId = mysqli_real_escape_string($conn, $_POST['student_id']);
            $student = getStudentDetails($conn, $studentId);
            echo json_encode($student);
            exit();
    }
}

// Function to get all students with event count
function getStudents($conn)
{
    $query = "
        SELECT 
            s.Stu_ID,
            s.Stu_Name,
            s.Stu_Email,
            s.Stu_Program,
            s.Stu_School,
            COUNT(e.Ev_ID) as total_events
        FROM student s
        LEFT JOIN events e ON s.Stu_ID = e.Stu_ID
        GROUP BY s.Stu_ID, s.Stu_Name, s.Stu_Email, s.Stu_Program, s.Stu_School
        ORDER BY s.Stu_Name
    ";

    $result = mysqli_query($conn, $query);
    $students = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = [
                'id' => $row['Stu_ID'],
                'name' => $row['Stu_Name'],
                'email' => $row['Stu_Email'],
                'program' => $row['Stu_Program'],
                'school' => $row['Stu_School'],
                'totalEvents' => (int) $row['total_events']
            ];
        }
    }

    return $students;
}

// Function to get student events
function getStudentEvents($conn, $studentId)
{
    $query = "
        SELECT 
            e.Ev_ID,
            e.Ev_Name,
            c.Club_Name
        FROM events e
        LEFT JOIN club c ON e.Club_ID = c.Club_ID
        WHERE e.Stu_ID = ?
        ORDER BY e.Ev_Date DESC
    ";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $events = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'eventId' => $row['Ev_ID'],
                'eventName' => $row['Ev_Name'],
                'clubName' => $row['Club_Name'] ?? 'No Club Assigned'
            ];
        }
    }

    mysqli_stmt_close($stmt);
    return $events;
}

// Function to get detailed student information
function getStudentDetails($conn, $studentId)
{
    $query = "SELECT * FROM student WHERE Stu_ID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $student = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $student = [
            'id' => $row['Stu_ID'],
            'name' => $row['Stu_Name'],
            'email' => $row['Stu_Email'],
            'program' => $row['Stu_Program'],
            'school' => $row['Stu_School']
        ];
    }

    mysqli_stmt_close($stmt);
    return $student;
}

// Function to add new student
function addStudent($conn, $data)
{
    $id = mysqli_real_escape_string($conn, $data['student_id']);
    $name = mysqli_real_escape_string($conn, $data['name']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $program = mysqli_real_escape_string($conn, $data['program']);
    $school = mysqli_real_escape_string($conn, $data['school']);

    // Check if student ID already exists
    $check_query = "SELECT Stu_ID FROM student WHERE Stu_ID = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        mysqli_stmt_close($check_stmt);
        return ['success' => false, 'message' => 'Student ID already exists'];
    }
    mysqli_stmt_close($check_stmt);

    // Check if email already exists
    $email_check_query = "SELECT Stu_Email FROM student WHERE Stu_Email = ?";
    $email_check_stmt = mysqli_prepare($conn, $email_check_query);
    mysqli_stmt_bind_param($email_check_stmt, "s", $email);
    mysqli_stmt_execute($email_check_stmt);
    $email_check_result = mysqli_stmt_get_result($email_check_stmt);

    if (mysqli_num_rows($email_check_result) > 0) {
        mysqli_stmt_close($email_check_stmt);
        return ['success' => false, 'message' => 'Email already exists'];
    }
    mysqli_stmt_close($email_check_stmt);

    // Insert new student
    $insert_query = "INSERT INTO student (Stu_ID, Stu_Name, Stu_Email, Stu_PSW, Stu_Program, Stu_School) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "ssssss", $id, $name, $email, $password, $program, $school);

    if (mysqli_stmt_execute($insert_stmt)) {
        mysqli_stmt_close($insert_stmt);
        return ['success' => true, 'message' => 'Student added successfully'];
    } else {
        mysqli_stmt_close($insert_stmt);
        return ['success' => false, 'message' => 'Failed to add student'];
    }
}

// Function to update student
function updateStudent($conn, $data)
{
    $id = mysqli_real_escape_string($conn, $data['student_id']);
    $name = mysqli_real_escape_string($conn, $data['name']);
    $email = mysqli_real_escape_string($conn, $data['email']);
    $program = mysqli_real_escape_string($conn, $data['program']);
    $school = mysqli_real_escape_string($conn, $data['school']);

    // Check if email already exists for another student
    $email_check_query = "SELECT Stu_ID FROM student WHERE Stu_Email = ? AND Stu_ID != ?";
    $email_check_stmt = mysqli_prepare($conn, $email_check_query);
    mysqli_stmt_bind_param($email_check_stmt, "ss", $email, $id);
    mysqli_stmt_execute($email_check_stmt);
    $email_check_result = mysqli_stmt_get_result($email_check_stmt);

    if (mysqli_num_rows($email_check_result) > 0) {
        mysqli_stmt_close($email_check_stmt);
        return ['success' => false, 'message' => 'Email already exists for another student'];
    }
    mysqli_stmt_close($email_check_stmt);

    // Update student information
    if (!empty($data['password'])) {
        // Update with password
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $update_query = "UPDATE student SET Stu_Name = ?, Stu_Email = ?, Stu_PSW = ?, Stu_Program = ?, Stu_School = ? WHERE Stu_ID = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssssss", $name, $email, $password, $program, $school, $id);
    } else {
        // Update without password
        $update_query = "UPDATE student SET Stu_Name = ?, Stu_Email = ?, Stu_Program = ?, Stu_School = ? WHERE Stu_ID = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssss", $name, $email, $program, $school, $id);
    }

    if (mysqli_stmt_execute($update_stmt)) {
        mysqli_stmt_close($update_stmt);
        return ['success' => true, 'message' => 'Student updated successfully'];
    } else {
        mysqli_stmt_close($update_stmt);
        return ['success' => false, 'message' => 'Failed to update student'];
    }
}

// Function to delete student
function deleteStudent($conn, $studentId)
{
    // Check if student has any events
    $event_check_query = "SELECT COUNT(*) as event_count FROM events WHERE Stu_ID = ?";
    $event_check_stmt = mysqli_prepare($conn, $event_check_query);
    mysqli_stmt_bind_param($event_check_stmt, "s", $studentId);
    mysqli_stmt_execute($event_check_stmt);
    $event_check_result = mysqli_stmt_get_result($event_check_stmt);
    $event_count = mysqli_fetch_assoc($event_check_result)['event_count'];
    mysqli_stmt_close($event_check_stmt);

    if ($event_count > 0) {
        return ['success' => false, 'message' => 'Cannot delete student with existing events. Please delete events first.'];
    }

    // Delete student
    $delete_query = "DELETE FROM student WHERE Stu_ID = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "s", $studentId);

    if (mysqli_stmt_execute($delete_stmt)) {
        mysqli_stmt_close($delete_stmt);
        return ['success' => true, 'message' => 'Student deleted successfully'];
    } else {
        mysqli_stmt_close($delete_stmt);
        return ['success' => false, 'message' => 'Failed to delete student'];
    }
}

// Define schools and their programs (matching your registration form)
$schoolPrograms = [
    "School of Computing" => [
        "Diploma in Computer Science",
        "Bachelor of Computer Science (Hons)",
        "Bachelor of Information Technology (Hons)",
        "Bachelor of Software Engineering (Hons)"
    ],
    "School of Accounting and Finance" => [
        "Diploma in Accounting",
        "Bachelor of Accounting (Hons)",
        "Bachelor of Finance (Hons)",
        "Bachelor of Business Administration (Finance)"
    ],
    "School of Aircraft Maintenance" => [
        "Diploma in Aircraft Maintenance Engineering",
        "Bachelor of Aircraft Maintenance Technology"
    ],
    "School of Applied Science" => [
        "Diploma in Applied Science",
        "Bachelor of Applied Science (Hons)",
        "Bachelor of Food Science (Hons)"
    ],
    "School of Foundation Studies" => [
        "Foundation in Science",
        "Foundation in Business",
        "Foundation in Information Technology"
    ],
    "School of Hospitality and Tourism" => [
        "Diploma in Hotel Management",
        "Bachelor of Hospitality Management (Hons)",
        "Bachelor of Tourism Management (Hons)"
    ],
    "School of Management and Marketing" => [
        "Diploma in Business Administration",
        "Bachelor of Business Administration (Hons)",
        "Bachelor of Marketing (Hons)",
        "Bachelor of Human Resource Management (Hons)"
    ],
    "School of Nursing" => [
        "Diploma in Nursing",
        "Bachelor of Nursing (Hons)"
    ]
];

// Get unique schools and programs for filters from database
$schools_query = "SELECT DISTINCT Stu_School FROM student ORDER BY Stu_School";
$schools_result = mysqli_query($conn, $schools_query);
$schools = [];
if ($schools_result) {
    while ($row = mysqli_fetch_assoc($schools_result)) {
        $schools[] = $row['Stu_School'];
    }
}

$programs_query = "SELECT DISTINCT Stu_Program FROM student ORDER BY Stu_Program";
$programs_result = mysqli_query($conn, $programs_query);
$programs = [];
if ($programs_result) {
    while ($row = mysqli_fetch_assoc($programs_result)) {
        $programs[] = $row['Stu_Program'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
    <link href="../assets/css/admin/studmanage.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>

    <!-- Main Content -->
    <div class="content-container">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-users me-3"></i>Student Management
                </h1>
                <p class="text-muted mb-0">Manage and view all student information and activities</p>
            </div>
            <div>
                <button class="add-student-btn" onclick="addNewStudent()" title="Add New Student">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-container">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="searchInput" class="form-label fw-semibold">
                        <i class="fas fa-search me-2"></i>Search Students
                    </label>
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Search by ID, name, or email...">
                </div>
                <div class="col-md-3">
                    <label for="schoolFilter" class="form-label fw-semibold">
                        <i class="fas fa-building me-2"></i>School
                    </label>
                    <select class="form-select" id="schoolFilter" onchange="updateFilterPrograms()">
                        <option value="">All Schools</option>
                        <option value="School of Computing">School of Computing</option>
                        <option value="School of Accounting and Finance">School of Accounting and Finance</option>
                        <option value="School of Aircraft Maintenance">School of Aircraft Maintenance</option>
                        <option value="School of Applied Science">School of Applied Science</option>
                        <option value="School of Foundation Studies">School of Foundation Studies</option>
                        <option value="School of Hospitality and Tourism">School of Hospitality and Tourism</option>
                        <option value="School of Management and Marketing">School of Management and Marketing</option>
                        <option value="School of Nursing">School of Nursing</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="programFilter" class="form-label fw-semibold">
                        <i class="fas fa-graduation-cap me-2"></i>Program
                    </label>
                    <select class="form-select" id="programFilter">
                        <option value="">All Programs</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" onclick="clearFilters()">
                        <i class="fas fa-redo me-2"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="studentsTable">
                    <thead>
                        <tr>
                            <th style="width: 12%">Student ID</th>
                            <th style="width: 20%">Name</th>
                            <th style="width: 20%">Email</th>
                            <th style="width: 16%">School</th>
                            <th style="width: 16%">Program</th>
                            <th style="width: 10%" class="text-center">Events</th>
                            <th style="width: 6%" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Student pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
                <li class="page-item disabled">
                    <a class="page-link" href="#">Previous</a>
                </li>
                <li class="page-item active">
                    <a class="page-link" href="#">1</a>
                </li>
                <li class="page-item disabled">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--header-green); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Student Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentModalBody">
                    <!-- Student details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Events Modal -->
    <div class="modal fade" id="eventsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--header-green); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt me-2"></i>Student Events
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventsModalBody">
                    <!-- Events will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--header-green); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Student
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editStudentModalBody">
                    <!-- Edit form will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveStudentChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--header-green); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Add New Student
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm">
                        <div class="mb-3">
                            <label for="addStudentId" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="addStudentId" placeholder="Enter student ID"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="addStudentName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addStudentName" placeholder="Enter full name"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="addStudentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="addStudentEmail"
                                placeholder="Enter email address" required>
                        </div>
                        <div class="mb-3">
                            <label for="addStudentPassword" class="form-label">Password</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="addStudentPassword"
                                    placeholder="Enter password" required>
                                <button type="button" class="password-toggle"
                                    onclick="togglePassword('addStudentPassword')">
                                    <i class="fas fa-eye" id="addStudentPasswordToggle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="addStudentSchool" class="form-label">School</label>
                            <select class="form-select" id="addStudentSchool" required onchange="updateAddPrograms()">
                                <option value="">Select School</option>
                                <option value="School of Computing">School of Computing</option>
                                <option value="School of Accounting and Finance">School of Accounting and Finance
                                </option>
                                <option value="School of Aircraft Maintenance">School of Aircraft Maintenance</option>
                                <option value="School of Applied Science">School of Applied Science</option>
                                <option value="School of Foundation Studies">School of Foundation Studies</option>
                                <option value="School of Hospitality and Tourism">School of Hospitality and Tourism
                                </option>
                                <option value="School of Management and Marketing">School of Management and Marketing
                                </option>
                                <option value="School of Nursing">School of Nursing</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addStudentProgram" class="form-label">Program</label>
                            <select class="form-select" id="addStudentProgram" required>
                                <option value="">Select Program</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewStudent()">Add Student</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--danger); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this student? This action cannot be undone.</p>
                    <div id="deleteStudentInfo" class="bg-light p-3 rounded">
                        <!-- Student info will be shown here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Student</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Define school programs mapping (from register.js)
        const schoolPrograms = {
            "School of Accounting and Finance": [
                "Diploma in Accounting",
                "Bachelor of Finance(Honours)(Financial Technology)",
                "Bachelor of Arts(Honours) in Accounting & Finance",
                "Bachelor of Business Administration(Honours) in Finance"
            ],
            "School of Aircraft Maintenance": [
                "Diploma in Aircraft Maintenance Engineering",
                "Advanced Diploma in Aircraft Engineering Technology BEng(Hons) Aircraft Engineering"
            ],
            "School of Applied Science": [
                "Bachelor of Science (Hons) Biotechnology",
                "Master of Applied Sciences"
            ],
            "School of Computing": [
                "Diploma in Computer Science",
                "Diploma in Information Technology",
                "Bachelor in Computer Science(Honours)(Artificial Intelligence)",
                "Bachelor in Information Technology(Cybersecurity)(Honours)",
                "Bachelor in Computer Science(Honours)(Data Science)",
                "Bachelor of Information Technology(Hons)",
                "Bachelor of Information Technology(Hons)(Internet Engineering and Cloud Computing)",
                "Bachelor in Software Engineering(Honours)"
            ],
            "School of Foundation Studies": [
                "Foundation in Business",
                "Foundation in Science"
            ],
            "School of Hospitality and Tourism": [
                "Diploma in Hotel Management",
                "Diploma in Culinary Arts",
                "Bachelor of Events Management (Honours)",
                "Bachelor in Hospitality Management (Honours) with Business Management"
            ],
            "School of Management and Marketing": [
                "Diploma in Business Administration",
                "Bachelor in Business Administration (Business Analytics) with Honours",
                "Bachelor of Business Administration (Honours) in Global Logistic",
                "Bachelor of Business Administration (Honours) in Digital Marketing",
                "Bachelor of Business Administration (Honours) in Marketing",
                "Bachelor of Business Administration (Honours) in Management",
                "Bachelor of Business Administration (Honours) in International Business",
                "Bachelor of Business Administration (Honours) in Human Resource Management",
                "Bachelor of Business Administration (Honours)"
            ],
            "School of Nursing": [
                "Diploma in Nursing",
                "Bachelor of Science(Hons) in Nursing"
            ]
        };

        let studentsData = [];
        let filteredData = [];
        let currentEditingId = null;
        let currentDeletingId = null;

        // Update programs dropdown for Add Student modal
        function updateAddPrograms() {
            const schoolSelect = document.getElementById('addStudentSchool');
            const programSelect = document.getElementById('addStudentProgram');
            const selectedSchool = schoolSelect.value;

            // Clear existing options
            programSelect.innerHTML = '<option value="">Select Program</option>';

            if (selectedSchool && schoolPrograms[selectedSchool]) {
                schoolPrograms[selectedSchool].forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.textContent = program;
                    programSelect.appendChild(option);
                });
            }
        }

        // Update programs dropdown for filter
        function updateFilterPrograms() {
            const schoolFilter = document.getElementById('schoolFilter');
            const programFilter = document.getElementById('programFilter');
            const selectedSchool = schoolFilter.value;

            // Clear existing program options
            programFilter.innerHTML = '<option value="">All Programs</option>';

            if (selectedSchool && schoolPrograms[selectedSchool]) {
                // Add programs for selected school
                schoolPrograms[selectedSchool].forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.textContent = program;
                    programFilter.appendChild(option);
                });
            } else if (selectedSchool === '') {
                // If "All Schools" is selected, show all programs from all schools
                const allPrograms = [];
                Object.values(schoolPrograms).forEach(programs => {
                    programs.forEach(program => {
                        if (!allPrograms.includes(program)) {
                            allPrograms.push(program);
                        }
                    });
                });

                allPrograms.sort().forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.textContent = program;
                    programFilter.appendChild(option);
                });
            }

            // Apply filters after updating programs
            applyFilters();
        }

        // Update programs dropdown for Edit Student modal
        function updateEditPrograms(currentProgram = '') {
            const schoolSelect = document.getElementById('editStudentSchool');
            const programSelect = document.getElementById('editStudentProgram');
            const selectedSchool = schoolSelect.value;

            // Clear existing options
            programSelect.innerHTML = '<option value="">Select Program</option>';

            if (selectedSchool && schoolPrograms[selectedSchool]) {
                schoolPrograms[selectedSchool].forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.textContent = program;
                    if (program === currentProgram) {
                        option.selected = true;
                    }
                    programSelect.appendChild(option);
                });
            }
        }

        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId + 'Toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Load students from database
        function loadStudents() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_students'
            })
                .then(response => response.json())
                .then(data => {
                    studentsData = data;
                    filteredData = [...studentsData];
                    loadStudentsTable();
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    showError('Failed to load students');
                });
        }

        // Load students table
        function loadStudentsTable(data = filteredData) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No students found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            data.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="fw-semibold text-primary">${student.id}</td>
                    <td>
                        <div class="fw-semibold">${student.name}</div>
                    </td>
                    <td>
                        <small class="text-muted">${student.email}</small>
                    </td>
                    <td>
                        ${student.school}
                    </td>
                    <td>
                        <small>${student.program}</small>
                    </td>
                    <td class="text-center">
                        <div class="events-info">
                            <span class="event-badge">${student.totalEvents} Events</span>
                            <button class="btn btn-info btn-sm" onclick="showEvents('${student.id}')" title="View Events">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="action-buttons">
                            <button class="btn btn-warning btn-sm" onclick="editStudent('${student.id}')" title="Edit Student">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteStudent('${student.id}')" title="Delete Student">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Show events modal
        function showEvents(studentId) {
            const student = studentsData.find(s => s.id === studentId);
            if (!student) return;

            // Show loading state
            const modalBody = document.getElementById('eventsModalBody');
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading events...</p>
                </div>
            `;

            new bootstrap.Modal(document.getElementById('eventsModal')).show();

            // Load events from database
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_student_events&student_id=${encodeURIComponent(studentId)}`
            })
                .then(response => response.json())
                .then(events => {
                    if (events.length > 0) {
                        modalBody.innerHTML = `
                        <!-- Student Summary -->
                        <div class="events-summary">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-1"><i class="fas fa-user me-2"></i>${student.name}</h5>
                                    <p class="text-muted mb-0">Student ID: <strong>${student.id}</strong></p>
                                    <p class="text-muted mb-0">${student.program}</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-stat">
                                        <span class="stat-number">${events.length}</span>
                                        <div class="stat-label">Events Participated</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Events List -->
                        <div class="events-list">
                            <h6 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Event Participation History</h6>
                            ${events.map((event, index) => `
                                <div class="event-card">
                                    <div class="event-header">
                                        <div class="flex-grow-1">
                                            <div class="event-id-badge">${event.eventId}</div>
                                        </div>
                                    </div>
                                    <h6 class="event-title">${event.eventName}</h6>
                                    <div class="event-club">
                                        <i class="fas fa-users"></i>
                                        ${event.clubName}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                    } else {
                        modalBody.innerHTML = `
                        <!-- Student Summary -->
                        <div class="events-summary">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-1"><i class="fas fa-user me-2"></i>${student.name}</h5>
                                    <p class="text-muted mb-0">Student ID: <strong>${student.id}</strong></p>
                                    <p class="text-muted mb-0">${student.program}</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="summary-stat">
                                        <span class="stat-number">0</span>
                                        <div class="stat-label">Events Participated</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div class="empty-events">
                            <i class="fas fa-calendar-times"></i>
                            <h5>No Events Yet</h5>
                            <p class="mb-0">This student has not participated in any events yet.</p>
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error loading events:', error);
                    modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load events. Please try again.
                    </div>
                `;
                });
        }

        // Add new student
        function addNewStudent() {
            document.getElementById('addStudentForm').reset();
            new bootstrap.Modal(document.getElementById('addStudentModal')).show();
        }

        // Save new student
        function saveNewStudent() {
            const id = document.getElementById('addStudentId').value.trim();
            const name = document.getElementById('addStudentName').value.trim();
            const email = document.getElementById('addStudentEmail').value.trim();
            const password = document.getElementById('addStudentPassword').value.trim();
            const school = document.getElementById('addStudentSchool').value;
            const program = document.getElementById('addStudentProgram').value.trim();

            // Validate form
            if (!id || !name || !email || !password || !school || !program) {
                showError('Please fill in all fields.');
                return;
            }

            if (password.length < 6) {
                showError('Password must be at least 6 characters long.');
                return;
            }

            // Show loading state
            const saveBtn = document.querySelector('#addStudentModal .btn-primary');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
            saveBtn.disabled = true;

            // Send to server
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_student&student_id=${encodeURIComponent(id)}&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&school=${encodeURIComponent(school)}&program=${encodeURIComponent(program)}`
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
                        showSuccess(result.message);
                        loadStudents(); // Reload student list
                    } else {
                        showError(result.message);
                    }
                })
                .catch(error => {
                    console.error('Error adding student:', error);
                    showError('Failed to add student. Please try again.');
                })
                .finally(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        // Edit student
        function editStudent(studentId) {
            const student = studentsData.find(s => s.id === studentId);
            if (!student) return;

            currentEditingId = studentId;

            // Get full student details from server
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_student_details&student_id=${encodeURIComponent(studentId)}`
            })
                .then(response => response.json())
                .then(studentDetails => {
                    if (!studentDetails) {
                        showError('Student not found');
                        return;
                    }

                    const modalBody = document.getElementById('editStudentModalBody');
                    modalBody.innerHTML = `
                    <form id="editStudentForm">
                        <div class="mb-3">
                            <label for="editStudentId" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="editStudentId" value="${studentDetails.id}" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editStudentName" value="${studentDetails.name}" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editStudentEmail" value="${studentDetails.email}" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <div class="password-field">
                                <input type="password" class="form-control" id="editStudentPassword" placeholder="Enter new password (optional)">
                                <button type="button" class="password-toggle" onclick="togglePassword('editStudentPassword')">
                                    <i class="fas fa-eye" id="editStudentPasswordToggle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentSchool" class="form-label">School</label>
                            <select class="form-select" id="editStudentSchool" required onchange="updateEditPrograms()">
                                <option value="School of Computing">School of Computing</option>
                                <option value="School of Accounting and Finance">School of Accounting and Finance</option>
                                <option value="School of Aircraft Maintenance">School of Aircraft Maintenance</option>
                                <option value="School of Applied Science">School of Applied Science</option>
                                <option value="School of Foundation Studies">School of Foundation Studies</option>
                                <option value="School of Hospitality and Tourism">School of Hospitality and Tourism</option>
                                <option value="School of Management and Marketing">School of Management and Marketing</option>
                                <option value="School of Nursing">School of Nursing</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentProgram" class="form-label">Program</label>
                            <select class="form-select" id="editStudentProgram" required>
                                <option value="">Select Program</option>
                            </select>
                        </div>
                    </form>
                `;

                    // Set the school dropdown value and update programs
                    document.getElementById('editStudentSchool').value = studentDetails.school;
                    updateEditPrograms(studentDetails.program);

                    new bootstrap.Modal(document.getElementById('editStudentModal')).show();
                })
                .catch(error => {
                    console.error('Error loading student details:', error);
                    showError('Failed to load student details');
                });
        }

        // Save student changes
        function saveStudentChanges() {
            if (!currentEditingId) return;

            const name = document.getElementById('editStudentName').value.trim();
            const email = document.getElementById('editStudentEmail').value.trim();
            const password = document.getElementById('editStudentPassword').value.trim();
            const school = document.getElementById('editStudentSchool').value;
            const program = document.getElementById('editStudentProgram').value.trim();

            // Validate form
            if (!name || !email || !school || !program) {
                showError('Please fill in all required fields.');
                return;
            }

            if (password && password.length < 6) {
                showError('Password must be at least 6 characters long.');
                return;
            }

            // Show loading state
            const saveBtn = document.querySelector('#editStudentModal .btn-primary');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            saveBtn.disabled = true;

            // Send to server
            const params = new URLSearchParams({
                action: 'update_student',
                student_id: currentEditingId,
                name: name,
                email: email,
                school: school,
                program: program
            });

            if (password) {
                params.append('password', password);
            }

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
                        showSuccess(result.message);
                        loadStudents(); // Reload student list
                    } else {
                        showError(result.message);
                    }
                })
                .catch(error => {
                    console.error('Error updating student:', error);
                    showError('Failed to update student. Please try again.');
                })
                .finally(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }

        // Delete student
        function deleteStudent(studentId) {
            const student = studentsData.find(s => s.id === studentId);
            if (!student) return;

            currentDeletingId = studentId;
            const deleteInfo = document.getElementById('deleteStudentInfo');
            deleteInfo.innerHTML = `
                <strong>Student ID:</strong> ${student.id}<br>
                <strong>Name:</strong> ${student.name}<br>
                <strong>Email:</strong> ${student.email}<br>
                <strong>Events Participated:</strong> ${student.totalEvents}
            `;

            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }

        // Confirm delete
        function confirmDelete() {
            if (!currentDeletingId) return;

            // Show loading state
            const deleteBtn = document.querySelector('#deleteConfirmModal .btn-danger');
            const originalText = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
            deleteBtn.disabled = true;

            // Send to server
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_student&student_id=${encodeURIComponent(currentDeletingId)}`
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                        showSuccess(result.message);
                        loadStudents(); // Reload student list
                    } else {
                        showError(result.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting student:', error);
                    showError('Failed to delete student. Please try again.');
                })
                .finally(() => {
                    deleteBtn.innerHTML = originalText;
                    deleteBtn.disabled = false;
                    currentDeletingId = null;
                });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            filteredData = studentsData.filter(student =>
                student.id.toLowerCase().includes(searchTerm) ||
                student.name.toLowerCase().includes(searchTerm) ||
                student.email.toLowerCase().includes(searchTerm)
            );
            applyFilters();
        });

        // Program filter (school filter is handled by onchange="updateFilterPrograms()")
        document.getElementById('programFilter').addEventListener('change', function () {
            applyFilters();
        });

        // Apply all filters
        function applyFilters() {
            let filtered = [...filteredData];

            const schoolFilter = document.getElementById('schoolFilter').value;
            const programFilter = document.getElementById('programFilter').value;

            if (schoolFilter) {
                filtered = filtered.filter(student => student.school === schoolFilter);
            }

            if (programFilter) {
                filtered = filtered.filter(student => student.program === programFilter);
            }

            loadStudentsTable(filtered);
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('schoolFilter').value = '';
            document.getElementById('programFilter').value = '';

            // Reset program dropdown to show all programs
            updateFilterPrograms();

            filteredData = [...studentsData];
            loadStudentsTable(studentsData);
        }

        // Utility functions for showing messages
        function showSuccess(message) {
            // Create a temporary success alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 3000);
        }

        function showError(message) {
            // Create a temporary error alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize program filter with all programs
            updateFilterPrograms();
            loadStudents();
        });
    </script>
</body>

</html>