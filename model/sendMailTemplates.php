<?php
// Use $_SERVER['DOCUMENT_ROOT'] for absolute path
require_once __DIR__ . '/../auth/sendMail.php';


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
// Add this function at the bottom of your sendMailTemplates.php file
function sendRegistrationOTP($studentEmail, $otp)
{
    $subject = 'Email Verification - Student Registration';
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>Email Verification</h1>
            </div>
            
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #e9ecef;'>
                <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>
                    Thank you for registering. Please use the following OTP to verify your email:
                </p>
                
                <div style='background: white; padding: 25px; text-align: center; border-radius: 8px; border: 2px solid #007bff; margin: 25px 0;'>
                    <h2 style='color: #007bff; font-size: 36px; margin: 0; letter-spacing: 8px;'>$otp</h2>
                </div>
                
                <p style='color: #666; font-size: 14px;'>• This OTP is valid for 10 minutes only</p>
                <p style='color: #666; font-size: 14px;'>• Do not share this code with anyone</p>
            </div>
        </div>
    ";

    return sendNotificationEmail($studentEmail, $subject, $body);
}

// 10. Post-Event Approved → Committee Members (COCU Claimers)
function postEventApprovedCommittee($eventName, $eventRefNum, $committeeMembers)
{
    $subject = "Event Completion - COCU Points Available for Collection";

    foreach ($committeeMembers as $member) {
        $comName = $member['Com_Name'];
        $comEmail = $member['Com_Email'];

        $body = "Dear $comName,\n\nCongratulations! The event \"$eventName\" has been successfully completed and approved.\n\nEvent Reference Number: $eventRefNum\n\nPlease note this reference number and contact Ms. Rekha to collect your COCU points.\n\nThank you for your participation and contribution to the event.\n\nBest regards,\nCMS Notification System";

        sendNotificationEmail($comEmail, $subject, $body);
    }
}
?>