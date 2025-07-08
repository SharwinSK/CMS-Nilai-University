let currentDate = new Date();
let isFloatingMenuOpen = false;

// Dummy event data (you can fetch from PHP later)
const events = {
  "2025-07-08": "AI Workshop",
  "2025-07-10": "Leadership Camp",
  "2025-07-20": "Cultural Day",
};

function initCalendar() {
  updateCalendarDisplay();
}

function updateCalendarDisplay() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const firstDayWeek = firstDay.getDay();
  const daysInMonth = lastDay.getDate();
  const today = new Date();

  // Display current month
  document.getElementById("currentMonth").textContent = firstDay.toLocaleString(
    "default",
    {
      month: "long",
      year: "numeric",
    }
  );

  const calendarDays = document.getElementById("calendarDays");
  calendarDays.innerHTML = "";

  // Empty cells before 1st day of the month
  for (let i = 0; i < firstDayWeek; i++) {
    const emptyDay = document.createElement("div");
    emptyDay.className = "calendar-day";
    calendarDays.appendChild(emptyDay);
  }

  // Day cells
  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(
      day
    ).padStart(2, "0")}`;
    const dayElement = document.createElement("div");
    dayElement.className = "calendar-day";
    dayElement.textContent = day;

    // Highlight today
    const currentDayDate = new Date(year, month, day);
    if (currentDayDate.toDateString() === today.toDateString()) {
      dayElement.classList.add("today");
    }

    // If event on that date
    if (events[dateStr]) {
      dayElement.classList.add("has-event");

      const eventLabel = document.createElement("span");
      eventLabel.className = "event-name";
      eventLabel.textContent = events[dateStr];
      dayElement.appendChild(document.createElement("br"));
      dayElement.appendChild(eventLabel);

      dayElement.addEventListener("click", () =>
        showEventDetails(dateStr, events[dateStr])
      );
    }

    calendarDays.appendChild(dayElement);
  }
}

function previousMonth() {
  currentDate.setMonth(currentDate.getMonth() - 1);
  updateCalendarDisplay();
}

function nextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  updateCalendarDisplay();
}

function showEventDetails(date, eventName) {
  document.getElementById("eventModalTitle").textContent =
    "Event: " + eventName;
  document.getElementById("eventModalBody").innerHTML = `
    <p><strong>Event:</strong> ${eventName}</p>
    <p><strong>Date:</strong> ${new Date(date).toLocaleDateString()}</p>
    <p><strong>Status:</strong> <span class="status-badge status-approved">Scheduled</span></p>
    <p><strong>Description:</strong> Join us for this exciting event!</p>
  `;
  new bootstrap.Modal(document.getElementById("eventModal")).show();
}

// Floating Button Logic
function toggleFloatingMenu() {
  const menu = document.getElementById("floatingMenu");
  const icon = document.getElementById("floatingIcon");

  if (isFloatingMenuOpen) {
    menu.style.display = "none";
    icon.className = "fas fa-plus";
    icon.style.transform = "rotate(0deg)";
  } else {
    menu.style.display = "flex";
    icon.className = "fas fa-times";
    icon.style.transform = "rotate(180deg)";
  }

  isFloatingMenuOpen = !isFloatingMenuOpen;
}

function createProposal() {
  window.location.href = "../student/proposal/ProposalEvent.php";
}

function createPostEvent() {
  window.location.href = "../student/postevent/Postmortem.php";
}

function viewNotification(eventName) {
  alert(`Viewing details for: ${eventName}`);
}

document.addEventListener("DOMContentLoaded", initCalendar);

// Close floating menu on outside click
document.addEventListener("click", function (event) {
  const floatingBtn = document.querySelector(".floating-btn");
  const floatingMenu = document.getElementById("floatingMenu");

  if (
    !floatingBtn.contains(event.target) &&
    !floatingMenu.contains(event.target)
  ) {
    if (isFloatingMenuOpen) {
      toggleFloatingMenu();
    }
  }
});
