//Student Profile with Enhanced Client-Side Validation
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

// Validation Functions
function validateName(name) {
  // Only alphabetic characters and spaces allowed
  const nameRegex = /^[A-Za-z\s]+$/;
  if (!name.trim()) {
    return { valid: false, message: "Name cannot be empty." };
  }
  if (!nameRegex.test(name)) {
    return {
      valid: false,
      message: "Name can only contain alphabetic characters and spaces.",
    };
  }
  if (name.trim().length < 2) {
    return {
      valid: false,
      message: "Name must be at least 2 characters long.",
    };
  }
  return { valid: true, message: "" };
}

function validateEmail(email) {
  // Check for valid email format and allowed domains
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const allowedDomains = ["@gmail.com", "@students.nilai.edu.my"];

  if (!email.trim()) {
    return { valid: false, message: "Email cannot be empty." };
  }

  if (!emailRegex.test(email)) {
    return { valid: false, message: "Please enter a valid email address." };
  }

  const domainValid = allowedDomains.some((domain) =>
    email.toLowerCase().endsWith(domain.toLowerCase())
  );
  if (!domainValid) {
    return {
      valid: false,
      message: "Email must be from gmail.com or students.nilai.edu.my domain.",
    };
  }

  return { valid: true, message: "" };
}

function validatePassword(password) {
  // At least 8 characters, one uppercase, one number
  if (!password.trim()) {
    return { valid: false, message: "Password cannot be empty." };
  }

  if (password.length < 8) {
    return {
      valid: false,
      message: "Password must be at least 8 characters long.",
    };
  }

  if (!/[A-Z]/.test(password)) {
    return {
      valid: false,
      message: "Password must contain at least one uppercase letter.",
    };
  }

  if (!/[0-9]/.test(password)) {
    return {
      valid: false,
      message: "Password must contain at least one number.",
    };
  }

  return { valid: true, message: "" };
}

// Real-time validation feedback
function addValidationListeners() {
  const nameField = document.getElementById("studentName");
  const emailField = document.getElementById("email");
  const passwordField = document.getElementById("password");

  // Name validation
  nameField.addEventListener("input", function () {
    if (isEditing) {
      const validation = validateName(this.value);
      showFieldValidation(this, validation);
    }
  });

  // Email validation
  emailField.addEventListener("input", function () {
    if (isEditing) {
      const validation = validateEmail(this.value);
      showFieldValidation(this, validation);
    }
  });

  // Password validation
  passwordField.addEventListener("input", function () {
    if (isEditing && this.value.length > 0) {
      const validation = validatePassword(this.value);
      showFieldValidation(this, validation);
    }
  });
}

function showFieldValidation(field, validation) {
  // Remove existing validation messages
  const existingError = field.parentNode.querySelector(".validation-error");
  if (existingError) {
    existingError.remove();
  }

  // Update field styling
  field.classList.remove("is-valid", "is-invalid");

  if (validation.valid) {
    field.classList.add("is-valid");
  } else {
    field.classList.add("is-invalid");

    // Add error message
    const errorDiv = document.createElement("div");
    errorDiv.className = "validation-error text-danger small mt-1";
    errorDiv.textContent = validation.message;
    field.parentNode.appendChild(errorDiv);
  }
}

// Validate all fields before submission
function validateAllFields() {
  const name = document.getElementById("studentName").value;
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  const nameValidation = validateName(name);
  const emailValidation = validateEmail(email);

  let isValid = nameValidation.valid && emailValidation.valid;
  let errorMessages = [];

  if (!nameValidation.valid) {
    errorMessages.push(nameValidation.message);
  }
  if (!emailValidation.valid) {
    errorMessages.push(emailValidation.message);
  }

  // Only validate password if it's not empty (user wants to change it)
  if (password.length > 0) {
    const passwordValidation = validatePassword(password);
    if (!passwordValidation.valid) {
      errorMessages.push(passwordValidation.message);
      isValid = false;
    }
  }

  return { valid: isValid, errors: errorMessages };
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

    // Clear password field to allow new input
    document.getElementById("password").value = "";
    document.getElementById("password").placeholder =
      "Enter new password (leave empty to keep current)";

    // Update UI
    form.classList.add("edit-mode");
    editBtn.style.display = "none";
    cancelBtn.style.display = "inline-block";
    saveBtn.style.display = "inline-block";

    showAlert(
      "Edit mode enabled. You can now modify your Name, Email, and Password.",
      "info"
    );

    // Add validation listeners
    addValidationListeners();
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

  // Remove validation classes and messages
  clearValidationMessages();

  // Disable editable fields
  document.getElementById("studentName").disabled = true;
  document.getElementById("email").disabled = true;
  document.getElementById("password").disabled = true;
  document.getElementById("password").placeholder = "";

  // Update UI
  form.classList.remove("edit-mode");
  editBtn.style.display = "inline-block";
  cancelBtn.style.display = "none";
  saveBtn.style.display = "none";

  showAlert("Changes cancelled.", "warning");
}

function clearValidationMessages() {
  // Remove all validation error messages
  const errorMessages = document.querySelectorAll(".validation-error");
  errorMessages.forEach((msg) => msg.remove());

  // Remove validation classes
  const fields = document.querySelectorAll(".is-valid, .is-invalid");
  fields.forEach((field) => {
    field.classList.remove("is-valid", "is-invalid");
  });
}

// Handle form submission with validation
function handleFormSubmission() {
  const form = document.getElementById("profileForm");

  form.addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent default submission

    if (!isEditing) return;

    // Validate all fields
    const validation = validateAllFields();

    if (!validation.valid) {
      let errorMessage = "Please fix the following errors:\n";
      errorMessage += validation.errors.join("\n");

      showAlert(errorMessage.replace(/\n/g, "<br>"), "danger");
      return false;
    }

    // If validation passes, show confirmation and submit
    Swal.fire({
      title: "Confirm Changes",
      text: "Are you sure you want to save these changes?",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#ac73ff",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, save changes!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        // Submit the form
        this.submit();
      }
    });
  });
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

  // Auto-dismiss after 7 seconds for error messages, 5 for others
  const dismissTime = type === "danger" ? 7000 : 5000;
  setTimeout(() => {
    const alert = alertContainer.querySelector(".alert");
    if (alert) {
      alert.classList.remove("show");
      setTimeout(() => {
        alertContainer.innerHTML = "";
      }, 150);
    }
  }, dismissTime);
}

// Check session status
function checkSessionStatus() {
  // This will be handled by PHP, but we can add client-side checks
  const studentName = document.getElementById("studentName").value;
  const studentId = document.getElementById("studentId").value;

  if (!studentName || !studentId) {
    console.warn("Session data incomplete");
    showAlert(
      "Session warning: Please refresh the page if you encounter issues.",
      "warning"
    );
  }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  // Check session status
  checkSessionStatus();

  // Set up form submission handler
  handleFormSubmission();

  // Initialize tooltips if needed
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  console.log("Student Profile initialized successfully");
});
