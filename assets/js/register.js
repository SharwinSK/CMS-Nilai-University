var tooltipTriggerList = [].slice.call(
  document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl, {
    html: true,
  });
});

document.getElementById("password").addEventListener("input", function () {
  const tooltip = bootstrap.Tooltip.getInstance(this);
  const password = this.value;

  const requirements = [
    /.{8,}/.test(password)
      ? "✔ At least 8 characters"
      : "✘ At least 8 characters",
    /[A-Z]/.test(password)
      ? "✔ At least one uppercase letter"
      : "✘ At least one uppercase letter",
    /\d/.test(password) ? "✔ At least one number" : "✘ At least one number",
  ];

  tooltip.setContent({
    ".tooltip-inner": requirements.join("<br>"),
  });
});
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

function updatePrograms() {
  const school = document.getElementById("schoolSelect").value;
  const programSelect = document.getElementById("programSelect");

  programSelect.innerHTML =
    '<option value="" disabled selected>Select Program</option>';

  if (programsBySchool[school]) {
    programsBySchool[school].forEach((program) => {
      const option = document.createElement("option");
      option.value = program;
      option.textContent = program;
      programSelect.appendChild(option);
    });
  }
}
document.querySelector("form").addEventListener("submit", function (e) {
  const name = document.querySelector('[name="Stu_Name"]').value;
  const email = document.querySelector('[name="Stu_Email"]').value;
  const studentID = document.querySelector('[name="Stu_ID"]').value;

  if (!/^[a-zA-Z\s]+$/.test(name)) {
    alert("Name must contain only letters.");
    e.preventDefault();
    return;
  }

  if (!/^000\d{5}$/.test(studentID)) {
    alert("Student ID must be exactly 8 digits and start with '000'.");
    e.preventDefault();
    return;
  }

  if (!/^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(email)) {
    alert("Email must be a valid Gmail address (e.g. example@gmail.com).");
    e.preventDefault();
    return;
  }
});
