// Updated JavaScript functions for stuprogresspage.js

// Original function (keep this for proposal feedback)
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

// New function for data attributes approach (recommended)
function showFeedbackFromData(buttonElement) {
  const type = buttonElement.getAttribute("data-feedback-type");
  const feedbackText = buttonElement.getAttribute("data-feedback-text");
  const eventId = buttonElement.getAttribute("data-event-id");

  const modalElement = document.getElementById("feedbackModal");
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);

  // Update modal content
  const titleElement = document.getElementById("feedbackTitle");
  const contentElement = document.getElementById("feedbackContent");

  titleElement.textContent = `${type} Feedback - ${eventId}`;
  contentElement.innerHTML =
    feedbackText && feedbackText.trim() !== ""
      ? `<div class="feedback-content">${feedbackText}</div>`
      : '<div class="no-data">No feedback available yet.</div>';

  // Show modal
  modalInstance.show();
}

// Rest of your existing functions remain the same...
function editProposal(eventId) {
  window.location.href = `../student/proposal/editModifyForm.php?mode=edit&id=${eventId}`;
}

function modifyProposal(eventId) {
  window.location.href = `../student/proposal/editModifyForm.php?mode=modify&id=${eventId}`;
}

function viewProposal(eventId) {
  window.location.href = `../model/viewProposal.php?id=${eventId}`;
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
  window.location.href = `../student/postevent/PostEvent_Form.php?mode=create&event_id=${eventId}`;
}

function exportProposal(eventId) {
  window.open(`../components/pdf/generate_pdf.php?id=${eventId}`, "_blank");
}

function editPostEvent(repId) {
  window.location.href = `../student/postevent/PostEventEdit_Form.php?mode=edit&rep_id=${repId}`;
}

function modifyPostEvent(repId) {
  window.location.href = `../student/postevent/PostEventEdit_Form.php?mode=modify&rep_id=${repId}`;
}

function viewPostEvent(repId) {
  window.location.href = `../model/viewPostEvent.php?rep_id=${repId}`;
}

function exportPostEvent(eventId) {
  window.open(
    `../components/pdf/reportgeneratepdf.php?id=${eventId}`,
    "_blank"
  );
}

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  console.log("Event Track Progress page loaded");
});
