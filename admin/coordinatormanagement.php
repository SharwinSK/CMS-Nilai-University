<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'usermanagement';

// Check if user is logged in and is an admin
if (!isset($_SESSION['Admin_ID']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/adminlogin.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_coordinator') {
        $coord_id = mysqli_real_escape_string($conn, $_POST['coordinator_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if Coordinator ID already exists
        $check_id = "SELECT Coor_ID FROM coordinator WHERE Coor_ID = ?";
        $check_id_stmt = mysqli_prepare($conn, $check_id);
        mysqli_stmt_bind_param($check_id_stmt, "s", $coord_id);
        mysqli_stmt_execute($check_id_stmt);
        $check_id_result = mysqli_stmt_get_result($check_id_stmt);

        if (mysqli_num_rows($check_id_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Coordinator ID already exists']);
            exit();
        }

        // Check if email already exists
        $check_email = "SELECT Coor_Email FROM coordinator WHERE Coor_Email = ?";
        $check_stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }

        // Insert new coordinator
        $insert_query = "INSERT INTO coordinator (Coor_ID, Coor_Name, Coor_Email, Coor_PhnNum, Coor_PSW) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sssss", $coord_id, $name, $email, $phone, $hashed_password);

        if (mysqli_stmt_execute($insert_stmt)) {
            echo json_encode(['success' => true, 'message' => 'Coordinator added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding coordinator']);
        }
        exit();
    }

    if ($action === 'edit_coordinator') {
        $coord_id = $_POST['coordinator_id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists (excluding current coordinator)
        $check_email = "SELECT Coor_Email FROM coordinator WHERE Coor_Email = ? AND Coor_ID != ?";
        $check_stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($check_stmt, "ss", $email, $coord_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }

        // Update coordinator
        $update_query = "UPDATE coordinator SET Coor_Name = ?, Coor_Email = ?, Coor_PhnNum = ?, Coor_PSW = ? WHERE Coor_ID = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssss", $name, $email, $phone, $hashed_password, $coord_id);

        if (mysqli_stmt_execute($update_stmt)) {
            echo json_encode(['success' => true, 'message' => 'Coordinator updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating coordinator']);
        }
        exit();
    }

    if ($action === 'delete_coordinator') {
        $coord_id = $_POST['coordinator_id'];

        // Delete coordinator
        $delete_query = "DELETE FROM coordinator WHERE Coor_ID = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "s", $coord_id);

        if (mysqli_stmt_execute($delete_stmt)) {
            echo json_encode(['success' => true, 'message' => 'Coordinator deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting coordinator']);
        }
        exit();
    }
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

// Fetch all coordinators
$coordinators_query = "SELECT * FROM coordinator ORDER BY Coor_ID ASC";
$coordinators_result = mysqli_query($conn, $coordinators_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Management - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
    <link href="../assets/css/admin/coormanage.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="management-card fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0" style="color: var(--header-green);">
                    <i class="fas fa-user-cog me-3"></i>Coordinator Management
                </h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCoordinatorModal">
                    <i class="fas fa-plus me-2"></i>Add Coordinator
                </button>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" id="searchInput"
                        placeholder="🔍 Search coordinators...">
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">Total Coordinators: <span
                            id="totalCount"><?= mysqli_num_rows($coordinators_result) ?></span></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="coordinatorTable">
                    <thead>
                        <tr>
                            <th>Coordinator ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($coordinator = mysqli_fetch_assoc($coordinators_result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($coordinator['Coor_ID']) ?></td>
                                <td><?= htmlspecialchars($coordinator['Coor_Name']) ?></td>
                                <td><?= htmlspecialchars($coordinator['Coor_Email']) ?></td>
                                <td><?= htmlspecialchars($coordinator['Coor_PhnNum']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning btn-sm"
                                            onclick="editCoordinator('<?= $coordinator['Coor_ID'] ?>', '<?= htmlspecialchars($coordinator['Coor_Name']) ?>', '<?= htmlspecialchars($coordinator['Coor_Email']) ?>', '<?= htmlspecialchars($coordinator['Coor_PhnNum']) ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="deleteCoordinator('<?= $coordinator['Coor_ID'] ?>')">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Coordinator Modal -->
    <div class="modal fade" id="addCoordinatorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Coordinator
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCoordinatorForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="coordinator_id" class="form-label">Coordinator ID</label>
                            <input type="text" class="form-control" id="coordinator_id" name="coordinator_id" required
                                placeholder="e.g. Cor00502">
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Coordinator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Coordinator Modal -->
    <div class="modal fade" id="editCoordinatorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Coordinator
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCoordinatorForm">
                    <div class="modal-body">
                        <input type="hidden" id="edit_coordinator_id" name="coordinator_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Coordinator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCoordinatorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-trash me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this coordinator? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form id="deleteCoordinatorForm">
                        <input type="hidden" id="delete_coordinator_id" name="coordinator_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            updateTotalCount();

            // Add search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', filterTable);
        });

        // Edit coordinator function
        function editCoordinator(id, name, email, phone) {
            document.getElementById('edit_coordinator_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_password').value = '';

            var editModal = new bootstrap.Modal(document.getElementById('editCoordinatorModal'));
            editModal.show();
        }

        // Delete coordinator function
        function deleteCoordinator(id) {
            document.getElementById('delete_coordinator_id').value = id;

            var deleteModal = new bootstrap.Modal(document.getElementById('deleteCoordinatorModal'));
            deleteModal.show();
        }

        // Form submission handlers
        document.getElementById('addCoordinatorForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add_coordinator');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addCoordinatorModal')).hide();
                        showSuccessMessage(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorMessage(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while adding the coordinator.');
                });
        });

        document.getElementById('editCoordinatorForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'edit_coordinator');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editCoordinatorModal')).hide();
                        showSuccessMessage(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorMessage(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while updating the coordinator.');
                });
        });

        document.getElementById('deleteCoordinatorForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'delete_coordinator');

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('deleteCoordinatorModal')).hide();
                        showSuccessMessage(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorMessage(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while deleting the coordinator.');
                });
        });

        // Search/Filter function
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('coordinatorTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                // Search through all cells except the last one (actions)
                for (let j = 0; j < cells.length - 1; j++) {
                    if (cells[j].textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }

            updateTotalCount();
        }

        // Update total count
        function updateTotalCount() {
            const table = document.getElementById('coordinatorTable');
            const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
            document.getElementById('totalCount').textContent = visibleRows.length;
        }

        // Success message function
        function showSuccessMessage(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }

        // Error message function
        function showErrorMessage(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }

        // Smooth animations for table rows
        const tableRows = document.querySelectorAll('#coordinatorTable tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.1}s`;
            row.classList.add('fade-in');
        });
    </script>
</body>

</html>