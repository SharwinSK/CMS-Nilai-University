<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#adminSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand text-white fw-bold" href="../admin/dashboard.php">
            <i class="fas fa-university me-2"></i>Nilai University CMS
        </a>
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($admin_name); ?>
                </a>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>