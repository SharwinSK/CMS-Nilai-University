<?php
session_start();
include('../db/dbconfig.php');
$currentPage = 'ongoing';

if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$stmt = $conn->prepare("SELECT Coor_Name FROM coordinator WHERE Coor_ID = ?");
$stmt->bind_param('s', $coordinator_id);
$stmt->execute();
$result = $stmt->get_result();
$coordinator_name = $result->fetch_assoc()['Coor_Name'] ?? 'Coordinator';

$currentPage = 'contact';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Nilai University CMS</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />

    <style>
        /* Custom styles for contact page */
        .contact-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .contact-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .construction-icon {
            font-size: 5rem;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .contact-title {
            color: var(--primary-dark);
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .contact-message {
            color: #6c757d;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .coming-soon-badge {
            background: linear-gradient(45deg, var(--primary-light), var(--primary-purple));
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            display: inline-block;
            margin-top: 1rem;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>
    <!-- Main Content -->
    <div class="container-fluid contact-container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="contact-card">
                    <div class="construction-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h2 class="contact-title">User Guide</h2>
                    <p class="contact-message">
                        We're still building this page and will release it in a future version.
                        Thanks for understanding!
                    </p>
                    <div class="coming-soon-badge">
                        <i class="fas fa-rocket me-2"></i>
                        Coming Soon
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal (if you have one in your system) -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="../logout.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>