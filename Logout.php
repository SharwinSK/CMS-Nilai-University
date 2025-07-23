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

        /// Determine redirect URL based on user type
        switch ($userType) {
            case 'admin':
                $redirectUrl = '../index.php';
                break;
            case 'coordinator':
                $redirectUrl = '../auth/coordinatorLogin.php';
                break;
            case 'advisor':
                $redirectUrl = '../auth/advisorLogin.php';
                break;
            default:
                $redirectUrl = '../auth/studentlogin.php';
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