<?php
session_start();
include '../db/dbconfig.php';
$currentPage = 'reportexport';

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
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet" />
    <link href="../assets/css/admin/dashboard.css?v=<?= time() ?>" rel="stylesheet" />
</head>
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

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/AdmHeader.php'); ?>
    <?php include('../components/AdmOffcanvas.php'); ?>
    <!-- Main Content -->
    <div class="container-fluid contact-container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="contact-card">
                    <div class="construction-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h2 class="contact-title">Report & Export</h2>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>