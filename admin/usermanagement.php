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

// Get user statistics
function getUserStats($conn)
{
    $stats = [];

    // Count students
    $student_query = "SELECT COUNT(*) as count FROM student";
    $student_result = mysqli_query($conn, $student_query);
    $stats['students'] = mysqli_fetch_assoc($student_result)['count'];

    // Count advisors
    $advisor_query = "SELECT COUNT(*) as count FROM advisor";
    $advisor_result = mysqli_query($conn, $advisor_query);
    $stats['advisors'] = mysqli_fetch_assoc($advisor_result)['count'];

    // Count coordinators
    $coordinator_query = "SELECT COUNT(*) as count FROM coordinator";
    $coordinator_result = mysqli_query($conn, $coordinator_query);
    $stats['coordinators'] = mysqli_fetch_assoc($coordinator_result)['count'];

    // Count admins
    $admin_query = "SELECT COUNT(*) as count FROM admin";
    $admin_result = mysqli_query($conn, $admin_query);
    $stats['admins'] = mysqli_fetch_assoc($admin_result)['count'];

    // Total users
    $stats['total'] = $stats['students'] + $stats['advisors'] + $stats['coordinators'] + $stats['admins'];

    return $stats;
}

// Get additional statistics
function getAdditionalStats($conn)
{
    $additional_stats = [];

    // Count unique schools from students
    $schools_query = "SELECT COUNT(DISTINCT Stu_School) as count FROM student";
    $schools_result = mysqli_query($conn, $schools_query);
    $additional_stats['schools'] = mysqli_fetch_assoc($schools_result)['count'];

    // Count clubs assigned to advisors
    $clubs_query = "SELECT COUNT(DISTINCT Club_ID) as count FROM advisor WHERE Club_ID IS NOT NULL";
    $clubs_result = mysqli_query($conn, $clubs_query);
    $additional_stats['clubs_assigned'] = mysqli_fetch_assoc($clubs_result)['count'];

    // Count events managed (assuming events managed by coordinators based on approval status)
    $events_query = "SELECT COUNT(*) as count FROM events WHERE Status_ID = 5"; // Approved by Coordinator
    $events_result = mysqli_query($conn, $events_query);
    $additional_stats['events_managed'] = mysqli_fetch_assoc($events_result)['count'];

    return $additional_stats;
}

$user_stats = getUserStats($conn);
$additional_stats = getAdditionalStats($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
    <link href="../assets/css/admin/usermanage.css?v=<?= time() ?>" rel="stylesheet">

</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-users-cog me-3"></i>User Management System</h1>
                    <p class="mb-0">Manage students, advisors, and coordinators in your institution</p>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats">
                    <h4 class="mb-4"><i class="fas fa-chart-line me-2"></i>System Overview</h4>
                    <div class="stats-grid">
                        <div class="stat-card total-users">
                            <div class="stat-number"><?= $user_stats['total'] ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-card active-students">
                            <div class="stat-number"><?= $user_stats['students'] ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                        <div class="stat-card active-advisors">
                            <div class="stat-number"><?= $user_stats['advisors'] ?></div>
                            <div class="stat-label">Advisors</div>
                        </div>
                        <div class="stat-card active-coordinators">
                            <div class="stat-number"><?= $user_stats['coordinators'] ?></div>
                            <div class="stat-label">Coordinators</div>
                        </div>
                    </div>
                </div>

                <!-- Management Cards -->
                <div class="content-container">
                    <h4 class="mb-4"><i class="fas fa-cogs me-2"></i>Management Modules</h4>

                    <div class="management-cards">
                        <!-- Student Management Card -->
                        <div class="management-card student-card"
                            onclick="navigateToPage('../admin/studentmanagement.php')">
                            <div class="card-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="card-title">Student Management</div>
                            <div class="card-description">
                                Manage student accounts and profiles.
                            </div>
                            <div class="card-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $user_stats['students'] ?></div>
                                    <div class="stat-label">Active Students</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $additional_stats['schools'] ?></div>
                                    <div class="stat-label">Schools</div>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-manage btn-student"
                                    onclick="event.stopPropagation(); navigateToPage('../admin/studentmanagement.php')">
                                    <i class="fas fa-arrow-right me-2"></i>Manage Students
                                </button>
                            </div>
                        </div>

                        <!-- Advisor Management Card -->
                        <div class="management-card advisor-card"
                            onclick="navigateToPage('../admin/advisormanagement.php')">
                            <div class="card-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="card-title">Advisor Management</div>
                            <div class="card-description">
                                Oversee advisor accounts, assign clubs.
                            </div>
                            <div class="card-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $user_stats['advisors'] ?></div>
                                    <div class="stat-label">Active Advisors</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $additional_stats['clubs_assigned'] ?></div>
                                    <div class="stat-label">Clubs Assigned</div>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-manage btn-advisor"
                                    onclick="event.stopPropagation(); navigateToPage('../admin/advisormanagement.php')">
                                    <i class="fas fa-arrow-right me-2"></i>Manage Advisors
                                </button>
                            </div>
                        </div>

                        <!-- Coordinator Management Card -->
                        <div class="management-card coordinator-card"
                            onclick="navigateToPage('../admin/coordinatormanagement.php')">
                            <div class="card-icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <div class="card-title">Coordinator Management</div>
                            <div class="card-description">
                                Manage coordinator accounts with elevated permissions.
                            </div>
                            <div class="card-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $user_stats['coordinators'] ?></div>
                                    <div class="stat-label">Active Coordinators</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $additional_stats['events_managed'] ?></div>
                                    <div class="stat-label">Events Managed</div>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-manage btn-coordinator"
                                    onclick="event.stopPropagation(); navigateToPage('../admin/coordinatormanagement.php')">
                                    <i class="fas fa-arrow-right me-2"></i>Manage Coordinators
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Actions -->
                <!-- Floating Action Buttons -->
                <div class="floating-actions">
                    <button class="fab fab-create" onclick="showCreateUserModal()" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Create New User">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <button class="fab fab-export" onclick="showComingSoonAlert('Export')" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Export User Data">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="fab fab-report" onclick="showComingSoonAlert('Report')" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="View User Reports">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-4">Select the type of user you want to create:</p>
                    <div class="d-grid gap-3">
                        <button class="btn btn-success btn-lg" onclick="navigateToCreateUser('student')">
                            <i class="fas fa-user-graduate me-2"></i>Create Student
                        </button>
                        <button class="btn btn-primary btn-lg" onclick="navigateToCreateUser('advisor')">
                            <i class="fas fa-user-tie me-2"></i>Create Advisor
                        </button>
                        <button class="btn btn-warning btn-lg" onclick="navigateToCreateUser('coordinator')">
                            <i class="fas fa-user-cog me-2"></i>Create Coordinator
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Navigation function
        function navigateToPage(page) {
            window.location.href = page;
        }

        // Show create user modal
        function showCreateUserModal() {
            const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
            modal.show();
        }

        // Navigate to create user page based on type
        function navigateToCreateUser(userType) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('createUserModal'));
            modal.hide();

            switch (userType) {
                case 'student':
                    window.location.href = '../admin/studentmanagement.php';
                    break;
                case 'advisor':
                    window.location.href = '../admin/advisormanagement.php';
                    break;
                case 'coordinator':
                    window.location.href = '../admin/coordinatormanagement.php';
                    break;
            }
        }

        // Show coming soon alert for features in development
        function showComingSoonAlert(feature) {
            Swal.fire({
                title: 'Coming Soon!',
                text: "We're still building this feature and will release it in a future version. Thanks for understanding!",
                icon: 'info',
                confirmButtonText: 'Got it!',
                confirmButtonColor: '#3085d6',
                customClass: {
                    popup: 'swal2-popup-custom'
                }
            });
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>