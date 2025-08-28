<?php
session_start();
include '../db/dbconfig.php';

// Get parameters
$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

// Validation
$validActions = ['view', 'edit', 'delete', 'export'];
$validTypes = ['proposal', 'report', 'completed', 'abandoned'];


if (!in_array($action, $validActions) || !in_array($type, $validTypes) || empty($id)) {
    die("Invalid request.");
}

// ───────────────────────────────────────
// VIEW / EDIT → Redirect to previewModal.php with mode
if ($action === 'view' || $action === 'edit') {
    $mode = $action === 'view' ? 'view' : 'edit';
    header("Location: previewModal.php?type=$type&id=$id&mode=$mode");
    exit();
}

// ───────────────────────────────────────
// EXPORT PDF
if ($action === 'export') {
    $pdfPage = match ($type) {
        'proposal' => '../components/pdf/generate_pdf.php',
        'report',
        'completed' => '../components/pdf/reportgeneratepdf.php',
    };
    header("Location: $pdfPage?id=" . urlencode($id));
    exit();
}

// DELETE (based on type)
if ($action === 'delete') {
    if (!isset($_SESSION['Admin_ID'])) {
        die("Unauthorized.");
    }

    switch ($type) {
        case 'proposal':
        case 'completed':
            $stmt = $conn->prepare("DELETE FROM events WHERE Ev_ID = ?");
            break;

        case 'report':
            $stmt = $conn->prepare("DELETE FROM eventpostmortem WHERE Ev_ID = ?");
            break;

        case 'abandoned': // NEW CASE for No Activity events
            $stmt = $conn->prepare("DELETE FROM events WHERE Ev_ID = ?");
            break;

        default:
            die("Invalid delete type.");
    }

    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        header("Location: eventmanagement.php?msg=deleted");
    } else {
        header("Location: eventmanagement.php?msg=delete_failed");
    }
    exit();
}

?>