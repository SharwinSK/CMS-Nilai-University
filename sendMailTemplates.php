<?php
include 'sendMail.php';

// 1. New Proposal → Advisor Only
function newProposalToAdvisor($studentName, $eventName, $advisorName, $advisorEmail)
{
    $subject = "New Proposal Submitted";
    $body = "Hi $advisorName, a new event proposal titled \"$eventName\" has been submitted by $studentName. Please review it.";
    sendNotificationEmail($advisorEmail, $subject, $body);
}

// 2. Advisor Approved → Notify Student + Coordinator
function advisorApproved($studentName, $eventName, $studentEmail, $coordinatorEmail, $clubName)
{
    $subjectStudent = "Proposal Approved";
    $bodyStudent = "Hi $studentName, your proposal \"$eventName\" was approved by Advisor. Please log in for more info.";
    sendNotificationEmail($studentEmail, $subjectStudent, $bodyStudent);

    $subjectCoor = "New Proposal Approved by Advisor";
    $bodyCoor = "Hi Coordinator, a new event proposal titled \"$eventName\" from $studentName under the club \“$clubName\” has been approved by Advisor. Please review it.";
    sendNotificationEmail($coordinatorEmail, $subjectCoor, $bodyCoor);
}



// 4. Rejected by Advisor → Notify Student Only
function advisorRejected($studentName, $eventName, $studentEmail)
{
    $subject = "Proposal Rejected";
    $body = "Hi $studentName, your proposal \"$eventName\" was rejected by Advisor. Please log in for more info.";
    sendNotificationEmail($studentEmail, $subject, $body);
}

function coordinatorApproved($studentName, $eventName, $studentEmail, $advisorEmail)
{
    $subject = "Proposal Approved";
    $body = "Hi, your proposal \"$eventName\" was approved by Coordinator. Please log in for more info.";

    sendNotificationEmail($studentEmail, $subject, $body);
    if (!empty($advisorEmail)) {
        sendNotificationEmail($advisorEmail, $subject, $body);
    }
}

function coordinatorRejected($studentName, $eventName, $studentEmail)
{
    $subject = "Proposal Rejected";
    $body = "Hi $studentName, your proposal \"$eventName\" was rejected by Coordinator. Please log in for more info.";
    sendNotificationEmail($studentEmail, $subject, $body);
}


?>