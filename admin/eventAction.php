<?php
session_start();
include '../dbconfig.php';

// Get parameters
$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

// Validation
$validActions = ['view', 'edit', 'delete', 'export'];
$validTypes = ['proposal', 'report', 'completed'];

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
        'proposal' => '../generate_pdf.php',
        'report',
        'completed' => '../reportgeneratepdf.php',
    };
    header("Location: $pdfPage?id=" . urlencode($id));
    exit();
}

// ───────────────────────────────────────
// DELETE (based on type)
if ($action === 'delete') {
    if (!isset($_SESSION['Admin_ID'])) {
        die("Unauthorized.");
    }

    [$table, $column] = match ($type) {
        'proposal' => ['events', 'Ev_ID'],
        'report' => ['eventpostmortem', 'Ev_ID'],
        'completed' => ['events', 'Ev_ID'],
    };

    $stmt = $conn->prepare("DELETE FROM $table WHERE $column = ?");
    $stmt->bind_param("s", $id);

    if ($stmt->execute()) {
        header("Location: eventmanagement.php?msg=deleted");
    } else {
        header("Location: eventmanagement.php?msg=delete_failed");
    }
    exit();
}
?>