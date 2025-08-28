<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'advisormanagement';
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
        case 'add_advisor':
            $advisor_id = mysqli_real_escape_string($conn, $_POST['advisor_id']);
            $advisor_name = mysqli_real_escape_string($conn, $_POST['advisor_name']);
            $advisor_email = mysqli_real_escape_string($conn, $_POST['advisor_email']);
            $advisor_phone = mysqli_real_escape_string($conn, $_POST['advisor_phone']);
            $advisor_club = (int) $_POST['advisor_club'];
            $advisor_password = password_hash($_POST['advisor_password'], PASSWORD_DEFAULT);

            // Check if advisor ID already exists
            $check_query = "SELECT Adv_ID FROM advisor WHERE Adv_ID = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $advisor_id);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($result) > 0) {
                echo json_encode(['success' => false, 'message' => 'Advisor ID already exists']);
                exit();
            }

            $insert_query = "INSERT INTO advisor (Adv_ID, Club_ID, Adv_Name, Adv_Email, Adv_PhnNum, Adv_PSW) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sissss", $advisor_id, $advisor_club, $advisor_name, $advisor_email, $advisor_phone, $advisor_password);

            if (mysqli_stmt_execute($insert_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Advisor added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding advisor']);
            }
            exit();

        case 'update_advisor':
            $advisor_id = mysqli_real_escape_string($conn, $_POST['advisor_id']);
            $advisor_name = mysqli_real_escape_string($conn, $_POST['advisor_name']);
            $advisor_email = mysqli_real_escape_string($conn, $_POST['advisor_email']);
            $advisor_phone = mysqli_real_escape_string($conn, $_POST['advisor_phone']);
            $advisor_club = (int) $_POST['advisor_club'];

            $update_query = "UPDATE advisor SET Adv_Name = ?, Adv_Email = ?, Adv_PhnNum = ?, Club_ID = ?";
            $params = [$advisor_name, $advisor_email, $advisor_phone, $advisor_club];
            $types = "sssi";

            if (!empty($_POST['advisor_password'])) {
                $advisor_password = password_hash($_POST['advisor_password'], PASSWORD_DEFAULT);
                $update_query .= ", Adv_PSW = ?";
                $params[] = $advisor_password;
                $types .= "s";
            }

            $update_query .= " WHERE Adv_ID = ?";
            $params[] = $advisor_id;
            $types .= "s";

            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, $types, ...$params);

            if (mysqli_stmt_execute($update_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Advisor updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating advisor']);
            }
            exit();

        case 'delete_advisor':
            $advisor_id = mysqli_real_escape_string($conn, $_POST['advisor_id']);

            $delete_query = "DELETE FROM advisor WHERE Adv_ID = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "s", $advisor_id);

            if (mysqli_stmt_execute($delete_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Advisor deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting advisor']);
            }
            exit();
    }
}

// Fetch advisors with club information
$advisors_query = "
    SELECT a.Adv_ID, a.Adv_Name, a.Adv_Email, a.Adv_PhnNum, c.Club_Name, c.Club_ID
    FROM advisor a 
    LEFT JOIN club c ON a.Club_ID = c.Club_ID 
    ORDER BY a.Adv_ID
";
$advisors_result = mysqli_query($conn, $advisors_query);

// Fetch clubs for dropdown
$clubs_query = "SELECT Club_ID, Club_Name FROM club ORDER BY Club_Name";
$clubs_result = mysqli_query($conn, $clubs_query);
$clubs = [];
while ($club = mysqli_fetch_assoc($clubs_result)) {
    $clubs[] = $club;
}

// Calculate statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_advisors,
        COUNT(DISTINCT Club_ID) as clubs_managed
    FROM advisor 
    WHERE Club_ID IS NOT NULL
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
    <link href="../assets/css/admin/advisormanagement.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Advisor Management Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-user-tie me-2"></i>Advisor Management</h5>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addAdvisorModal">
                    <i class="fas fa-plus me-2"></i>Add New Advisor
                </button>
            </div>
            <div class="card-body">
                <h5><i class="fas fa-user-tie me-2"></i><?= 'Total: ' . $stats['total_advisors'] . ' Advisors' ?></h5>
                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Search Advisors</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchInput"
                                placeholder="Search by name or email...">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Filter by Club</label>
                        <select class="form-select" id="clubFilter">
                            <option value="">All Clubs</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= htmlspecialchars($club['Club_Name']) ?>">
                                    <?= htmlspecialchars($club['Club_Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status Filter</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary d-block w-100" onclick="clearFilters()">
                            <i class="fas fa-refresh me-1"></i>Clear
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="advisorsTable">
                        <thead>
                            <tr>
                                <th>Advisor ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Club</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="advisorsTableBody">
                            <?php while ($advisor = mysqli_fetch_assoc($advisors_result)): ?>
                                <tr data-advisor-id="<?= htmlspecialchars($advisor['Adv_ID']) ?>">
                                    <td><?= htmlspecialchars($advisor['Adv_ID']) ?></td>
                                    <td><?= htmlspecialchars($advisor['Adv_Name']) ?></td>
                                    <td><?= htmlspecialchars($advisor['Adv_Email']) ?></td>
                                    <td><?= htmlspecialchars($advisor['Club_Name'] ?? 'No Club Assigned') ?></td>
                                    <td><?= htmlspecialchars($advisor['Adv_PhnNum']) ?></td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                            onclick="editAdvisor('<?= htmlspecialchars($advisor['Adv_ID']) ?>')"
                                            data-bs-toggle="tooltip" title="Edit Advisor">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-info btn-sm"
                                            onclick="viewAdvisor('<?= htmlspecialchars($advisor['Adv_ID']) ?>')"
                                            data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="deleteAdvisor('<?= htmlspecialchars($advisor['Adv_ID']) ?>')"
                                            data-bs-toggle="tooltip" title="Delete Advisor">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Advisor Modal -->
    <div class="modal fade" id="addAdvisorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Advisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAdvisorForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Advisor ID</label>
                                <input type="text" class="form-control" id="advisorId" placeholder="Adv00001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="advisorName" placeholder="Dr. John Doe"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="advisorEmail"
                                    placeholder="john.doe@nilai.edu.my" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="advisorPhone" placeholder="+60123456789"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assigned Club</label>
                                <select class="form-select" id="advisorClub" required>
                                    <option value="">Select Club</option>
                                    <?php foreach ($clubs as $club): ?>
                                        <option value="<?= $club['Club_ID'] ?>"><?= htmlspecialchars($club['Club_Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="advisorPassword"
                                        placeholder="Enter password" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('advisorPassword')">
                                        <i class="fas fa-eye" id="advisorPasswordEye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="addAdvisor()">
                        <i class="fas fa-save me-1"></i>Add Advisor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Advisor Modal -->
    <div class="modal fade" id="editAdvisorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Advisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAdvisorForm">
                        <input type="hidden" id="editAdvisorIdHidden">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Advisor ID</label>
                                <input type="text" class="form-control" id="editAdvisorId" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="editAdvisorName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="editAdvisorEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="editAdvisorPhone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assigned Club</label>
                                <select class="form-select" id="editAdvisorClub" required>
                                    <option value="">Select Club</option>
                                    <?php foreach ($clubs as $club): ?>
                                        <option value="<?= $club['Club_ID'] ?>"><?= htmlspecialchars($club['Club_Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password (optional)</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="editAdvisorPassword"
                                        placeholder="Leave blank to keep current password">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('editAdvisorPassword')">
                                        <i class="fas fa-eye" id="editAdvisorPasswordEye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="updateAdvisor()">
                        <i class="fas fa-save me-1"></i>Update Advisor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Advisor Modal -->
    <div class="modal fade" id="viewAdvisorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Advisor Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Advisor ID</h6>
                            <p id="viewAdvisorId" class="mb-3"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Full Name</h6>
                            <p id="viewAdvisorName" class="mb-3"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Email</h6>
                            <p id="viewAdvisorEmail" class="mb-3"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Phone</h6>
                            <p id="viewAdvisorPhone" class="mb-3"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Assigned Club</h6>
                            <p id="viewAdvisorClub" class="mb-3"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eyeIcon = document.getElementById(inputId + 'Eye');

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Store advisors data globally
        let advisorsData = [];

        // Load advisors from database on page load
        function loadAdvisorsData() {
            advisorsData = [];
            const rows = document.querySelectorAll('#advisorsTableBody tr');
            rows.forEach(row => {
                const cells = row.cells;
                if (cells.length > 0) {
                    advisorsData.push({
                        id: cells[0].textContent.trim(),
                        name: cells[1].textContent.trim(),
                        email: cells[2].textContent.trim(),
                        club: cells[3].textContent.trim(),
                        phone: cells[4].textContent.trim(),
                        status: 'Active'
                    });
                }
            });
        }

        // Add new advisor
        function addAdvisor() {
            const form = document.getElementById('addAdvisorForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add_advisor');
            formData.append('advisor_id', document.getElementById('advisorId').value);
            formData.append('advisor_name', document.getElementById('advisorName').value);
            formData.append('advisor_email', document.getElementById('advisorEmail').value);
            formData.append('advisor_phone', document.getElementById('advisorPhone').value);
            formData.append('advisor_club', document.getElementById('advisorClub').value);
            formData.append('advisor_password', document.getElementById('advisorPassword').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        form.reset();
                        const modal = bootstrap.Modal.getInstance(document.getElementById('addAdvisorModal'));
                        modal.hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.message, 'danger');
                    }
                })
                .catch(error => {
                    showMessage('Error adding advisor', 'danger');
                    console.error('Error:', error);
                });
        }

        // Edit advisor
        function editAdvisor(advisorId) {
            const row = document.querySelector(`tr[data-advisor-id="${advisorId}"]`);
            if (!row) return;

            const cells = row.cells;

            // Populate edit form
            document.getElementById('editAdvisorIdHidden').value = advisorId;
            document.getElementById('editAdvisorId').value = advisorId;
            document.getElementById('editAdvisorName').value = cells[1].textContent.trim();
            document.getElementById('editAdvisorEmail').value = cells[2].textContent.trim();
            document.getElementById('editAdvisorPhone').value = cells[4].textContent.trim();

            // Set club selection
            const clubName = cells[3].textContent.trim();
            const clubSelect = document.getElementById('editAdvisorClub');
            for (let option of clubSelect.options) {
                if (option.textContent === clubName) {
                    option.selected = true;
                    break;
                }
            }

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editAdvisorModal'));
            modal.show();
        }

        // Update advisor
        function updateAdvisor() {
            const form = document.getElementById('editAdvisorForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_advisor');
            formData.append('advisor_id', document.getElementById('editAdvisorIdHidden').value);
            formData.append('advisor_name', document.getElementById('editAdvisorName').value);
            formData.append('advisor_email', document.getElementById('editAdvisorEmail').value);
            formData.append('advisor_phone', document.getElementById('editAdvisorPhone').value);
            formData.append('advisor_club', document.getElementById('editAdvisorClub').value);
            formData.append('advisor_password', document.getElementById('editAdvisorPassword').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editAdvisorModal'));
                        modal.hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.message, 'danger');
                    }
                })
                .catch(error => {
                    showMessage('Error updating advisor', 'danger');
                    console.error('Error:', error);
                });
        }

        // View advisor details
        function viewAdvisor(advisorId) {
            const row = document.querySelector(`tr[data-advisor-id="${advisorId}"]`);
            if (!row) return;

            const cells = row.cells;

            // Populate view modal
            document.getElementById('viewAdvisorId').textContent = cells[0].textContent.trim();
            document.getElementById('viewAdvisorName').textContent = cells[1].textContent.trim();
            document.getElementById('viewAdvisorEmail').textContent = cells[2].textContent.trim();
            document.getElementById('viewAdvisorPhone').textContent = cells[4].textContent.trim();
            document.getElementById('viewAdvisorClub').textContent = cells[3].textContent.trim();

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewAdvisorModal'));
            modal.show();
        }

        // Delete advisor
        function deleteAdvisor(advisorId) {
            const row = document.querySelector(`tr[data-advisor-id="${advisorId}"]`);
            if (!row) return;

            const advisorName = row.cells[1].textContent.trim();

            Swal.fire({
                title: 'Are you sure?',
                text: `You want to delete advisor "${advisorName}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete_advisor');
                    formData.append('advisor_id', advisorId);

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: '#25aa20'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    confirmButtonColor: '#e74c3c'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Error deleting advisor',
                                icon: 'error',
                                confirmButtonColor: '#e74c3c'
                            });
                            console.error('Error:', error);
                        });
                }
            });
        }

        // Search and filter functions
        function setupSearchAndFilter() {
            const searchInput = document.getElementById('searchInput');
            const clubFilter = document.getElementById('clubFilter');
            const statusFilter = document.getElementById('statusFilter');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const clubValue = clubFilter.value;
                const statusValue = statusFilter.value;

                const rows = document.querySelectorAll('#advisorsTableBody tr');

                rows.forEach(row => {
                    const cells = row.cells;
                    if (cells.length === 0) return;

                    const name = cells[1].textContent.toLowerCase();
                    const email = cells[2].textContent.toLowerCase();
                    const club = cells[3].textContent;
                    const status = cells[5].textContent.trim();

                    const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                    const matchesClub = !clubValue || club === clubValue;
                    const matchesStatus = !statusValue || status.includes(statusValue);

                    if (matchesSearch && matchesClub && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('input', filterTable);
            clubFilter.addEventListener('change', filterTable);
            statusFilter.addEventListener('change', filterTable);
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clubFilter').value = '';
            document.getElementById('statusFilter').value = '';

            // Show all rows
            const rows = document.querySelectorAll('#advisorsTableBody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }

        // Show success/error message
        function showMessage(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alert.style.zIndex = '9999';
            alert.innerHTML = `
                ${message}
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

        // Auto-generate advisor ID
        function generateAdvisorId() {
            // Get current max advisor ID from table
            const rows = document.querySelectorAll('#advisorsTableBody tr');
            let maxId = 0;

            rows.forEach(row => {
                const id = row.cells[0].textContent.trim();
                const match = id.match(/Adv(\d+)/);
                if (match) {
                    const num = parseInt(match[1]);
                    if (num > maxId) {
                        maxId = num;
                    }
                }
            });

            const nextId = maxId + 1;
            return 'Adv' + String(nextId).padStart(5, '0');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            loadAdvisorsData();
            setupSearchAndFilter();

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-generate advisor ID when modal opens
            document.getElementById('addAdvisorModal').addEventListener('show.bs.modal', function () {
                document.getElementById('advisorId').value = generateAdvisorId();
            });
        });

        // Reset forms when modals are hidden
        document.getElementById('addAdvisorModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('addAdvisorForm').reset();
            // Reset password visibility for add form
            const addPasswordInput = document.getElementById('advisorPassword');
            const addPasswordEye = document.getElementById('advisorPasswordEye');
            if (addPasswordInput.type === 'text') {
                addPasswordInput.type = 'password';
                addPasswordEye.classList.remove('fa-eye-slash');
                addPasswordEye.classList.add('fa-eye');
            }
        });

        document.getElementById('editAdvisorModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('editAdvisorForm').reset();
            // Reset password visibility for edit form
            const editPasswordInput = document.getElementById('editAdvisorPassword');
            const editPasswordEye = document.getElementById('editAdvisorPasswordEye');
            if (editPasswordInput.type === 'text') {
                editPasswordInput.type = 'password';
                editPasswordEye.classList.remove('fa-eye-slash');
                editPasswordEye.classList.add('fa-eye');
            }
        });
    </script>
</body>

</html>