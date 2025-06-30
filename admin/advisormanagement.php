<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Management - Nilai University CMS</title>
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

        /* Card Styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1.2rem 1.5rem;
        }

        .card-header h5 {
            margin: 0;
            font-weight: bold;
        }

        /* Button Styling */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(3, 167, 145, 0.4);
        }

        .btn-warning {
            background: var(--warm-color);
            border: none;
            color: var(--primary-color);
            font-weight: bold;
        }

        .btn-danger {
            background: #e74c3c;
            border: none;
        }

        .btn-success {
            background: var(--secondary-color);
            border: none;
            color: var(--primary-color);
            font-weight: bold;
        }

        /* Table Styling */
        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: bold;
            padding: 1rem;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(3, 167, 145, 0.05);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 15px 15px 0 0;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(3, 167, 145, 0.25);
        }

        /* Action Buttons */
        .btn-sm {
            padding: 0.4rem 0.8rem;
            margin: 0 0.2rem;
            border-radius: 6px;
        }

        /* Search and Filter */
        .search-filter-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stats-label {
            color: #6c757d;
            font-weight: 500;
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
                <a class="nav-link active" href="advisormanagement.php" data-section="advisors">
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
            <h2><i class="fas fa-user-tie me-3"></i>Advisor Management</h2>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="totalAdvisors">12</div>
                    <div class="stats-label">Total Advisors</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="activeAdvisors">10</div>
                    <div class="stats-label">Active Advisors</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="clubsManaged">15</div>
                    <div class="stats-label">Clubs Managed</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="newThisMonth">3</div>
                    <div class="stats-label">New This Month</div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-container">
            <div class="row">
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
                        <option value="Computer Science Club">Computer Science Club</option>
                        <option value="Business Club">Business Club</option>
                        <option value="Engineering Society">Engineering Society</option>
                        <option value="Arts & Design Club">Arts & Design Club</option>
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
        </div>

        <!-- Advisor Management Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-user-tie me-2"></i>Advisor List</h5>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addAdvisorModal">
                    <i class="fas fa-plus me-2"></i>Add New Advisor
                </button>
            </div>
            <div class="card-body">
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
                            <!-- Sample data - replace with PHP MySQL data -->
                            <tr>
                                <td>ADV001</td>
                                <td>Dr. Sarah Johnson</td>
                                <td>sarah.johnson@nilai.edu.my</td>
                                <td>Computer Science Club</td>
                                <td>+60123456789</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editAdvisor('ADV001')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="viewAdvisor('ADV001')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteAdvisor('ADV001')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>ADV002</td>
                                <td>Prof. Michael Chen</td>
                                <td>michael.chen@nilai.edu.my</td>
                                <td>Business Club</td>
                                <td>+60187654321</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editAdvisor('ADV002')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="viewAdvisor('ADV002')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteAdvisor('ADV002')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>ADV003</td>
                                <td>Dr. Lisa Wong</td>
                                <td>lisa.wong@nilai.edu.my</td>
                                <td>Engineering Society</td>
                                <td>+60198765432</td>
                                <td><span class="badge bg-warning">Inactive</span></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editAdvisor('ADV003')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="viewAdvisor('ADV003')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteAdvisor('ADV003')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
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
                                <input type="text" class="form-control" id="advisorId" placeholder="ADV001" required>
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
                                    <option value="Computer Science Club">Computer Science Club</option>
                                    <option value="Business Club">Business Club</option>
                                    <option value="Engineering Society">Engineering Society</option>
                                    <option value="Arts & Design Club">Arts & Design Club</option>
                                    <option value="Sports Club">Sports Club</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" id="advisorPassword"
                                    placeholder="Enter password" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="advisorDepartment"
                                placeholder="Faculty of Computer Science">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio/Notes</label>
                            <textarea class="form-control" id="advisorBio" rows="3"
                                placeholder="Additional information about the advisor..."></textarea>
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
                                    <option value="Computer Science Club">Computer Science Club</option>
                                    <option value="Business Club">Business Club</option>
                                    <option value="Engineering Society">Engineering Society</option>
                                    <option value="Arts & Design Club">Arts & Design Club</option>
                                    <option value="Sports Club">Sports Club</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password (optional)</label>
                                <input type="password" class="form-control" id="editAdvisorPassword"
                                    placeholder="Leave blank to keep current password">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="editAdvisorDepartment">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio/Notes</label>
                            <textarea class="form-control" id="editAdvisorBio" rows="3"></textarea>
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
                        <div class="col-md-6">
                            <h6 class="text-muted">Department</h6>
                            <p id="viewAdvisorDepartment" class="mb-3"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Bio/Notes</h6>
                            <p id="viewAdvisorBio" class="mb-3"></p>
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
    <script>
        // Sample data for demonstration
        let advisors = [
            {
                id: 'ADV001',
                name: 'Dr. Sarah Johnson',
                email: 'sarah.johnson@nilai.edu.my',
                club: 'Computer Science Club',
                phone: '+60123456789',
                status: 'Active',
                department: 'Faculty of Computer Science',
                bio: 'Expert in software engineering and database systems.'
            },
            {
                id: 'ADV002',
                name: 'Prof. Michael Chen',
                email: 'michael.chen@nilai.edu.my',
                club: 'Business Club',
                phone: '+60187654321',
                status: 'Active',
                department: 'Faculty of Business',
                bio: 'Specializes in business management and entrepreneurship.'
            },
            {
                id: 'ADV003',
                name: 'Dr. Lisa Wong',
                email: 'lisa.wong@nilai.edu.my',
                club: 'Engineering Society',
                phone: '+60198765432',
                status: 'Inactive',
                department: 'Faculty of Engineering',
                bio: 'Research focus on mechanical engineering and automation.'
            }
        ];

        // Load advisors table
        function loadAdvisorsTable() {
            const tbody = document.getElementById('advisorsTableBody');
            tbody.innerHTML = '';

            advisors.forEach(advisor => {
                const statusBadge = advisor.status === 'Active' ?
                    '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-warning">Inactive</span>';

                tbody.innerHTML += `
                    <tr>
                        <td>${advisor.id}</td>
                        <td>${advisor.name}</td>
                        <td>${advisor.email}</td>
                        <td>${advisor.club}</td>
                        <td>${advisor.phone}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editAdvisor('${advisor.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-info btn-sm" onclick="viewAdvisor('${advisor.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteAdvisor('${advisor.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        // Add new advisor
        function addAdvisor() {
            const form = document.getElementById('addAdvisorForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const newAdvisor = {
                id: document.getElementById('advisorId').value,
                name: document.getElementById('advisorName').value,
                email: document.getElementById('advisorEmail').value,
                club: document.getElementById('advisorClub').value,
                phone: document.getElementById('advisorPhone').value,
                status: 'Active',
                department: document.getElementById('advisorDepartment').value,
                bio: document.getElementById('advisorBio').value
            };

            // Check if advisor ID already exists
            if (advisors.some(advisor => advisor.id === newAdvisor.id)) {
                alert('Advisor ID already exists! Please use a different ID.');
                return;
            }

            advisors.push(newAdvisor);
            loadAdvisorsTable();
            updateStats();

            // Reset form and close modal
            form.reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addAdvisorModal'));
            modal.hide();

            showSuccessMessage('Advisor added successfully!');
        }

        // Edit advisor
        function editAdvisor(advisorId) {
            const advisor = advisors.find(a => a.id === advisorId);
            if (!advisor) return;

            // Populate edit form
            document.getElementById('editAdvisorIdHidden').value = advisor.id;
            document.getElementById('editAdvisorId').value = advisor.id;
            document.getElementById('editAdvisorName').value = advisor.name;
            document.getElementById('editAdvisorEmail').value = advisor.email;
            document.getElementById('editAdvisorPhone').value = advisor.phone;
            document.getElementById('editAdvisorClub').value = advisor.club;
            document.getElementById('editAdvisorDepartment').value = advisor.department || '';
            document.getElementById('editAdvisorBio').value = advisor.bio || '';

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

            const advisorId = document.getElementById('editAdvisorIdHidden').value;
            const advisorIndex = advisors.findIndex(a => a.id === advisorId);

            if (advisorIndex === -1) return;

            // Update advisor data
            advisors[advisorIndex] = {
                ...advisors[advisorIndex],
                name: document.getElementById('editAdvisorName').value,
                email: document.getElementById('editAdvisorEmail').value,
                phone: document.getElementById('editAdvisorPhone').value,
                club: document.getElementById('editAdvisorClub').value,
                department: document.getElementById('editAdvisorDepartment').value,
                bio: document.getElementById('editAdvisorBio').value
            };

            loadAdvisorsTable();

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editAdvisorModal'));
            modal.hide();

            showSuccessMessage('Advisor updated successfully!');
        }

        // View advisor details
        function viewAdvisor(advisorId) {
            const advisor = advisors.find(a => a.id === advisorId);
            if (!advisor) return;

            // Populate view modal
            document.getElementById('viewAdvisorId').textContent = advisor.id;
            document.getElementById('viewAdvisorName').textContent = advisor.name;
            document.getElementById('viewAdvisorEmail').textContent = advisor.email;
            document.getElementById('viewAdvisorPhone').textContent = advisor.phone;
            document.getElementById('viewAdvisorClub').textContent = advisor.club;
            document.getElementById('viewAdvisorDepartment').textContent = advisor.department || 'Not specified';
            document.getElementById('viewAdvisorBio').textContent = advisor.bio || 'No additional information';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewAdvisorModal'));
            modal.show();
        }

        // Delete advisor
        function deleteAdvisor(advisorId) {
            const advisor = advisors.find(a => a.id === advisorId);
            if (!advisor) return;

            if (confirm(`Are you sure you want to delete advisor "${advisor.name}"? This action cannot be undone.`)) {
                advisors = advisors.filter(a => a.id !== advisorId);
                loadAdvisorsTable();
                updateStats();
                showSuccessMessage('Advisor deleted successfully!');
            }
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

                const filteredAdvisors = advisors.filter(advisor => {
                    const matchesSearch = advisor.name.toLowerCase().includes(searchTerm) ||
                        advisor.email.toLowerCase().includes(searchTerm);
                    const matchesClub = !clubValue || advisor.club === clubValue;
                    const matchesStatus = !statusValue || advisor.status === statusValue;

                    return matchesSearch && matchesClub && matchesStatus;
                });

                displayFilteredAdvisors(filteredAdvisors);
            }

            searchInput.addEventListener('input', filterTable);
            clubFilter.addEventListener('change', filterTable);
            statusFilter.addEventListener('change', filterTable);
        }

        function displayFilteredAdvisors(filteredAdvisors) {
            const tbody = document.getElementById('advisorsTableBody');
            tbody.innerHTML = '';

            filteredAdvisors.forEach(advisor => {
                const statusBadge = advisor.status === 'Active' ?
                    '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-warning">Inactive</span>';

                tbody.innerHTML += `
                    <tr>
                        <td>${advisor.id}</td>
                        <td>${advisor.name}</td>
                        <td>${advisor.email}</td>
                        <td>${advisor.club}</td>
                        <td>${advisor.phone}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editAdvisor('${advisor.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-info btn-sm" onclick="viewAdvisor('${advisor.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteAdvisor('${advisor.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('clubFilter').value = '';
            document.getElementById('statusFilter').value = '';
            loadAdvisorsTable();
        }

        // Update statistics
        function updateStats() {
            const totalAdvisors = advisors.length;
            const activeAdvisors = advisors.filter(a => a.status === 'Active').length;
            const uniqueClubs = [...new Set(advisors.map(a => a.club))].length;

            document.getElementById('totalAdvisors').textContent = totalAdvisors;
            document.getElementById('activeAdvisors').textContent = activeAdvisors;
            document.getElementById('clubsManaged').textContent = uniqueClubs;
            document.getElementById('newThisMonth').textContent = '3'; // This would come from your backend
        }

        // Show success message
        function showSuccessMessage(message) {
            // Create a temporary alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            loadAdvisorsTable();
            setupSearchAndFilter();
            updateStats();
        });

        // Auto-generate advisor ID
        document.getElementById('advisorName').addEventListener('input', function () {
            const name = this.value;
            if (name.length > 0) {
                // Generate ID based on name (you can customize this logic)
                const prefix = 'ADV';
                const number = String(advisors.length + 1).padStart(3, '0');
                document.getElementById('advisorId').value = prefix + number;
            }
        });
    </script>
</body>

</html>