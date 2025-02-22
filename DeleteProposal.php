<?php
session_start();
include('dbconfig.php');

if (!isset($_SESSION['Stu_ID'])) {
    header("Location: StudentLogin.php");
    exit();
}

if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']); 
    $stu_id = $_SESSION['Stu_ID']; 

    $check_query = "SELECT * FROM events WHERE Ev_ID = ? AND Stu_ID = ? AND 
    (Ev_Status = 'Sent Back by Advisor' OR Ev_Status = 'Rejected by Coordinator')";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $event_id, $stu_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $delete_query = "DELETE FROM events WHERE Ev_ID = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $event_id);

        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Proposal deleted successfully.";
        } else {
            $_SESSION['message'] = "Error deleting the proposal.";
        }
        $delete_stmt->close();
    } else {
        $_SESSION['message'] = "Invalid request. Proposal cannot be deleted.";
    }
    $stmt->close();
} else {
    $_SESSION['message'] = "Invalid request. Event ID missing.";
}

header("Location: ProgressPage.php");
exit();
?>