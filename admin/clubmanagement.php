<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'clubmanagement';

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

// Handle form submissions
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_club':
                $club_name = trim($_POST['club_name']);
                $club_logo = null;

                // Handle file upload
                if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['club_logo']['type'];

                    if (in_array($file_type, $allowed_types)) {
                        $upload_dir = '../uploads/clublogos/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_extension = pathinfo($_FILES['club_logo']['name'], PATHINFO_EXTENSION);
                        $filename = time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;

                        if (move_uploaded_file($_FILES['club_logo']['tmp_name'], $upload_path)) {
                            $club_logo = $upload_path;
                        }
                    }
                }

                // Check if club name already exists
                $check_query = "SELECT Club_ID FROM club WHERE Club_Name = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "s", $club_name);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($check_result) > 0) {
                    $message = "Club name already exists!";
                    $message_type = "danger";
                } else {
                    // Insert new club
                    $insert_query = "INSERT INTO club (Club_Name, Club_Logo) VALUES (?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "ss", $club_name, $club_logo);

                    if (mysqli_stmt_execute($insert_stmt)) {
                        $message = "Club added successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error adding club!";
                        $message_type = "danger";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
                mysqli_stmt_close($check_stmt);
                break;

            case 'update_club':
                $club_id = $_POST['club_id'];
                $club_name = trim($_POST['club_name']);
                $club_logo = $_POST['current_logo']; // Keep current logo by default

                // Handle file upload
                if (isset($_FILES['club_logo']) && $_FILES['club_logo']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = $_FILES['club_logo']['type'];

                    if (in_array($file_type, $allowed_types)) {
                        $upload_dir = '../uploads/club_logos/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_extension = pathinfo($_FILES['club_logo']['name'], PATHINFO_EXTENSION);
                        $filename = time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;

                        if (move_uploaded_file($_FILES['club_logo']['tmp_name'], $upload_path)) {
                            // Delete old logo if it exists
                            if (!empty($_POST['current_logo']) && file_exists($_POST['current_logo'])) {
                                unlink($_POST['current_logo']);
                            }
                            $club_logo = $upload_path;
                        }
                    }
                }

                // Check if club name already exists (excluding current club)
                $check_query = "SELECT Club_ID FROM club WHERE Club_Name = ? AND Club_ID != ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "si", $club_name, $club_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($check_result) > 0) {
                    $message = "Club name already exists!";
                    $message_type = "danger";
                } else {
                    // Update club
                    $update_query = "UPDATE club SET Club_Name = ?, Club_Logo = ? WHERE Club_ID = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ssi", $club_name, $club_logo, $club_id);

                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "Club updated successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error updating club!";
                        $message_type = "danger";
                    }
                    mysqli_stmt_close($update_stmt);
                }
                mysqli_stmt_close($check_stmt);
                break;

            case 'delete_club':
                $club_id = $_POST['club_id'];

                // Check if club has associated advisors or events
                $check_advisor_query = "SELECT COUNT(*) as advisor_count FROM advisor WHERE Club_ID = ?";
                $check_advisor_stmt = mysqli_prepare($conn, $check_advisor_query);
                mysqli_stmt_bind_param($check_advisor_stmt, "i", $club_id);
                mysqli_stmt_execute($check_advisor_stmt);
                $advisor_result = mysqli_stmt_get_result($check_advisor_stmt);
                $advisor_data = mysqli_fetch_assoc($advisor_result);

                $check_event_query = "SELECT COUNT(*) as event_count FROM events WHERE Club_ID = ?";
                $check_event_stmt = mysqli_prepare($conn, $check_event_query);
                mysqli_stmt_bind_param($check_event_stmt, "i", $club_id);
                mysqli_stmt_execute($check_event_stmt);
                $event_result = mysqli_stmt_get_result($check_event_stmt);
                $event_data = mysqli_fetch_assoc($event_result);

                if ($advisor_data['advisor_count'] > 0 || $event_data['event_count'] > 0) {
                    $message = "Cannot delete club. It has associated advisors or events!";
                    $message_type = "danger";
                } else {
                    // Get club logo to delete
                    $logo_query = "SELECT Club_Logo FROM club WHERE Club_ID = ?";
                    $logo_stmt = mysqli_prepare($conn, $logo_query);
                    mysqli_stmt_bind_param($logo_stmt, "i", $club_id);
                    mysqli_stmt_execute($logo_stmt);
                    $logo_result = mysqli_stmt_get_result($logo_stmt);
                    $logo_data = mysqli_fetch_assoc($logo_result);

                    // Delete club
                    $delete_query = "DELETE FROM club WHERE Club_ID = ?";
                    $delete_stmt = mysqli_prepare($conn, $delete_query);
                    mysqli_stmt_bind_param($delete_stmt, "i", $club_id);

                    if (mysqli_stmt_execute($delete_stmt)) {
                        // Delete logo file if it exists
                        if (!empty($logo_data['Club_Logo']) && file_exists($logo_data['Club_Logo'])) {
                            unlink($logo_data['Club_Logo']);
                        }
                        $message = "Club deleted successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error deleting club!";
                        $message_type = "danger";
                    }
                    mysqli_stmt_close($delete_stmt);
                    mysqli_stmt_close($logo_stmt);
                }

                mysqli_stmt_close($check_advisor_stmt);
                mysqli_stmt_close($check_event_stmt);
                break;
        }
    }
}

// Get clubs with advisor information and event count
$clubs_query = "
    SELECT 
        c.Club_ID,
        c.Club_Name,
        c.Club_Logo,
        a.Adv_Name,
        COUNT(e.Ev_ID) as event_count
    FROM club c
    LEFT JOIN advisor a ON c.Club_ID = a.Club_ID
    LEFT JOIN events e ON c.Club_ID = e.Club_ID
    GROUP BY c.Club_ID, c.Club_Name, c.Club_Logo, a.Adv_Name
    ORDER BY c.Club_ID
";
$clubs_result = mysqli_query($conn, $clubs_query);

// Get all advisors for dropdown
$advisors_query = "SELECT Adv_ID, Adv_Name FROM advisor ORDER BY Adv_Name";
$advisors_result = mysqli_query($conn, $advisors_query);
$advisors = [];
while ($advisor = mysqli_fetch_assoc($advisors_result)) {
    $advisors[] = $advisor;
}

// Count total clubs
$total_clubs = mysqli_num_rows($clubs_result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Management - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
    <link href="../assets/css/admin/clubmanagement.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Content Header -->
        <div class="content-header">
            <h2><i class="fas fa-users me-3"></i>NU Club List</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="club-management-container">
            <!-- Action Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn add-club-btn" data-bs-toggle="modal" data-bs-target="#addClubModal">
                        <i class="fas fa-plus me-2"></i>Add New Club
                    </button>
                    <span class="club-count ms-3">
                        <i class="fas fa-building me-1"></i>
                        <?= $total_clubs ?> Clubs
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
                                <th>Logo</th>
                                <th>Club Name</th>
                                <th>Advisor</th>
                                <th>Total Events</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clubTableBody">
                            <?php while ($club = mysqli_fetch_assoc($clubs_result)): ?>
                                <tr>
                                    <td><?= $club['Club_ID'] ?></td>
                                    <td>
                                        <?php if (!empty($club['Club_Logo']) && file_exists($club['Club_Logo'])): ?>
                                            <button class="btn view-logo-btn"
                                                onclick="showLogoModal('<?= htmlspecialchars($club['Club_Logo']) ?>', '<?= htmlspecialchars($club['Club_Name'], ENT_QUOTES) ?>')"
                                                title="Click to view club logo">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                        <?php else: ?>
                                            <button class="btn view-logo-btn" disabled title="No logo available">
                                                <i class="fas fa-image me-1"></i>No Logo
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($club['Club_Name']) ?></td>
                                    <td><?= htmlspecialchars($club['Adv_Name'] ?? 'No Advisor Assigned') ?></td>
                                    <td><?= $club['event_count'] ?></td>
                                    <td>
                                        <button class="btn action-btn edit-btn"
                                            onclick="editClub(<?= $club['Club_ID'] ?>, '<?= htmlspecialchars($club['Club_Name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($club['Club_Logo'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn action-btn delete-btn"
                                            onclick="deleteClub(<?= $club['Club_ID'] ?>, '<?= htmlspecialchars($club['Club_Name'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash me-1"></i>Delete
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
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_club">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Club ID will be assigned automatically by the database.
                        </div>
                        <div class="mb-3">
                            <label for="clubName" class="form-label">Club Name *</label>
                            <input type="text" class="form-control" name="club_name" id="clubName"
                                placeholder="Enter club name" required>
                        </div>
                        <div class="mb-3">
                            <label for="clubLogo" class="form-label">Club Logo (Optional)</label>
                            <input type="file" class="form-control" name="club_logo" id="clubLogo"
                                accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">Accepted formats: JPG, PNG, GIF. Max size: 2MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Club
                        </button>
                    </div>
                </form>
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
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_club">
                    <input type="hidden" name="club_id" id="editClubId">
                    <input type="hidden" name="current_logo" id="editCurrentLogo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editClubName" class="form-label">Club Name *</label>
                            <input type="text" class="form-control" name="club_name" id="editClubName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Logo</label>
                            <div id="currentLogoDisplay" class="mb-2"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editClubLogo" class="form-label">Upload New Logo (Optional)</label>
                            <input type="file" class="form-control" name="club_logo" id="editClubLogo"
                                accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">Leave empty to keep current logo. Accepted formats: JPG, PNG, GIF.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Club
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClubModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: #dc3545 !important;">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        style="filter: brightness(0) invert(1);"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_club">
                    <input type="hidden" name="club_id" id="deleteClubId">
                    <div class="modal-body" style="background: white;">
                        <p>Are you sure you want to delete the club "<strong id="deleteClubName"></strong>"?</p>
                        <p class="text-muted">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer" style="background: white;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Club
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Logo View Modal -->
    <div class="modal fade logo-modal" id="logoViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoModalTitle">
                        <i class="fas fa-image me-2"></i>Club Logo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="logoModalImage" src="" alt="Club Logo" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show logo in modal
        function showLogoModal(logoSrc, clubName) {
            document.getElementById('logoModalImage').src = logoSrc;
            document.getElementById('logoModalTitle').innerHTML = `<i class="fas fa-image me-2"></i>${clubName} - Logo`;
            new bootstrap.Modal(document.getElementById('logoViewModal')).show();
        }

        // Edit club function
        function editClub(id, name, logo) {
            document.getElementById('editClubId').value = id;
            document.getElementById('editClubName').value = name;
            document.getElementById('editCurrentLogo').value = logo;

            // Display current logo
            const logoDisplay = document.getElementById('currentLogoDisplay');
            if (logo && logo.trim() !== '') {
                logoDisplay.innerHTML = `
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn view-logo-btn me-2" 
                                onclick="showLogoModal('${logo}', '${name}')" 
                                title="Click to view current logo">
                            <i class="fas fa-eye me-1"></i>View Current Logo
                        </button>
                        <span class="text-muted">Click to view current logo</span>
                    </div>
                `;
            } else {
                logoDisplay.innerHTML = `
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary">
                            <i class="fas fa-image me-1"></i>No logo uploaded
                        </span>
                    </div>
                `;
            }

            new bootstrap.Modal(document.getElementById('editClubModal')).show();
        }

        // Delete club function
        function deleteClub(id, name) {
            document.getElementById('deleteClubId').value = id;
            document.getElementById('deleteClubName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteClubModal')).show();
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#clubTableBody tr');

            rows.forEach(row => {
                const clubId = row.cells[0].textContent.toLowerCase();
                const clubName = row.cells[2].textContent.toLowerCase();
                const advisor = row.cells[3].textContent.toLowerCase();

                if (clubId.includes(searchTerm) || clubName.includes(searchTerm) || advisor.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 5000);
            });
        });
    </script>
</body>

</html>