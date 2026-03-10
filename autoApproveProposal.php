<?php
/* This script is intended to be run as a scheduled task to automatically approve proposals that have been pending for
more than 2 days. It updates the status of the proposal to "Coordinator Review" and sends an email notification to the student, advisor,
and coordinator.*/


include('db/dbconfig.php');
require_once 'model/sendMailTemplates.php';

// Find overdue proposals
$query = "
SELECT e.Ev_ID, e.Ev_Name, s.Stu_Name, s.Stu_Email, a.Adv_Email
FROM events e
LEFT JOIN student s ON e.Stu_ID = s.Stu_ID
LEFT JOIN advisor a ON e.Club_ID = a.Club_ID
WHERE e.Status_ID = 1
AND e.created_at <= NOW() - INTERVAL 2 DAY
";

$result = $conn->query($query);

// Get coordinator email
$coorQuery = "SELECT Coor_Email FROM coordinator LIMIT 1";
$coorResult = $conn->query($coorQuery);
$coordinator = $coorResult->fetch_assoc();
$coordinatorEmail = $coordinator['Coor_Email'];

while ($row = $result->fetch_assoc()) {

    $ev_id = $row['Ev_ID'];

    // Update status to coordinator review
    $stmt = $conn->prepare("
        UPDATE events
        SET Status_ID = 3
        WHERE Ev_ID = ?
    ");

    $stmt->bind_param("s", $ev_id);
    $stmt->execute();

    // Send escalation email
    proposalAutoApproved(
        $row['Ev_Name'],
        $row['Stu_Name'],
        $row['Stu_Email'],
        $row['Adv_Email'],
        $coordinatorEmail
    );
}

echo "Auto approved completed.";

?>