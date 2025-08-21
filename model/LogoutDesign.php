<style>
    /* Customize modal header */
    .modal-header.bg-danger {
        background-color: #2DD627 !important;
        /* Header green color */
        color: white;
    }

    .modal-header {
        background-color: #2DD627 !important;
        /* Header green color */
        color: white;
    }

    /* Customize buttons */
    .btn-danger {
        background-color: #2DD627 !important;
        border-color: #2DD627 !important;
        color: white;
    }

    .btn-secondary {
        background-color: #A2AF9B !important;
        border-color: #A2AF9B !important;
        color: white;
    }

    /* Add hover effect for buttons */
    .btn-danger:hover {
        background-color: #FF7A30 !important;
        border-color: #FF7A30 !important;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #FF7A30 !important;
        border-color: #FF7A30 !important;
        color: white;
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
        background-color: #DDDAD0;
    }

    /* Modal body styling */
    .modal-body {
        background-color: #DDDAD0;
        color: #333;
    }

    /* Modal footer styling */
    .modal-footer {
        background-color: #DDDAD0;
        border-top: 1px solid #ccc;
    }

    /* Modal title styling */
    .modal-title {
        font-weight: bold;
    }

    /* Close button styling */
    .btn-close-white {
        filter: invert(1);
    }
</style>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Logout</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmLogout">Logout</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('confirmLogout').addEventListener('click', () => {
        fetch('../Logout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(err => {
                console.error('Error during logout:', err);
                alert('An error occurred. Please try again.');
            });
    });
</script>