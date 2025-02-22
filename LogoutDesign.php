<style>
    /* Customize modal header */
    .modal-header.bg-danger {
        background-color: #dc3545 !important;
        /* Bootstrap danger color */
        color: white;
    }

    /* Customize buttons */
    .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }

    .btn-secondary {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
    }

    /* Add hover effect for buttons */
    .btn-danger:hover {
        background-color: #c82333 !important;
        /* Darker red */
    }

    .btn-secondary:hover {
        background-color: #5a6268 !important;
        /* Darker gray */
    }

    /* Center modal vertically */
    .modal-dialog-centered {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Optional: Add shadow to modal for a modern look */
    .modal-content {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        border-radius: 10px;
    }
</style>


<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmLogout">Log Out</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('confirmLogout').addEventListener('click', function () {
        fetch('Logout.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'logout' }),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to the appropriate login page
                    window.location.href = data.redirect;
                } else {
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error during logout:', error);
                alert('An error occurred. Please try again.');
            });
    });


</script>