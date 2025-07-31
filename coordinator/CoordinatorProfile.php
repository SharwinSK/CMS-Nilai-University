<?php
session_start();
include('../db/dbconfig.php');
$currentPage = 'profile';


// If this is a fetch() POST request → handle AJAX update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['Coor_ID'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Read raw body and decode
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit();
    }

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if ($name === '' || $email === '' || $phone === '') {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    $coordinator_id = $_SESSION['Coor_ID'];

    $stmt = $conn->prepare("UPDATE coordinator SET Coor_Name = ?, Coor_Email = ?, Coor_PhnNum = ? WHERE Coor_ID = ?");
    $stmt->bind_param("ssss", $name, $email, $phone, $coordinator_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    exit();
}

// === PAGE LOAD (GET) ===
if (!isset($_SESSION['Coor_ID'])) {
    header("Location: CoordinatorLogin.php");
    exit();
}

$coordinator_id = $_SESSION['Coor_ID'];
$stmt = $conn->prepare("SELECT Coor_Name, Coor_Email, Coor_PhnNum FROM coordinator WHERE Coor_ID = ?");
$stmt->bind_param("s", $coordinator_id);
$stmt->execute();
$result = $stmt->get_result();
$coordinator = $result->fetch_assoc();
$coordinator_name = $coordinator['Coor_Name'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Profile - Nilai University CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/coordinator.css?v=<?= time() ?>" rel="stylesheet" />

</head>

<body>
    <?php include('../model/LogoutDesign.php'); ?>
    <?php include('../components/Cordoffcanvas.php'); ?>
    <?php include('../components/Cordheader.php'); ?>
    <!-- Main Content -->
    <div class="profile-container">
        <div class="profile-card">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>

                <p class="profile-role">Event Coordinator</p>
            </div>

            <!-- Profile Body -->
            <div class="profile-body">
                <ul class="profile-info-list">
                    <!-- Full Name -->
                    <li class="profile-info-item">
                        <div class="info-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">👨‍💼 Full Name</div>
                            <div class="info-value" id="display-name">
                                <?php echo htmlspecialchars($coordinator['Coor_Name']); ?>
                            </div>
                        </div>
                    </li>

                    <!-- Coordinator ID -->
                    <li class="profile-info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">🆔 Coordinator ID</div>
                            <div class="info-value" id="display-id"><?php echo htmlspecialchars($coordinator_id); ?>
                            </div>
                        </div>
                    </li>

                    <!-- Email Address -->
                    <li class="profile-info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">📧 Email Address</div>
                            <div class="info-value" id="display-email">
                                <?php echo htmlspecialchars($coordinator['Coor_Email']); ?>
                            </div>
                        </div>
                    </li>

                    <!-- Phone Number -->
                    <li class="profile-info-item">
                        <div class="info-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">📱 Phone Number</div>
                            <div class="info-value" id="display-phone">
                                <?php echo htmlspecialchars($coordinator['Coor_PhnNum']); ?>
                            </div>
                        </div>
                    </li>
                </ul>

                <!-- Edit Profile Button -->
                <button class="edit-profile-btn" onclick="openEditProfileModal()">
                    <i class="fas fa-edit me-2"></i>
                    Edit Profile
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Profile Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <!-- Name Field -->
                        <div class="mb-4">
                            <label for="editName" class="form-label">
                                <i class="fas fa-user me-2 text-primary"></i>
                                👨‍💼 Full Name
                            </label>
                            <input type="text" class="form-control" id="editName"
                                value="<?php echo htmlspecialchars($coordinator['Coor_Name']); ?>" required />
                            <div class="invalid-feedback" id="nameFeedback"></div>
                        </div>

                        <!-- Email Field -->
                        <div class="mb-4">
                            <label for="editEmail" class="form-label">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                📧 Email Address
                            </label>
                            <input type="email" class="form-control" id="editEmail"
                                value="<?php echo htmlspecialchars($coordinator['Coor_Email']); ?>" required />
                            <div class="invalid-feedback" id="emailFeedback"></div>
                        </div>

                        <!-- Phone Field -->
                        <div class="mb-4">
                            <label for="editPhone" class="form-label">
                                <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                📱 Phone Number
                            </label>
                            <input type="tel" class="form-control" id="editPhone"
                                value="<?php echo htmlspecialchars($coordinator['Coor_PhnNum']); ?>" required />
                            <div class="invalid-feedback" id="phoneFeedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveAllChanges()">
                        <i class="fas fa-save me-1"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    Profile updated successfully!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEditProfileModal() {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));

            // Populate the form with current values
            document.getElementById('editName').value = document.getElementById('display-name').textContent;
            document.getElementById('editEmail').value = document.getElementById('display-email').textContent;
            document.getElementById('editPhone').value = document.getElementById('display-phone').textContent;

            // Clear any previous validation states
            clearValidationErrors();

            modal.show();

            // Focus on first input after modal is shown
            setTimeout(() => document.getElementById('editName').focus(), 500);
        }

        function saveAllChanges() {
            const nameInput = document.getElementById('editName');
            const emailInput = document.getElementById('editEmail');
            const phoneInput = document.getElementById('editPhone');
            const saveButton = document.querySelector('.btn-primary');

            const nameValue = nameInput.value.trim();
            const emailValue = emailInput.value.trim();
            const phoneValue = phoneInput.value.trim();

            // Clear previous validation errors
            clearValidationErrors();

            let hasErrors = false;

            // Validate all fields
            if (!nameValue) {
                showFieldValidationError('editName', 'nameFeedback', 'Full name is required.');
                hasErrors = true;
            }

            if (!emailValue) {
                showFieldValidationError('editEmail', 'emailFeedback', 'Email address is required.');
                hasErrors = true;
            } else if (!isValidEmail(emailValue)) {
                showFieldValidationError('editEmail', 'emailFeedback', 'Please enter a valid email address.');
                hasErrors = true;
            }

            if (!phoneValue) {
                showFieldValidationError('editPhone', 'phoneFeedback', 'Phone number is required.');
                hasErrors = true;
            } else if (!isValidPhone(phoneValue)) {
                showFieldValidationError('editPhone', 'phoneFeedback', 'Please enter a valid phone number.');
                hasErrors = true;
            }

            if (hasErrors) {
                return;
            }

            // Show loading state
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving Changes...';
            saveButton.disabled = true;

            // Simulate API call delay
            setTimeout(() => {
                // Update all display elements
                document.getElementById('display-name').textContent = nameValue;
                document.getElementById('display-email').textContent = emailValue;
                document.getElementById('display-phone').textContent = phoneValue;

                // Update header name and navigation

                document.querySelectorAll('.nav-link.dropdown-toggle').forEach(link => {
                    // Remove existing text nodes after the icon
                    const nodes = [...link.childNodes].filter(n => n.nodeType === Node.TEXT_NODE);
                    nodes.forEach(n => link.removeChild(n));

                    // Append the updated name as a text node
                    link.appendChild(document.createTextNode(' ' + nameValue));
                });


                // Add success animation to all updated items
                const nameItem = document.getElementById('display-name').closest('.profile-info-item');
                const emailItem = document.getElementById('display-email').closest('.profile-info-item');
                const phoneItem = document.getElementById('display-phone').closest('.profile-info-item');

                [nameItem, emailItem, phoneItem].forEach(item => {
                    item.classList.add('success-animation');
                    setTimeout(() => {
                        item.classList.remove('success-animation');
                    }, 600);
                });

                // Reset button state
                saveButton.innerHTML = '<i class="fas fa-save me-1"></i> Save All Changes';
                saveButton.disabled = false;

                // Close modal and show success toast
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                showSuccessToast();

                // Here you would make the actual API call
                updateAllProfileData(nameValue, emailValue, phoneValue);

            }, 1500);
        }

        function clearValidationErrors() {
            const inputs = ['editName', 'editEmail', 'editPhone'];
            inputs.forEach(inputId => {
                document.getElementById(inputId).classList.remove('is-invalid');
            });
        }

        function showFieldValidationError(inputId, feedbackId, message) {
            const input = document.getElementById(inputId);
            const feedback = document.getElementById(feedbackId);

            input.classList.add('is-invalid');
            feedback.textContent = message;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidPhone(phone) {
            const phoneRegex = /^[\+\-\s\(\)\d]{7,20}$/;
            return phoneRegex.test(phone);
        }

        function showSuccessToast() {
            const toast = new bootstrap.Toast(document.getElementById('successToast'));
            toast.show();
        }

        // Handle form submission with Enter key
        document.getElementById('editForm').addEventListener('submit', function (e) {
            e.preventDefault();
            saveAllChanges();
        });

        function updateAllProfileData(name, email, phone) {
            fetch('CoordinatorProfile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    phone: phone,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showSuccessToast();
                    } else {
                        alert('Update failed: ' + data.message);
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Error while updating profile.');
                });
        }

    </script>
</body>

</html>