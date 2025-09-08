// Wait for DOM to be fully loaded before running any code
document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips only if they exist
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
      html: true,
    });
  });

  // Password validation with tooltip update - ONLY if password field exists
  var passwordField = document.getElementById("password");
  if (passwordField) {
    passwordField.addEventListener("input", function () {
      const tooltip = bootstrap.Tooltip.getInstance(this);
      if (tooltip) {
        const password = this.value;

        const requirements = [
          /.{8,}/.test(password)
            ? "✓ At least 8 characters"
            : "✗ At least 8 characters",
          /[A-Z]/.test(password)
            ? "✓ At least one uppercase letter"
            : "✗ At least one uppercase letter",
          /\d/.test(password)
            ? "✓ At least one number"
            : "✗ At least one number",
        ];

        tooltip.setContent({
          ".tooltip-inner": requirements.join("<br>"),
        });
      }
    });
  }

  // Email form validation - ONLY if email form exists
  var emailForm = document.getElementById("emailForm");
  if (emailForm) {
    emailForm.addEventListener("submit", function (e) {
      const emailInput = document.querySelector('[name="email"]');
      if (emailInput) {
        const email = emailInput.value;
        if (!/^n\d{8}@students\.nilai\.edu\.my$/.test(email)) {
          alert(
            "Email must be a valid university email address (e.g. n00012345@students.nilai.edu.my)."
          );
          e.preventDefault();
          return;
        }
      }
    });
  }

  // OTP form validation - ONLY if OTP form exists
  var otpForm = document.getElementById("otpForm");
  if (otpForm) {
    otpForm.addEventListener("submit", function (e) {
      const otpInput = document.querySelector('[name="otp"]');
      if (otpInput) {
        const otp = otpInput.value;
        if (!/^\d{6}$/.test(otp)) {
          alert("OTP must be exactly 6 digits.");
          e.preventDefault();
          return;
        }
      }
    });
  }

  // Final registration form validation - ONLY if registration form exists
  var registrationForm = document.getElementById("registrationForm");
  if (registrationForm) {
    registrationForm.addEventListener("submit", function (e) {
      const nameInput = document.querySelector('[name="Stu_Name"]');
      const studentIDInput = document.querySelector('[name="Stu_ID"]');

      if (nameInput && studentIDInput) {
        const name = nameInput.value;
        const studentID = studentIDInput.value;

        if (!/^[a-zA-Z\s]+$/.test(name)) {
          alert("Name must contain only letters and spaces.");
          e.preventDefault();
          return;
        }

        if (!/^000\d{5}$/.test(studentID)) {
          alert("Student ID must be exactly 8 digits and start with '000'.");
          e.preventDefault();
          return;
        }
      }
    });
  }

  // Auto-format OTP input - ONLY if OTP input exists
  var otpInput = document.querySelector('[name="otp"]');
  if (otpInput) {
    otpInput.addEventListener("input", function (e) {
      // Only allow numbers
      e.target.value = e.target.value.replace(/[^0-9]/g, "");

      // Limit to 6 digits
      if (e.target.value.length > 6) {
        e.target.value = e.target.value.slice(0, 6);
      }
    });
  }

  // OTP Countdown Timer - ONLY if countdown element exists
  var countdownElement = document.getElementById("otpCountdown");
  if (countdownElement) {
    startOTPCountdown();
  }
});

// Programs data
const programsBySchool = {
  "School of Accounting and Finance": [
    "Diploma in Accounting",
    "Bachelor of Finance(Honours)(Financial Technology)",
    "Bachelor of Arts(Honours) in Accounting & Finance",
    "Bachelor of Business Administration(Honours) in Finance",
  ],
  "School of Aircraft Maintenance": [
    "Diploma in Aircraft Maintenance Engineering",
    "Advanced Diploma in Aircraft Engineering Technology BEng(Hons) Aircraft Engineering",
  ],
  "School of Applied Science": [
    "Bachelor of Science (Hons) Biotechnology",
    "Master of Applied Sciences",
  ],
  "School of Computing": [
    "Diploma in Computer Science",
    "Diploma in Information Technology",
    "Bachelor in Computer Science(Honours)(Artificial Intelligence)",
    "Bachelor in Information Technology(Cybersecurity)(Honours)",
    "Bachelor in Computer Science(Honours)(Data Science)",
    "Bachelor of Information Technology(Hons)",
    "Bachelor of Information Technology(Hons)(Internet Engineering and Cloud Computing)",
    "Bachelor in Software Engineering(Honours)",
  ],
  "School of Foundation Studies": [
    "Foundation in Business",
    "Foundation in Science",
  ],
  "School of Hospitality and Tourism": [
    "Diploma in Hotel Management",
    "Diploma in Culinary Arts",
    "Bachelor of Events Management (Honours)",
    "Bachelor in Hospitality Management (Honours) with Business Management",
  ],
  "School of Management and Marketing": [
    "Diploma in Business Administration",
    "Bachelor in Business Administration (Business Analytics) with Honours",
    "Bachelor of Business Administration (Honours) in Global Logistic",
    "Bachelor of Business Administration (Honours) in Digital Marketing",
    "Bachelor of Business Administration (Honours) in Marketing",
    "Bachelor of Business Administration (Honours) in Management",
    "Bachelor of Business Administration (Honours) in International Business",
    "Bachelor of Business Administration (Honours) in Human Resource Management",
    "Bachelor of Business Administration (Honours)",
  ],
  "School of Nursing": [
    "Diploma in Nursing",
    "Bachelor of Science(Hons) in Nursing",
  ],
};

// This function is called from HTML, so keep it outside DOMContentLoaded
function updatePrograms() {
  const school = document.getElementById("schoolSelect");
  const programSelect = document.getElementById("programSelect");

  // Check if elements exist before using them
  if (school && programSelect) {
    const schoolValue = school.value;
    programSelect.innerHTML =
      '<option value="" disabled selected>Select Program</option>';

    if (programsBySchool[schoolValue]) {
      programsBySchool[schoolValue].forEach((program) => {
        const option = document.createElement("option");
        option.value = program;
        option.textContent = program;
        programSelect.appendChild(option);
      });
    }
  }
}

// OTP Countdown Timer Function
function startOTPCountdown() {
  const countdownElement = document.getElementById("otpCountdown");
  const resendButton = document.getElementById("resendOTP");
  const verifyButton = document.querySelector('button[name="verify_otp"]');

  if (!countdownElement) return;

  let timeLeft = 600; // 10 minutes in seconds

  // Disable resend button initially
  if (resendButton) {
    resendButton.disabled = true;
    resendButton.classList.add("btn-secondary");
    resendButton.classList.remove("btn-warning");
  }

  const timer = setInterval(() => {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;

    // Format time display
    const timeDisplay = `${minutes.toString().padStart(2, "0")}:${seconds
      .toString()
      .padStart(2, "0")}`;
    countdownElement.textContent = `Time remaining: ${timeDisplay}`;

    // Change color based on time remaining
    if (timeLeft <= 60) {
      countdownElement.style.color = "#dc3545"; // Red for last minute
      countdownElement.style.fontWeight = "bold";
    } else if (timeLeft <= 180) {
      countdownElement.style.color = "#ffc107"; // Yellow for last 3 minutes
    } else {
      countdownElement.style.color = "#28a745"; // Green for normal time
    }

    timeLeft--;

    if (timeLeft < 0) {
      clearInterval(timer);
      countdownElement.textContent = "OTP has expired";
      countdownElement.style.color = "#dc3545";
      countdownElement.style.fontWeight = "bold";

      // Disable verify button and enable resend
      if (verifyButton) {
        verifyButton.disabled = true;
        verifyButton.textContent = "OTP Expired";
      }

      if (resendButton) {
        resendButton.disabled = false;
        resendButton.classList.remove("btn-secondary");
        resendButton.classList.add("btn-warning");
        resendButton.textContent = "Resend OTP";
      }

      // Show expiration alert
      setTimeout(() => {
        alert("OTP has expired. Please request a new one.");
        window.location.reload();
      }, 1000);
    }
  }, 1000);

  // Store timer ID in case we need to clear it
  if (countdownElement) {
    countdownElement.timerID = timer;
  }
}
