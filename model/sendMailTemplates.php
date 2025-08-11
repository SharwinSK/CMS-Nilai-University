<?php
include '../../auth/sendMail.php';

// 1. New Proposal → Advisor
function newProposalToAdvisor($studentName, $eventName, $advisorName, $advisorEmail)
{
    $subject = "New Event Proposal Submitted";
    $body = "Dear $advisorName,\n\nA new event proposal titled \"$eventName\" has been submitted by $studentName. Kindly log in to review and take the necessary action.\n\nThank you.";
    sendNotificationEmail($advisorEmail, $subject, $body);
}

// 2. Advisor Approved → Student + Coordinator
function advisorApproved($studentName, $eventName, $studentEmail, $coordinatorEmail, $clubName)
{
    // To Student
    $subjectStudent = "Proposal Approved by Advisor";
    $bodyStudent = "Dear $studentName,\n\nYour event proposal titled \"$eventName\" has been approved by your advisor. It has now been forwarded for coordinator review.\n\nThank you.";
    sendNotificationEmail($studentEmail, $subjectStudent, $bodyStudent);

    // To Coordinator
    $subjectCoor = "New Proposal Awaiting Your Review";
    $bodyCoor = "Dear Coordinator,\n\nA new event proposal titled \"$eventName\" has been submitted by $studentName under the club \"$clubName\". Please review it at your earliest convenience.\n\nThank you.";
    sendNotificationEmail($coordinatorEmail, $subjectCoor, $bodyCoor);
}

// 3. Advisor Rejected → Student
function advisorRejected($studentName, $eventName, $studentEmail)
{
    $subject = "Proposal Rejected by Advisor";
    $body = "Dear $studentName,\n\nYour event proposal titled \"$eventName\" has been rejected by your advisor. Please make the necessary modifications and resubmit it for review.\n\nThank you.";
    sendNotificationEmail($studentEmail, $subject, $body);
}

// 4. Coordinator Approved → Student + Advisor
function coordinatorApproved($eventName, $studentEmail, $advisorEmail)
{
    $subject = "Proposal Approved by Coordinator";
    $body = "Dear Participant,\n\nThe event proposal titled \"$eventName\" has been approved by the coordinator. Your event is now officially approved.\n\nThank you.";

    sendNotificationEmail($studentEmail, $subject, $body);
    if (!empty($advisorEmail)) {
        sendNotificationEmail($advisorEmail, $subject, $body);
    }
}

// 5. Coordinator Rejected → Student + Advisor
function coordinatorRejected($eventName, $studentEmail, $advisorEmail)
{
    $subject = "Proposal Rejected by Coordinator";
    $body = "Dear Participant,\n\nThe event proposal titled \"$eventName\" has been rejected by the coordinator. Please review the comments and resubmit if necessary.\n\nThank you.";

    sendNotificationEmail($studentEmail, $subject, $body);
    if (!empty($advisorEmail)) {
        sendNotificationEmail($advisorEmail, $subject, $body);
    }
}

// 6. Student Resubmits Modified Proposal → Coordinator
function modifiedProposalToCoordinator($coordinatorName, $eventName, $clubName, $studentName, $coordinatorEmail)
{
    $subject = "Modified Proposal Resubmitted";
    $body = "Dear $coordinatorName,\n\nA modified proposal titled \"$eventName\" from the club \"$clubName\" has been resubmitted by $studentName. Please review the updated version.\n\nThank you.";
    sendNotificationEmail($coordinatorEmail, $subject, $body);
}

// 7. Post-Event Submitted by Student → Coordinator
function postEventSubmitted($coordinatorName, $eventName, $studentName, $coordinatorEmail)
{
    $subject = "Post-Event Report Submitted";
    $body = "Dear $coordinatorName,\n\nThe post-event report for \"$eventName\" has been submitted by $studentName. Kindly review the report.\n\nThank you.";
    sendNotificationEmail($coordinatorEmail, $subject, $body);
}

// 8. Post-Event Approved by Coordinator → Student + Advisor
function postEventApproved($eventName, $studentEmail, $advisorEmail, $advisorName)
{
    // Email to student
    $subjectStudent = "Post-Event Report Approved";
    $bodyStudent = "Dear Participant,\n\nThe post-event report for \"$eventName\" has been reviewed and approved. Thank you for organizing and participating in the event.\n\nRegards.";
    sendNotificationEmail($studentEmail, $subjectStudent, $bodyStudent);

    // Email to advisor
    if (!empty($advisorEmail)) {
        $subjectAdvisor = "Post-Event Report Approved";
        $bodyAdvisor = "Dear $advisorName,\n\nThe post-event report for \"$eventName\" has been reviewed and approved. This marks the successful completion of the event process.\n\nThank you for your guidance.\n\nRegards.";
        sendNotificationEmail($advisorEmail, $subjectAdvisor, $bodyAdvisor);
    }
}

// 9. Post-Event Rejected by Coordinator → Student
function postEventRejected($eventName, $studentName, $studentEmail)
{
    $subject = "Post-Event Report Rejected";
    $body = "Dear $studentName,\n\nYour post-event report for \"$eventName\" has been rejected by the coordinator. Please make the necessary modifications and resubmit it.\n\nThank you.";
    sendNotificationEmail($studentEmail, $subject, $body);
}
?>