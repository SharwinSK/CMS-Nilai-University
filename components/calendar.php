<!-- Event Calendar -->
<div class="calendar-container">
    <h4 class="section-title">
        <i class="fas fa-calendar-alt me-2"></i>
        Event Calendar
    </h4>
    <div class="calendar-header">
        <div class="calendar-nav-btn" onclick="previousMonth()">
            <i class="fas fa-chevron-left"></i>
        </div>
        <h5 class="calendar-month-title" id="currentMonth">July 2025</h5>
        <div class="calendar-nav-btn" onclick="nextMonth()">
            <i class="fas fa-chevron-right"></i>
        </div>
    </div>
    <div class="calendar-wrapper">
        <div class="calendar-grid">
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
        </div>
        <div id="calendarDays" class="calendar-grid"></div>
    </div>
</div>
