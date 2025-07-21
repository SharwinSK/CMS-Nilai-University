function showFeedback(type, feedbackText, eventId) {
  const modalElement = document.getElementById("feedbackModal");
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);

  // Update modal content
  const titleElement = document.getElementById("feedbackTitle");
  const contentElement = document.getElementById("feedbackContent");

  titleElement.textContent = `${type} Feedback - ${eventId}`;
  contentElement.innerHTML = feedbackText
    ? `<div class="feedback-content">${feedbackText}</div>`
    : '<div class="no-data">No feedback available yet.</div>';

  // Show modal
  modalInstance.show();
}

function editProposal(eventId) {
  // Redirect to edit proposal page
  window.location.href = `../student/proposal/EditProposal.php?id=${eventId}`;
}

function modifyProposal(eventId) {
  // Redirect to modify proposal page
  window.location.href = `../student/proposal/ModifyProposal.php?id=${eventId}`;
}

function deleteProposal(eventId) {
  if (confirm(`Are you sure you want to delete the proposal for ${eventId}?`)) {
    fetch(`../student/proposal/DeleteProposal.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `event_id=${eventId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Server response:", data);
        if (data.success) {
          alert("Proposal deleted successfully!");
          location.reload();
        } else {
          alert("Error deleting proposal: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        alert("Error deleting proposal (JS error)");
      });
  }
}

function createPostmortem(eventId) {
  // Redirect to create postmortem page
  window.location.href = `../student/postevent/Postmortem.php?event_id=${eventId}`;
}

function exportProposal(eventId) {
  // Generate and download proposal PDF
  window.open(`../components/pdf/generate_pdf.php?id=${eventId}`, "_blank");
}

function editPostEvent(eventId) {
  // Redirect to edit post event page
  window.location.href = `../student/postevent/Postmortem.php?id=${eventId}`;
}

function modifyPostEvent(eventId) {
  // Redirect to modify post event page
  window.location.href = `../student/postevent/Postmortem.php?id=${eventId}`;
}

function exportPostEvent(eventId) {
  // Generate and download post event report PDF
  window.open(
    `../components/pdf/reportgeneratepdf.php?id=${eventId}`,
    "_blank"
  );
}

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  // Any initialization code here
  console.log("Event Track Progress page loaded");
});
