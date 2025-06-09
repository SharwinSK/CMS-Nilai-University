<?php
$file = $_GET['file']; // e.g., uploads/cocustatement/example.pdf
$filepath = htmlspecialchars($file);

if (file_exists($filepath)) {
    header("Content-type: application/pdf");
    header("Content-Disposition: inline; filename=\"" . basename($filepath) . "\"");
    readfile($filepath);
    exit;
} else {
    echo "File not found.";
}
?>