<?php
session_start();

// ─── Access Protection ─────────────────────────────
if (!isset($_SESSION['Stu_ID']) && !isset($_SESSION['Adv_ID']) && !isset($_SESSION['Coor_ID']) && !isset($_SESSION['Admin_ID'])) {
    die("Access denied.");
}

// ─── Validate and Sanitize Input ───────────────────
if (!isset($_GET['file'])) {
    die("No file specified.");
}

$filename = basename(urldecode($_GET['file']));

// ─── Allow Only Certain Extensions ─────────────────
$allowed_extensions = ['pdf', 'docx', 'png', 'jpg'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_extensions)) {
    die("Invalid file type.");
}

// ─── Define File Paths to Search ───────────────────
$paths = [
    __DIR__ . "/uploads/cocustatement/$filename",
    __DIR__ . "/uploads/individual_reports/$filename",
    __DIR__ . "/uploads/additional/$filename",      // for Ev_AdditionalInfo

];

// ─── File Handling ─────────────────────────────────
foreach ($paths as $path) {
    if (file_exists($path)) {
        $mime_types = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
        ];

        header("Content-Type: " . $mime_types[$ext]);
        header("Content-Disposition: inline; filename=\"$filename\"");
        header("Content-Length: " . filesize($path));
        readfile($path);
        exit;
    }
}

echo "File not found.";
?>