<?php
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Enhanced PDF Appendix Helper Functions
 * Provides better error handling and logging for PDF operations
 */

// ─────────────────────────────
// 1. Enhanced PDF Append Function (without individual separator pages)
// ─────────────────────────────
function appendPDF($fpdi, $filePath, $description = "Unknown PDF", $addSeparator = false)
{
    try {
        // Validate file existence
        if (!file_exists($filePath)) {
            error_log("PDF Merge Warning: File not found - $filePath ($description)");
            return false;
        }

        // Validate file size
        if (filesize($filePath) == 0) {
            error_log("PDF Merge Warning: Empty file - $filePath ($description)");
            return false;
        }

        // Validate MIME type
        $mime_type = mime_content_type($filePath);
        if ($mime_type !== 'application/pdf') {
            error_log("PDF Merge Warning: Invalid file type ($mime_type) - $filePath ($description)");
            return false;
        }

        // Add separator only if requested (only for first appendix)
        if ($addSeparator) {
            $fpdi->AddPage();
            $fpdi->SetFont('times', 'B', 16);
            $fpdi->Cell(0, 30, '', 0, 1); // spacing from top
            $fpdi->Cell(0, 15, 'APPENDIX', 0, 1, 'C');
            $fpdi->SetLineWidth(0.5);
            $fpdi->Line(50, $fpdi->GetY() + 5, 160, $fpdi->GetY() + 5); // Centered line
            $fpdi->Ln(20);

            // Add description of appendix contents
            $fpdi->SetFont('times', '', 12);
            $fpdi->Cell(0, 8, 'This section contains:', 0, 1, 'L');
            $fpdi->Cell(0, 6, '• COCU Statements from committee members', 0, 1, 'L');
            $fpdi->Cell(0, 6, '• Additional event information and documents', 0, 1, 'L');
        }

        // Import and append PDF pages
        $pageCount = $fpdi->setSourceFile($filePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $fpdi->importPage($i);
            $size = $fpdi->getTemplateSize($tplId);

            // Maintain aspect ratio and orientation
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $fpdi->AddPage($orientation, [$size['width'], $size['height']]);
            $fpdi->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
        }

        return true;

    } catch (Exception $e) {
        error_log("PDF Merge Error: " . $e->getMessage() . " - File: $filePath ($description)");
        return false;
    }
}

// ─────────────────────────────
// 2. Enhanced Merge Function for Proposal Report
// ─────────────────────────────
function mergeAppendices($temp_file, $event, $budget_summary, $cocu_pdfs)
{
    try {
        $finalPdf = new Fpdi();

        // Set PDF metadata
        $finalPdf->SetCreator('Nilai University - Event Management System');
        $finalPdf->SetAuthor($event['Stu_Name'] ?? 'Student');
        $finalPdf->SetTitle('Complete Event Proposal: ' . $event['Ev_Name']);
        $finalPdf->SetSubject('Event Proposal with Appendices');

        // Validate main PDF file
        if (!file_exists($temp_file)) {
            throw new Exception("Main PDF file not found: $temp_file");
        }

        if (filesize($temp_file) == 0) {
            throw new Exception("Main PDF file is empty: $temp_file");
        }

        // Import main PDF pages
        $pageCount = $finalPdf->setSourceFile($temp_file);
        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $finalPdf->importPage($i);
            $size = $finalPdf->getTemplateSize($tplId);
            $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $finalPdf->useTemplate($tplId);
        }

        // Track if any appendices were added
        $appendix_count = 0;
        $first_appendix = true;

        // Append Additional Information PDF if exists
        if (!empty($event['Ev_AdditionalInfo'])) {
            $additional_info_path = $event['Ev_AdditionalInfo'];
            if (appendPDF($finalPdf, $additional_info_path, "Additional Event Information", $first_appendix)) {
                $appendix_count++;
                $first_appendix = false; // Only show separator page once
            }
        }
        // Append Budget Statement PDF (Post-Event)
        if (!empty($event['statement'])) {
            $statement_file = basename((string) $event['statement']); // ensure plain filename

            // Use the confirmed working base: one level up from components/pdf/
            $paths = [
                __DIR__ . "/../uploads/statements/{$statement_file}",          // ✅ your confirmed working path
                __DIR__ . "/../uploads/budget_statements/{$statement_file}",   // optional: second location if you ever move it
                // Try the original value as-is (if DB ever stores a full/relative path)
                (string) $event['statement'],
            ];

            foreach ($paths as $path) {
                if (is_file($path)) {
                    if (appendPDF($finalPdf, $path, "Budget Statement", $first_appendix)) {
                        $appendix_count++;
                        $first_appendix = false; // Only show separator once
                        break; // stop after first found file
                    }
                } else {
                    error_log("Budget Statement not found at: {$path}");
                }
            }
        }

        // Append COCU Statement PDFs
        if (!empty($cocu_pdfs) && is_array($cocu_pdfs)) {
            foreach ($cocu_pdfs as $cocu_file) {
                if (!empty($cocu_file)) {
                    if (appendPDF($finalPdf, $cocu_file, "COCU Statement", $first_appendix)) {
                        $appendix_count++;
                        $first_appendix = false; // Only show separator page once
                    }
                }
            }

        }




        // If no appendices were added, add a note
        if ($appendix_count == 0) {
            $finalPdf->AddPage();
            $finalPdf->SetFont('times', 'B', 16);
            $finalPdf->Cell(0, 30, '', 0, 1); // spacing from top
            $finalPdf->Cell(0, 15, 'APPENDIX', 0, 1, 'C');
            $finalPdf->SetLineWidth(0.5);
            $finalPdf->Line(50, $finalPdf->GetY() + 5, 160, $finalPdf->GetY() + 5);
            $finalPdf->Ln(20);

            $finalPdf->SetFont('times', '', 12);
            $finalPdf->Cell(0, 10, 'No additional documents or COCU statements attached.', 0, 1, 'C');
        }

        // Generate safe filename
        $event_id_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $event['Ev_ID']);
        $event_name_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', substr($event['Ev_Name'], 0, 30));
        $filename = "Event_Proposal_{$event_id_safe}_{$event_name_safe}.pdf";

        // Output the final merged PDF
        $finalPdf->Output($filename, 'D');

    } catch (Exception $e) {
        error_log("PDF Merge Fatal Error: " . $e->getMessage());

        // Try to output a basic error PDF instead of dying
        try {
            $errorPdf = new TCPDF();
            $errorPdf->AddPage();
            $errorPdf->SetFont('times', 'B', 16);
            $errorPdf->Cell(0, 20, 'PDF Generation Error', 0, 1, 'C');
            $errorPdf->SetFont('times', '', 12);
            $errorPdf->MultiCell(0, 10, 'An error occurred while generating the complete PDF document. Please contact the system administrator.', 0, 'C');
            $errorPdf->Output('Error_Report.pdf', 'D');
        } catch (Exception $fallback_error) {
            die("Critical Error: Unable to generate PDF document. Please try again or contact support.");
        }
    }
}

/**
 * Utility function to validate PDF files before processing
 */
function validatePDFFile($filepath, $description = "PDF")
{
    $errors = [];

    if (empty($filepath)) {
        $errors[] = "$description: File path is empty";
    }

    if (!file_exists($filepath)) {
        $errors[] = "$description: File does not exist - $filepath";
    }

    if (file_exists($filepath) && filesize($filepath) == 0) {
        $errors[] = "$description: File is empty - $filepath";
    }

    if (file_exists($filepath) && mime_content_type($filepath) !== 'application/pdf') {
        $errors[] = "$description: File is not a valid PDF - $filepath";
    }

    return $errors;
}

/**
 * Log PDF operation details for debugging
 */
function logPDFOperation($operation, $details)
{
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] PDF Operation: $operation - $details";
    error_log($log_message);
}
?>