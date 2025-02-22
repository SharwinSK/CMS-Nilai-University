<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['action']) && $input['action'] === 'logout') {
        // Get user type from session
        $userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

        // Destroy session
        session_unset();
        session_destroy();

        // Determine redirect URL based on user type
        $redirectUrl = 'StudentLogin.php'; // Default fallback
        if ($userType === 'coordinator') {
            $redirectUrl = 'CoordinatorLogin.php';
        } elseif ($userType === 'advisor') {
            $redirectUrl = 'AdvisorLogin.php';
        }

        // Return JSON response with redirect URL
        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
        exit();
    }
}

// Redirect to a default login page if accessed directly
header('Location: Index.php');
exit();
?>