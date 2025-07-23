let currentDate = new Date();
let isFloatingMenuOpen = false;

function updateCalendarDisplay() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const today = new Date();

  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);

  const firstDayWeek = firstDayOfMonth.getDay(); // Sunday = 0
  const totalDaysInMonth = lastDayOfMonth.getDate();

  const calendarDays = document.getElementById("calendarDays");
  calendarDays.innerHTML = "";

  // Calculate the starting date of the grid (including previous month's trailing days)
  let startDate = new Date(firstDayOfMonth);
  startDate.setDate(startDate.getDate() - firstDayWeek); // Go back to the previous Sunday

  // Always show 6 full weeks (6 * 7 = 42 cells)
  for (let i = 0; i < 42; i++) {
    const cellDate = new Date(startDate);
    cellDate.setDate(startDate.getDate() + i);

    const dateStr = cellDate.toISOString().split("T")[0];
    const dayElement = document.createElement("div");
    dayElement.className = "calendar-day";
    dayElement.textContent = cellDate.getDate();

    // Dim the days from other months
    if (cellDate.getMonth() !== month) {
      dayElement.style.opacity = "0.4";
    }

    // Highlight today
    if (cellDate.toDateString() === today.toDateString()) {
      dayElement.classList.add("today");
    }

    // Add event if matched
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

  // Update month title
  document.getElementById("currentMonth").textContent =
    firstDayOfMonth.toLocaleString("default", {
      month: "long",
      year: "numeric",
    });
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
  window.location.href = "../student/PostmortemView.php";
}

function viewNotification(eventName) {
  alert(`Viewing details for: ${eventName}`);
}

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
