<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        /* Club Management Specific Styles */
        .club-management-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .add-club-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .add-club-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(3, 167, 145, 0.3);
            color: white;
        }

        .club-table {
            margin-top: 2rem;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: bold;
            padding: 1rem 0.75rem;
        }

        .table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(3, 167, 145, 0.05);
            transform: translateX(5px);
        }

        .action-btn {
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .edit-btn {
            background-color: var(--warm-color);
            color: var(--primary-color);
        }

        .edit-btn:hover {
            background-color: #e8a876;
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        /* Modal Styling */
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }

        .modal-title {
            color: white;
            font-weight: bold;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-label {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid rgba(3, 167, 145, 0.2);
            border-radius: 10px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(3, 167, 145, 0.1);
        }

        .form-select {
            border: 2px solid rgba(3, 167, 145, 0.2);
            border-radius: 10px;
            padding: 0.75rem;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(3, 167, 145, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: bold;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #02967f, #6dd19b);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
        }

        .search-container {
            margin-bottom: 1.5rem;
        }

        .search-input {
            border: 2px solid rgba(3, 167, 145, 0.2);
            border-radius: 10px 0 0 10px;
            border-right: none;
        }

        .search-btn {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            color: white;
            border-radius: 0 10px 10px 0;
        }

        .club-count {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
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
                        <li>
                            <a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>
                        <li>
                            <a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                        </li>
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
                <a class="nav-link active" href="clubmanagement.php" data-section="clubs">
                    <i class="fas fa-users me-2"></i>Club Management
                </a>
                <a class="nav-link" href="advisormanagement.php" data-section="advisors">
                    <i class="fas fa-user-tie me-2"></i>Advisor Management
                </a>
                <a class="nav-link" href="coordinatormanagement.php" data-section="coordinators">
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
                <i class="fas fa-users me-3"></i>Club Management
            </h2>
        </div>

        <div class="club-management-container">
            <!-- Action Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn add-club-btn" data-bs-toggle="modal" data-bs-target="#addClubModal">
                        <i class="fas fa-plus me-2"></i>Add New Club
                    </button>
                    <span class="club-count ms-3">
                        <i class="fas fa-users me-1"></i>
                        Total Clubs: <span id="clubCount">5</span>
                    </span>
                </div>

                <!-- Search Bar -->
                <div class="search-container">
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control search-input" id="searchInput"
                            placeholder="Search clubs...">
                        <button class="btn search-btn" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Club Table -->
            <div class="club-table">
                <div class="table-responsive">
                    <table class="table table-striped" id="clubTable">
                        <thead>
                            <tr>
                                <th>Club ID</th>
                                <th>Club Name</th>
                                <th>Advisor</th>
                                <th>Total Events</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clubTableBody">
                            <tr>
                                <td>CLB001</td>
                                <td>Computer Science Society</td>
                                <td>Dr. Ahmad Rahman</td>
                                <td>12</td>
                                <td>
                                    <button class="btn action-btn edit-btn"
                                        onclick="editClub('CLB001', 'Computer Science Society', 'Dr. Ahmad Rahman', '12')">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn action-btn delete-btn"
                                        onclick="deleteClub('CLB001', 'Computer Science Society')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>CLB002</td>
                                <td>Business Club</td>
                                <td>Prof. Sarah Lee</td>
                                <td>8</td>
                                <td>
                                    <button class="btn action-btn edit-btn"
                                        onclick="editClub('CLB002', 'Business Club', 'Prof. Sarah Lee', '8')">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn action-btn delete-btn"
                                        onclick="deleteClub('CLB002', 'Business Club')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>CLB003</td>
                                <td>Drama Society</td>
                                <td>Ms. Lisa Wong</td>
                                <td>15</td>
                                <td>
                                    <button class="btn action-btn edit-btn"
                                        onclick="editClub('CLB003', 'Drama Society', 'Ms. Lisa Wong', '15')">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn action-btn delete-btn"
                                        onclick="deleteClub('CLB003', 'Drama Society')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>CLB004</td>
                                <td>Sports Club</td>
                                <td>Coach Michael Tan</td>
                                <td>20</td>
                                <td>
                                    <button class="btn action-btn edit-btn"
                                        onclick="editClub('CLB004', 'Sports Club', 'Coach Michael Tan', '20')">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn action-btn delete-btn"
                                        onclick="deleteClub('CLB004', 'Sports Club')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>CLB005</td>
                                <td>Photography Club</td>
                                <td>Mr. David Chen</td>
                                <td>6</td>
                                <td>
                                    <button class="btn action-btn edit-btn"
                                        onclick="editClub('CLB005', 'Photography Club', 'Mr. David Chen', '6')">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <button class="btn action-btn delete-btn"
                                        onclick="deleteClub('CLB005', 'Photography Club')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Club Modal -->
    <div class="modal fade" id="addClubModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Club
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addClubForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clubId" class="form-label">Club ID</label>
                                <input type="text" class="form-control" id="clubId" placeholder="e.g., CLB006" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clubName" class="form-label">Club Name</label>
                                <input type="text" class="form-control" id="clubName" placeholder="Enter club name"
                                    required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="clubAdvisor" class="form-label">Advisor</label>
                            <select class="form-select" id="clubAdvisor" required>
                                <option value="">Select Advisor</option>
                                <option value="Dr. Ahmad Rahman">Dr. Ahmad Rahman</option>
                                <option value="Prof. Sarah Lee">Prof. Sarah Lee</option>
                                <option value="Ms. Lisa Wong">Ms. Lisa Wong</option>
                                <option value="Coach Michael Tan">Coach Michael Tan</option>
                                <option value="Mr. David Chen">Mr. David Chen</option>
                                <option value="Dr. Emily Johnson">Dr. Emily Johnson</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addClub()">
                        <i class="fas fa-save me-1"></i>Add Club
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Club Modal -->
    <div class="modal fade" id="editClubModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Club Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editClubForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editClubId" class="form-label">Club ID</label>
                                <input type="text" class="form-control" id="editClubId" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editClubName" class="form-label">Club Name</label>
                                <input type="text" class="form-control" id="editClubName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editClubAdvisor" class="form-label">Advisor</label>
                                <select class="form-select" id="editClubAdvisor" required>
                                    <option value="">Select Advisor</option>
                                    <option value="Dr. Ahmad Rahman">Dr. Ahmad Rahman</option>
                                    <option value="Prof. Sarah Lee">Prof. Sarah Lee</option>
                                    <option value="Ms. Lisa Wong">Ms. Lisa Wong</option>
                                    <option value="Coach Michael Tan">Coach Michael Tan</option>
                                    <option value="Mr. David Chen">Mr. David Chen</option>
                                    <option value="Dr. Emily Johnson">Dr. Emily Johnson</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editTotalEvents" class="form-label">Total Events</label>
                                <input type="number" class="form-control" id="editTotalEvents" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editClubDescription" class="form-label">Club Description</label>
                            <textarea class="form-control" id="editClubDescription" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateClub()">
                        <i class="fas fa-save me-1"></i>Update Club
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the club "<strong id="deleteClubName"></strong>"?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                    <input type="hidden" id="deleteClubId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-1"></i>Delete Club
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let clubs = [
            { id: 'CLB001', name: 'Computer Science Society', advisor: 'Dr. Ahmad Rahman', events: 12 },
            { id: 'CLB002', name: 'Business Club', advisor: 'Prof. Sarah Lee', events: 8 },
            { id: 'CLB003', name: 'Drama Society', advisor: 'Ms. Lisa Wong', events: 15 },
            { id: 'CLB004', name: 'Sports Club', advisor: 'Coach Michael Tan', events: 20 },
            { id: 'CLB005', name: 'Photography Club', advisor: 'Mr. David Chen', events: 6 }
        ];

        // Add new club
        function addClub() {
            const clubId = document.getElementById('clubId').value;
            const clubName = document.getElementById('clubName').value;
            const clubAdvisor = document.getElementById('clubAdvisor').value;
            const totalEvents = document.getElementById('totalEvents').value;

            // Validation
            if (!clubId || !clubName || !clubAdvisor) {
                alert('Please fill in all required fields.');
                return;
            }

            // Check if club ID already exists
            if (clubs.find(club => club.id === clubId)) {
                alert('Club ID already exists. Please use a different ID.');
                return;
            }

            // Add to clubs array
            clubs.push({
                id: clubId,
                name: clubName,
                advisor: clubAdvisor,
                events: parseInt(totalEvents) || 0
            });

            // Refresh table
            refreshClubTable();
            updateClubCount();

            // Close modal and reset form
            bootstrap.Modal.getInstance(document.getElementById('addClubModal')).hide();
            document.getElementById('addClubForm').reset();

            // Show success message
            showSuccessMessage('Club added successfully!');
        }

        // Edit club
        function editClub(id, name, advisor, events) {
            document.getElementById('editClubId').value = id;
            document.getElementById('editClubName').value = name;
            document.getElementById('editClubAdvisor').value = advisor;
            document.getElementById('editTotalEvents').value = events;

            // Show modal
            new bootstrap.Modal(document.getElementById('editClubModal')).show();
        }

        // Update club
        function updateClub() {
            const clubId = document.getElementById('editClubId').value;
            const clubName = document.getElementById('editClubName').value;
            const clubAdvisor = document.getElementById('editClubAdvisor').value;
            const totalEvents = document.getElementById('editTotalEvents').value;

            // Find and update club
            const clubIndex = clubs.findIndex(club => club.id === clubId);
            if (clubIndex !== -1) {
                clubs[clubIndex] = {
                    id: clubId,
                    name: clubName,
                    advisor: clubAdvisor,
                    events: parseInt(totalEvents) || 0
                };

                // Refresh table
                refreshClubTable();

                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('editClubModal')).hide();

                // Show success message
                showSuccessMessage('Club updated successfully!');
            }
        }

        // Delete club
        function deleteClub(id, name) {
            document.getElementById('deleteClubId').value = id;
            document.getElementById('deleteClubName').textContent = name;

            // Show modal
            new bootstrap.Modal(document.getElementById('deleteClubModal')).show();
        }

        // Confirm delete
        function confirmDelete() {
            const clubId = document.getElementById('deleteClubId').value;

            // Remove from clubs array
            clubs = clubs.filter(club => club.id !== clubId);

            // Refresh table
            refreshClubTable();
            updateClubCount();

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('deleteClubModal')).hide();

            // Show success message
            showSuccessMessage('Club deleted successfully!');
        }

        // Refresh club table
        function refreshClubTable() {
            const tbody = document.getElementById('clubTableBody');
            tbody.innerHTML = '';

            clubs.forEach(club => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${club.id}</td>
                    <td>${club.name}</td>
                    <td>${club.advisor}</td>
                    <td>${club.events}</td>
                    <td>
                        <button class="btn action-btn edit-btn" onclick="editClub('${club.id}', '${club.name}', '${club.advisor}', '${club.events}')">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <button class="btn action-btn delete-btn" onclick="deleteClub('${club.id}', '${club.name}')">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Update club count
        function updateClubCount() {
            document.getElementById('clubCount').textContent = clubs.length;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#clubTableBody tr');

            rows.forEach(row => {
                const clubName = row.cells[1].textContent.toLowerCase();
                const advisor = row.cells[2].textContent.toLowerCase();
                const clubId = row.cells[0].textContent.toLowerCase();

                if (clubName.includes(searchTerm) || advisor.includes(searchTerm) || clubId.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Show success message
        function showSuccessMessage(message) {
            // Create alert element
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alert.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            // Add to body
            document.body.appendChild(alert);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 3000);
        }

        // Generate next club ID
        function generateNextClubId() {
            const existingIds = clubs.map(club => club.id);
            let nextNum = 1;
            let nextId;

            do {
                nextId = `CLB${String(nextNum).padStart(3, '0')}`;
                nextNum++;
            } while (existingIds.includes(nextId));

            return nextId;
        }

        // Auto-generate club ID when modal opens
        document.getElementById('addClubModal').addEventListener('show.bs.modal', function () {
            document.getElementById('clubId').value = generateNextClubId();
        });

        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], select[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // Enhanced add club with validation
        function addClub() {
            const clubId = document.getElementById('clubId').value;
            const clubName = document.getElementById('clubName').value.trim();
            const clubAdvisor = document.getElementById('clubAdvisor').value;

            // Validation
            if (!clubName || !clubAdvisor) {
                alert('Please fill in all required fields.');
                return;
            }

            // Check if club ID already exists
            if (clubs.find(club => club.id === clubId)) {
                alert('Club ID already exists. Please use a different ID.');
                return;
            }

            // Check if club name already exists
            if (clubs.find(club => club.name.toLowerCase() === clubName.toLowerCase())) {
                alert('Club name already exists. Please use a different name.');
                document.getElementById('clubName').focus();
                return;
            }

            // Add to clubs array with default 0 events
            clubs.push({
                id: clubId,
                name: clubName,
                advisor: clubAdvisor,
                events: 0
            });

            // Sort clubs by ID
            clubs.sort((a, b) => a.id.localeCompare(b.id));

            // Refresh table
            refreshClubTable();
            updateClubCount();

            // Close modal and reset form
            bootstrap.Modal.getInstance(document.getElementById('addClubModal')).hide();
            document.getElementById('addClubForm').reset();

            // Show success message
            showSuccessMessage('Club added successfully!');
        }

        // Enhanced update club with validation
        function updateClub() {
            if (!validateForm('editClubForm')) {
                alert('Please fill in all required fields.');
                return;
            }

            const clubId = document.getElementById('editClubId').value;
            const clubName = document.getElementById('editClubName').value;
            const clubAdvisor = document.getElementById('editClubAdvisor').value;
            const totalEvents = document.getElementById('editTotalEvents').value;

            // Check if club name already exists (excluding current club)
            const existingClub = clubs.find(club => club.name.toLowerCase() === clubName.toLowerCase() && club.id !== clubId);
            if (existingClub) {
                alert('Club name already exists. Please use a different name.');
                document.getElementById('editClubName').focus();
                return;
            }

            // Find and update club
            const clubIndex = clubs.findIndex(club => club.id === clubId);
            if (clubIndex !== -1) {
                clubs[clubIndex] = {
                    id: clubId,
                    name: clubName,
                    advisor: clubAdvisor,
                    events: parseInt(totalEvents) || 0
                };

                // Refresh table
                refreshClubTable();

                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('editClubModal')).hide();

                // Show success message
                showSuccessMessage('Club updated successfully!');
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            updateClubCount();

            // Add keyboard shortcuts
            document.addEventListener('keydown', function (e) {
                // Ctrl + N to add new club
                if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    new bootstrap.Modal(document.getElementById('addClubModal')).show();
                }

                // Escape to close modals
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(modal => {
                        bootstrap.Modal.getInstance(modal).hide();
                    });
                }
            });
        });

        // Export clubs data (for future PHP integration)
        function exportClubsData() {
            return {
                clubs: clubs,
                count: clubs.length,
                lastUpdated: new Date().toISOString()
            };
        }

        // Import clubs data (for future PHP integration)
        function importClubsData(data) {
            if (data && data.clubs) {
                clubs = data.clubs;
                refreshClubTable();
                updateClubCount();
            }
        }