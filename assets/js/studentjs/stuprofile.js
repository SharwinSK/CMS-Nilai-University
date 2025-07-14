//Student Profile
let isEditing = false;
let originalData = {};

// Store original data when entering edit mode
function storeOriginalData() {
  originalData = {
    studentName: document.getElementById("studentName").value,
    email: document.getElementById("email").value,
    password: document.getElementById("password").value,
  };
}

// Toggle edit mode
function toggleEdit() {
  isEditing = !isEditing;
  const form = document.getElementById("profileForm");
  const editBtn = document.getElementById("editBtn");
  const cancelBtn = document.getElementById("cancelBtn");
  const saveBtn = document.getElementById("saveBtn");

  if (isEditing) {
    // Store original data
    storeOriginalData();

    // Enable editable fields
    document.getElementById("studentName").disabled = false;
    document.getElementById("email").disabled = false;
    document.getElementById("password").disabled = false;

    // Update UI
    form.classList.add("edit-mode");
    editBtn.style.display = "none";
    cancelBtn.style.display = "inline-block";
    saveBtn.style.display = "inline-block";

    showAlert(
      "Edit mode enabled. You can now modify your Name, Email, and Password.",
      "info"
    );
  }
}

// Cancel edit mode
function cancelEdit() {
  isEditing = false;
  const form = document.getElementById("profileForm");
  const editBtn = document.getElementById("editBtn");
  const cancelBtn = document.getElementById("cancelBtn");
  const saveBtn = document.getElementById("saveBtn");

  // Restore original data
  document.getElementById("studentName").value = originalData.studentName;
  document.getElementById("email").value = originalData.email;
  document.getElementById("password").value = originalData.password;

  // Disable editable fields
  document.getElementById("studentName").disabled = true;
  document.getElementById("email").disabled = true;
  document.getElementById("password").disabled = true;

  // Update UI
  form.classList.remove("edit-mode");
  editBtn.style.display = "inline-block";
  cancelBtn.style.display = "none";
  saveBtn.style.display = "none";

  showAlert("Changes cancelled.", "warning");
}

// Toggle password visibility
function togglePassword() {
  const passwordField = document.getElementById("password");
  const toggleIcon = document.getElementById("passwordToggleIcon");

  if (passwordField.type === "password") {
    passwordField.type = "text";
    toggleIcon.classList.remove("fa-eye");
    toggleIcon.classList.add("fa-eye-slash");
  } else {
    passwordField.type = "password";
    toggleIcon.classList.remove("fa-eye-slash");
    toggleIcon.classList.add("fa-eye");
  }
}

// Show alert messages
function showAlert(message, type) {
  const alertContainer = document.getElementById("alertContainer");
  const alertClass =
    type === "success"
      ? "alert-success"
      : type === "danger"
      ? "alert-danger"
      : type === "warning"
      ? "alert-warning"
      : "alert-info";

  const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

  alertContainer.innerHTML = alertHtml;

  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    const alert = alertContainer.querySelector(".alert");
    if (alert) {
      alert.classList.remove("show");
      setTimeout(() => {
        alertContainer.innerHTML = "";
      }, 150);
    }
  }, 5000);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {});
