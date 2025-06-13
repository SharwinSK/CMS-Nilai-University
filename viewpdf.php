<?php
if (!isset($_GET['file'])) {
    die("No file specified.");
}

$filename = basename(urldecode($_GET['file'])); // clean filename only

$paths = [
    __DIR__ . '/uploads/cocustatement/' . $filename,
    __DIR__ . '/uploads/individual_reports/' . $filename
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=\"" . $filename . "\"");
        header("Content-Length: " . filesize($path));
        readfile($path);
        exit;
    }
}

echo "File not found.";
?>