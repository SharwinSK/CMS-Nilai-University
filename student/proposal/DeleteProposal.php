<?php
session_start();
include('../../db/dbconfig.php');

header('Content-Type: application/json');

if (!isset($_SESSION['Stu_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (isset($_POST['event_id'])) {
    $event_id = $_POST['event_id']; // no intval here
    $stu_id = $_SESSION['Stu_ID'];

    // Validate the event belongs to the student and is rejected
    $check_query = "
        SELECT * FROM events 
        WHERE Ev_ID = ? AND Stu_ID = ? 
        AND Status_ID IN (
            SELECT Status_ID FROM eventstatus 
            WHERE Status_Name = 'Rejected by Advisor' OR Status_Name = 'Rejected by Coordinator'
        )
    ";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $event_id, $stu_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // Proceed to delete
        $delete_query = "DELETE FROM events WHERE Ev_ID = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("s", $event_id); // use 's' here

        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete']);
        }
        $delete_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or unauthorized delete']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing event_id']);
}
?>