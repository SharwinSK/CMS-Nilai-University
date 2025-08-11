let currentDate = new Date();
let isFloatingMenuOpen = false;

function updateCalendarDisplay() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const today = new Date();

  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);

  const firstDayWeek = firstDayOfMonth.getDay();
  const totalDaysInMonth = lastDayOfMonth.getDate();

  const calendarDays = document.getElementById("calendarDays");
  calendarDays.innerHTML = "";

  let startDate = new Date(firstDayOfMonth);
  startDate.setDate(startDate.getDate() - firstDayWeek);

  for (let i = 0; i < 42; i++) {
    const cellDate = new Date(startDate);
    cellDate.setDate(startDate.getDate() + i);

    // Fix: Format date properly without timezone conversion
    const year = cellDate.getFullYear();
    const month = String(cellDate.getMonth() + 1).padStart(2, "0");
    const day = String(cellDate.getDate()).padStart(2, "0");
    const dateStr = `${year}-${month}-${day}`;

    const dayElement = document.createElement("div");
    dayElement.className = "calendar-day";
    dayElement.textContent = cellDate.getDate();

    if (cellDate.getMonth() !== currentDate.getMonth()) {
      dayElement.style.opacity = "0.4";
    }

    if (cellDate.toDateString() === today.toDateString()) {
      dayElement.classList.add("today");
    }

    // Check for events on this date
    if (events[dateStr] && events[dateStr].length > 0) {
      dayElement.classList.add("has-event");

      const eventCount = events[dateStr].length;
      const eventLabel = document.createElement("span");
      eventLabel.className = "event-name";
      eventLabel.textContent =
        eventCount > 1 ? `${eventCount} Events` : events[dateStr][0].name;
      dayElement.appendChild(document.createElement("br"));
      dayElement.appendChild(eventLabel);

      dayElement.addEventListener("click", () =>
        showEventDetails(dateStr, events[dateStr])
      );
    }

    calendarDays.appendChild(dayElement);
  }

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

function showEventDetails(date, eventList) {
  // Fix: Create date without timezone conversion
  const dateParts = date.split("-");
  const displayDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
  const formattedDate = displayDate.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

  document.getElementById(
    "eventModalTitle"
  ).textContent = `Events on ${formattedDate}`;

  let eventHTML = "";
  eventList.forEach((event) => {
    eventHTML += `
      <div style="margin-bottom: 15px; padding: 10px; border-left: 4px solid var(--primary-purple); background: #f8f9fa;">
        <p><strong>Event:</strong> ${event.name}</p>
        <p><strong>Club:</strong> ${event.club}</p>
      </div>
    `;
  });

  document.getElementById("eventModalBody").innerHTML = eventHTML;
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
