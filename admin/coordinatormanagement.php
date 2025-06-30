<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Management - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #03a791;
            --secondary-color: #81e7af;
            --accent-color: #e9f5be;
            --warm-color: #f1ba88;
            --light-bg: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, var(--accent-color), var(--light-bg));
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling */
        .offcanvas-start {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            width: 280px;
        }

        .offcanvas-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.5rem;
        }

        .offcanvas-title {
            color: white;
            font-weight: bold;
            font-size: 1.4rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
            transform: translateX(5px);
        }

        .nav-link.active {
            background-color: var(--warm-color);
            color: var(--primary-color) !important;
            font-weight: bold;
        }

        /* Content Styling */
        .main-content {
            padding: 2rem;
            margin-top: 1rem;
        }

        .content-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .content-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-weight: bold;
        }

        .management-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warm-color);
            border: none;
            border-radius: 20px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-warning:hover {
            background: #e8a76f;
            transform: translateY(-1px);
        }

        .btn-danger {
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(3, 167, 145, 0.1);
            transform: scale(1.01);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .modal-header.bg-danger {
            background: linear-gradient(135deg, #dc3545, #c82333) !important;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(3, 167, 145, 0.25);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .fade-in {
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

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }

        .search-container {
            background: rgba(3, 167, 145, 0.1);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .search-input {
            border: none;
            background: white;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(3, 167, 145, 0.25);
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg"
        style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#adminSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand text-white fw-bold" href="#">
                <i class="fas fa-university me-2"></i>Nilai University CMS
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Offcanvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="adminSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <i class="fas fa-tachometer-alt me-2"></i>Admin Panel
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php" data-section="dashboard">
                    <i class="fas fa-home me-2"></i>Admin Dashboard
                </a>
                <a class="nav-link" href="eventmanagement.php" data-section="events">
                    <i class="fas fa-calendar-alt me-2"></i>Event Management
                </a>
                <a class="nav-link" href="clubmanagement.php" data-section="clubs">
                    <i class="fas fa-users me-2"></i>Club Management
                </a>
                <a class="nav-link" href="advisormanagement.php" data-section="advisors">
                    <i class="fas fa-user-tie me-2"></i>Advisor Management
                </a>
                <a class="nav-link active" href="coordinatormanagement.php" data-section="coordinators">
                    <i class="fas fa-user-cog me-2"></i>Coordinator Management
                </a>
                <a class="nav-link" href="usermanagement.php" data-section="users">
                    <i class="fas fa-user-friends me-2"></i>User Management
                </a>
                <a class="nav-link" href="reportexport.php" data-section="reports">
                    <i class="fas fa-chart-bar me-2"></i>Report & Export
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <h2>
                <i class="fas fa-user-cog me-3"></i>Coordinator Management
            </h2>
        </div>

        <!-- Search Container -->
        <div class="search-container">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="form-control search-input" id="searchInput"
                        placeholder="🔍 Search coordinators...">
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">Total Coordinators: <span id="totalCount">0</span></span>
                </div>
            </div>
        </div>

        <div class="management-card fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-list me-2"></i>Coordinators List
                </h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCoordinatorModal">
                    <i class="fas fa-plus me-2"></i>Add Coordinator
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="coordinatorTable">
                    <thead>
                        <tr>
                            <th>Coordinator ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample data - Replace with PHP generated content -->
                        <tr>
                            <td>001</td>
                            <td>John Doe</td>
                            <td>john.doe@nilai.edu.my</td>
                            <td>+60123456789</td>
                            <td>password123</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm"
                                        onclick="editCoordinator(1, 'John Doe', 'john.doe@nilai.edu.my', '+60123456789', 'password123')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCoordinator(1)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>002</td>
                            <td>Jane Smith</td>
                            <td>jane.smith@nilai.edu.my</td>
                            <td>+60198765432</td>
                            <td>secure456</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm"
                                        onclick="editCoordinator(2, 'Jane Smith', 'jane.smith@nilai.edu.my', '+60198765432', 'secure456')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCoordinator(2)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>003</td>
                            <td>Ahmad Rahman</td>
                            <td>ahmad.rahman@nilai.edu.my</td>
                            <td>+60187654321</td>
                            <td>admin789</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm"
                                        onclick="editCoordinator(3, 'Ahmad Rahman', 'ahmad.rahman@nilai.edu.my', '+60187654321', 'admin789')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCoordinator(3)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
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
                            <input type="text" class="form-control" id="password" name="password" required>
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
                            <label for="edit_password" class="form-label">Password</label>
                            <input type="text" class="form-control" id="edit_password" name="password" required>
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
        function editCoordinator(id, name, email, phone, password) {
            document.getElementById('edit_coordinator_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_password').value = password;

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

            // Get form data
            const formData = new FormData(this);

            // Here you would typically send the data to your PHP script
            console.log('Adding coordinator:', Object.fromEntries(formData));

            // Close modal and show success message
            bootstrap.Modal.getInstance(document.getElementById('addCoordinatorModal')).hide();
            showSuccessMessage('Coordinator added successfully!');

            // Reset form
            this.reset();
        });

        document.getElementById('editCoordinatorForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);

            // Here you would typically send the data to your PHP script
            console.log('Editing coordinator:', Object.fromEntries(formData));

            // Close modal and show success message
            bootstrap.Modal.getInstance(document.getElementById('editCoordinatorModal')).hide();
            showSuccessMessage('Coordinator updated successfully!');
        });

        document.getElementById('deleteCoordinatorForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const coordinatorId = document.getElementById('delete_coordinator_id').value;

            // Here you would typically send the data to your PHP script
            console.log('Deleting coordinator ID:', coordinatorId);

            // Close modal and show success message
            bootstrap.Modal.getInstance(document.getElementById('deleteCoordinatorModal')).hide();
            showSuccessMessage('Coordinator deleted successfully!');
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

        // Smooth animations for table rows
        const tableRows = document.querySelectorAll('#coordinatorTable tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.1}s`;
            row.classList.add('fade-in');
        });
    </script>
</body>

</html>