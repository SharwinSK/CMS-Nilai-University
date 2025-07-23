  // Event Chart
        const ctx = document.getElementById("eventChart").getContext("2d");
        let eventChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Academic", "Cultural", "Sports", "Technology", "Social"],
                datasets: [
                    {
                        label: "Approved",
                        data: [12, 8, 15, 6, 10],
                        backgroundColor: "#28a745",
                        borderColor: "#28a745",
                        borderWidth: 1,
                    },
                    {
                        label: "Pending",
                        data: [3, 2, 4, 1, 2],
                        backgroundColor: "#ffc107",
                        borderColor: "#ffc107",
                        borderWidth: 1,
                    },
                    {
                        label: "Rejected",
                        data: [1, 0, 2, 0, 1],
                        backgroundColor: "#dc3545",
                        borderColor: "#dc3545",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Events by Category and Status",
                    },
                    legend: {
                        position: "top",
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                        },
                    },
                },
            },
        });

        // Filter functionality
        document
            .getElementById("yearFilter")
            .addEventListener("change", updateChart);
        document
            .getElementById("monthFilter")
            .addEventListener("change", updateChart);
        document
            .getElementById("eventTypeFilter")
            .addEventListener("change", updateChart);

        function updateChart() {
            // Simulate data update based on filters
            const year = document.getElementById("yearFilter").value;
            const month = document.getElementById("monthFilter").value;
            const eventType = document.getElementById("eventTypeFilter").value;

            // In real application, this would fetch data from your PHP backend
            console.log(
                `Filtering: Year=${year}, Month=${month}, Type=${eventType}`
            );

            // Update chart with filtered data (example)
            eventChart.update();
        }

        // Calendar functionality
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();

        const months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December",
        ];




        function generateCalendar(month, year) {
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            document.getElementById(
                "currentMonth"
            ).textContent = `${months[month]} ${year}`;

            const calendarDays = document.getElementById("calendarDays");
            calendarDays.innerHTML = "";

            // Previous month's trailing days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                const dayElement = document.createElement("div");
                dayElement.className = "calendar-day other-month";
                dayElement.innerHTML = `<span>${day}</span>`;
                calendarDays.appendChild(dayElement);
            }

            // Current month's days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement("div");
                dayElement.className = "calendar-day";

                const dateKey = `${year}-${String(month + 1).padStart(
                    2,
                    "0"
                )}-${String(day).padStart(2, "0")}`;

                if (events[dateKey]) {
                    dayElement.classList.add("has-event");
                    dayElement.innerHTML = `<span>${day}</span><div class="event-dot"></div>`;

                    dayElement.addEventListener("click", function () {
                        const eventList = events[dateKey];
                        let content = "<ul>";
                        eventList.forEach((ev) => {
                            content += `<li><strong>${ev.name}</strong><br><small class="text-muted">${ev.club}</small></li><hr>`;
                        });
                        content += "</ul>";

                        document.getElementById("eventModalBody").innerHTML = content;
                        new bootstrap.Modal(document.getElementById("eventModal")).show();
                    });
                }
                else {
                    dayElement.innerHTML = `<span>${day}</span>`;
                }

                calendarDays.appendChild(dayElement);
            }

            // Next month's leading days
            const totalCells = calendarDays.children.length;
            const remainingCells = 42 - totalCells; // 6 rows × 7 days

            for (let day = 1; day <= remainingCells; day++) {
                const dayElement = document.createElement("div");
                dayElement.className = "calendar-day other-month";
                dayElement.innerHTML = `<span>${day}</span>`;
                calendarDays.appendChild(dayElement);
            }
        }

        document.getElementById("prevMonthBtn").addEventListener("click", () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            generateCalendar(currentMonth, currentYear);
        });

        document.getElementById("nextMonthBtn").addEventListener("click", () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            generateCalendar(currentMonth, currentYear);
        });

        // Initialize calendar
        generateCalendar(currentMonth, currentYear);

        // View notification function
        function viewNotification(eventName) {
            alert(`Viewing details for: ${eventName}`);
            // In real application, this would open a modal or navigate to detail page
        }

        // Initialize on page load
        document.addEventListener("DOMContentLoaded", function () {
            // Any additional initialization code
            console.log("Advisor Dashboard loaded successfully");
        });
