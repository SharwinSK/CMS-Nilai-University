<?php

session_start();
include('../db/dbconfig.php');
include('../model/LogoutDesign.php');

if (!isset($_SESSION['Adv_ID'])) {
    header("Location: AdvisorLogin.php");
    exit();
}
$currentPage = 'profile';
$adv_id = $_SESSION['Adv_ID'];
$advisor = ['Adv_Name' => '', 'Adv_Email' => '', 'Club_ID' => ''];
$club_name = '';

// Fetch advisor info
$sql = "SELECT Adv_Name, Adv_Email, Club_ID FROM advisor WHERE Adv_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $adv_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $advisor = $result->fetch_assoc();

    $club_id = $advisor['Club_ID'];
    $club_stmt = $conn->prepare("SELECT Club_Name FROM club WHERE Club_ID = ?");
    $club_stmt->bind_param("i", $club_id);
    $club_stmt->execute();
    $club_result = $club_stmt->get_result();
    if ($club_result->num_rows > 0) {
        $club_name = $club_result->fetch_assoc()['Club_Name'];
    }
}

// Handle AJAX update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    ob_clean(); // remove any accidental buffer
    header('Content-Type: application/json');
    http_response_code(200);

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE advisor SET Adv_Name = ?, Adv_Email = ? WHERE Adv_ID = ?");
    $stmt->bind_param("sss", $name, $email, $_SESSION['Adv_ID']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }

    exit();
}


$advisor_name = $advisor['Adv_Name'];

?>

<?php include('../components/Advoffcanvas.php'); ?>
<?php include('../components/Advheader.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Advisor Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="../assets/css/advisor.css?v=<?= time() ?>" rel="stylesheet" />
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <!-- Main Content -->
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <!-- Page Header -->
                <div class="mb-4">
                    <h2 class="fw-bold text-white">
                        <i class="fas fa-user me-2"></i>
                        Advisor Profile
                    </h2>
                    <p class="text-white-50 mb-0">Manage your profile information</p>
                </div>

                <!-- Profile Card -->
                <div class="profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="mb-2"><?= $advisor_name ?></h3>
                        <p class="mb-0 opacity-75">Club Advisor</p>
                    </div>

                    <!-- Profile Body -->
                    <div class="profile-body">
                        <!-- Name -->
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Full Name</div>
                                <div class="info-value" id="displayName"><?= $advisor['Adv_Name'] ?></div>
                            </div>
                        </div>

                        <!-- Staff ID -->
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Staff ID</div>
                                <div class="info-value"><?= $adv_id ?></div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Email Address</div>
                                <div class="info-value" id="displayEmail"><?= $advisor['Adv_Email'] ?></div>
                            </div>
                        </div>

                        <!-- Club Assigned -->
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Club Assigned</div>
                                <div class="info-value"><?= $club_name ?></div>
                            </div>
                        </div>

                        <!-- Edit Button -->
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-edit" data-bs-toggle="modal"
                                data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-2"></i>
                                Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Profile Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-user me-2"></i>Full Name
                        </label>
                        <input type="text" class="form-control" id="name" value="<?= $advisor['Adv_Name'] ?>"
                            required />
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email Address
                        </label>
                        <input type="email" class="form-control" id="email" value="<?= $advisor['Adv_Email'] ?>"
                            required />
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Note: Staff ID and Club Assignment cannot be modified.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-edit" onclick="saveProfile()">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Alert (Hidden by default) -->
    <div class="alert alert-success alert-dismissible fade" role="alert" id="successAlert" style="
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 1050;
        display: none;
      ">
        <i class="fas fa-check-circle me-2"></i>
        Profile updated successfully!
        <button type="button" class="btn-close" onclick="hideAlert()"></button>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Confirm Logout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to log out?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a href="../logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../assets/js/advisorjs/advprofile.js?v=<?= time(); ?>"></script>
</body>

</html>