<?php
session_start();
include '../db/dbconfig.php';

// Handle email verification request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_email'])) {
    $email = $_POST['email'];

    // Check if email already exists
    $stmt = $conn->prepare("SELECT Stu_Email FROM student WHERE Stu_Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email already taken, please try other email. Or login using your Student ID.'); window.location.href='StudentLogin.php';</script>";
        exit;
    }

    // Generate OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['otp'] = $otp;
    $_SESSION['email_to_verify'] = $email;
    $_SESSION['otp_timestamp'] = time(); // For expiration

    // Send OTP via email using existing email system
    require_once '../model/sendMailTemplates.php'; // Use your existing email templates

    // Send OTP email using the new function
    if (sendRegistrationOTP($email, $otp)) {
        $email_sent = true;
    } else {
        echo "<script>alert('Failed to send OTP. Please try again.'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
}

// Handle resend OTP request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    if (isset($_SESSION['email_to_verify'])) {
        // Generate new OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_timestamp'] = time(); // Reset timestamp

        // Send OTP via email
        require_once '../model/sendMailTemplates.php';

        if (sendRegistrationOTP($_SESSION['email_to_verify'], $otp)) {
            $email_sent = true;
            echo "<script>alert('New OTP sent to your email!'); window.location.reload();</script>";
        } else {
            echo "<script>alert('Failed to send OTP. Please try again.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Session expired. Please start again.'); window.location.href='Register.php';</script>";
    }
    exit;
}

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    // Check if OTP is expired (10 minutes)
    if (!isset($_SESSION['otp_timestamp']) || (time() - $_SESSION['otp_timestamp']) > 600) {
        unset($_SESSION['otp'], $_SESSION['email_to_verify'], $_SESSION['otp_timestamp']);
        echo "<script>alert('OTP has expired. Please request a new one.'); window.location.reload();</script>";
        exit;
    }

    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $entered_otp) {
        $_SESSION['email_verified'] = true;
        $_SESSION['verified_email'] = $_SESSION['email_to_verify'];
        unset($_SESSION['otp'], $_SESSION['otp_timestamp']); // Keep email_to_verify for form
        $otp_verified = true;
    } else {
        echo "<script>alert('Invalid OTP. Please try again.'); window.history.back();</script>";
        exit;
    }
}

// Handle final registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_registration'])) {
    // Check if email is verified
    if (!isset($_SESSION['email_verified']) || !$_SESSION['email_verified']) {
        echo "<script>alert('Please verify your email first.'); window.location.reload();</script>";
        exit;
    }

    $name = $_POST['Stu_Name'];
    $program = $_POST['Stu_Program'];
    $school = $_POST['Stu_School'];
    $studentID = $_POST['Stu_ID'];
    $passwordInput = $_POST['Stu_PSW'];
    $email = $_SESSION['verified_email'];

    // Password validation
    if (
        !preg_match('/.{8,}/', $passwordInput) || // At least 8 characters
        !preg_match('/[A-Z]/', $passwordInput) || // At least one uppercase letter
        !preg_match('/\d/', $passwordInput)      // At least one number
    ) {
        echo "<script>alert('Password does not meet the requirements. Please include at least 8 characters, one uppercase letter, and one number.'); window.history.back();</script>";
        exit;
    }

    // Check for duplicate student ID
    $stmt = $conn->prepare("SELECT Stu_ID FROM student WHERE Stu_ID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Student ID already registered! Please login instead.'); window.location.href='StudentLogin.php';</script>";
        exit;
    } else {
        // Hash the password
        $hashedPassword = password_hash($passwordInput, PASSWORD_BCRYPT);

        // Insert the new student record
        $stmt = $conn->prepare("INSERT INTO student (Stu_Name, Stu_Program, Stu_School, Stu_ID, Stu_Email, Stu_PSW) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $program, $school, $studentID, $email, $hashedPassword);

        if ($stmt->execute()) {
            // Clear session data
            unset($_SESSION['email_verified'], $_SESSION['verified_email'], $_SESSION['email_to_verify']);
            echo "<script>alert('Registration successful! You can now login.'); window.location.href='StudentLogin.php';</script>";
        } else {
            echo "<script>alert('Registration failed! Please try again.'); window.location.reload();</script>";
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="../assets/js/register.js" defer></script>
</head>

<body>
    <div class="container mt-5">
        <a href="../Index.php"><img src="../assets/img/NU logo.png" alt="University Logo" class="logo mb-3"></a>
        <h1 class="mb-4">Student Registration</h1>

        <?php if (!isset($email_sent) && !isset($otp_verified)): ?>
            <!-- Step 1: Email Verification -->
            <form action="Register.php" method="POST" id="emailForm">
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Enter Student Email" required>
                    <small class="text-muted">Example : n00012345@student.nilai.edu.my</small>
                </div>
                <button type="submit" name="verify_email" class="btn btn-primary">Send OTP</button>
            </form>

        <?php elseif (isset($email_sent) && !isset($otp_verified)): ?>
            <!-- Step 2: OTP Verification -->
            <!-- Floating Message Box -->
            <div class="floating-message-box">
                <div class="floating-message">
                    <i class="checkmark-icon">✓</i>
                    <span>OTP sent successfully!</span>
                    <br>
                    <small>Check your email inbox or spam folder</small>
                </div>
            </div>

            <!-- Countdown Timer Display -->
            <div id="otpCountdown" class="otp-countdown">
                Time remaining: 10:00
            </div>

            <form action="Register.php" method="POST" id="otpForm">
                <div class="mb-3">
                    <input type="text" name="otp" class="form-control" placeholder="Enter 6-digit OTP" maxlength="6"
                        required>
                </div>
                <div class="button-row">
                    <button type="submit" name="verify_otp" class="btn btn-success">Verify OTP</button>
                    <a href="Register.php" class="btn btn-secondary">Back</a>
                </div>
                <button type="submit" name="resend_otp" id="resendOTP" class="btn btn-warning mt-2"
                    style="display: none;">Resend OTP</button>
            </form>

            <!-- JavaScript to start countdown -->
            <script>
                // Pass PHP timestamp to JavaScript
                const otpTimestamp = <?php echo $_SESSION['otp_timestamp']; ?>;
                const currentTime = <?php echo time(); ?>;
                const timeElapsed = currentTime - otpTimestamp;
                const timeRemaining = Math.max(0, 600 - timeElapsed); // 600 seconds = 10 minutes

                // Update countdown with remaining time
                if (timeRemaining > 0) {
                    startOTPCountdown(timeRemaining);
                } else {
                    // OTP already expired
                    document.getElementById('otpCountdown').textContent = 'OTP has expired';
                    document.getElementById('otpCountdown').style.color = '#dc3545';
                    document.querySelector('button[name="verify_otp"]').disabled = true;
                    document.getElementById('resendOTP').style.display = 'inline-block';
                    document.getElementById('resendOTP').disabled = false;
                }

                // Modified countdown function to accept initial time
                function startOTPCountdown(initialTime = 600) {
                    const countdownElement = document.getElementById("otpCountdown");
                    const resendButton = document.getElementById("resendOTP");
                    const verifyButton = document.querySelector('button[name="verify_otp"]');

                    if (!countdownElement) return;

                    let timeLeft = initialTime;

                    // Initial setup
                    if (resendButton) {
                        resendButton.disabled = true;
                        resendButton.style.display = 'none';
                    }

                    const timer = setInterval(() => {
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;

                        // Format time display
                        const timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        countdownElement.textContent = `Time remaining: ${timeDisplay}`;

                        // Change color and class based on time remaining
                        countdownElement.className = 'otp-countdown';
                        if (timeLeft <= 60) {
                            countdownElement.className += ' danger';
                        } else if (timeLeft <= 180) {
                            countdownElement.className += ' warning';
                        }

                        timeLeft--;

                        if (timeLeft < 0) {
                            clearInterval(timer);
                            countdownElement.textContent = 'OTP has expired';
                            countdownElement.className = 'otp-countdown danger';

                            // Disable verify button and enable resend
                            if (verifyButton) {
                                verifyButton.disabled = true;
                                verifyButton.textContent = 'OTP Expired';
                                verifyButton.classList.add('btn-secondary');
                                verifyButton.classList.remove('btn-success');
                            }

                            if (resendButton) {
                                resendButton.disabled = false;
                                resendButton.style.display = 'inline-block';
                            }

                            // Show expiration alert after a delay
                            setTimeout(() => {
                                alert('OTP has expired. Please click "Resend OTP" to get a new verification code.');
                            }, 1000);
                        }
                    }, 1000);

                    // Store timer ID in case we need to clear it
                    if (countdownElement) {
                        countdownElement.timerID = timer;
                    }
                }
            </script>

        <?php elseif (isset($otp_verified) || (isset($_SESSION['email_verified']) && $_SESSION['email_verified'])): ?>
            <!-- Step 3: Complete Registration -->
            <div class="alert alert-success">
                ✓ Email verified: <?php echo htmlspecialchars($_SESSION['verified_email']); ?>
            </div>

            <form action="Register.php" method="POST" id="registrationForm">
                <div class="mb-3">
                    <input type="text" name="Stu_Name" class="form-control" placeholder="Student Name" required>
                </div>

                <div class="mb-3">
                    <select name="Stu_School" id="schoolSelect" class="form-select" required onchange="updatePrograms()">
                        <option value="" disabled selected>Select School</option>
                        <option value="School of Computing">School of Computing</option>
                        <option value="School of Accounting and Finance">School of Accounting and Finance</option>
                        <option value="School of Aircraft Maintenance">School of Aircraft Maintenance</option>
                        <option value="School of Applied Science">School of Applied Science</option>
                        <option value="School of Foundation Studies">School of Foundation Studies</option>
                        <option value="School of Hospitality and Tourism">School of Hospitality and Tourism</option>
                        <option value="School of Management and Marketing">School of Management and Marketing</option>
                        <option value="School of Nursing">School of Nursing</option>
                    </select>
                </div>

                <div class="mb-3">
                    <select name="Stu_Program" id="programSelect" class="form-select" required>
                        <option value="" disabled selected>Select Program</option>
                    </select>
                </div>

                <div class="mb-3">
                    <input type="text" name="Stu_ID" class="form-control" placeholder="Student ID (000#####)" required>
                    <small class="text-muted">Student ID must start with '000' followed by 5 digits</small>
                </div>

                <div class="mb-3 position-relative">
                    <input type="password" name="Stu_PSW" class="form-control" id="password" placeholder="Password" required
                        data-bs-toggle="tooltip" data-bs-placement="right" title="Password must contain: 
                             • At least 8 characters 
                             • At least one uppercase letter 
                             • At least one number">
                    <small class="text-muted">Password must be at least 8 characters with uppercase letter and
                        number</small>
                </div>

                <button type="submit" name="complete_registration" class="btn btn-success">Complete Registration</button>
            </form>
        <?php endif; ?>

        <p class="mt-3">Already have an account? <a href="StudentLogin.php">Login Here</a></p>
    </div>

</body>

</html>