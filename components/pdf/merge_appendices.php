<?php
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Enhanced PDF Appendix Helper Functions
 * Provides better error handling and logging for PDF operations
 */

// ─────────────────────────────
// 1. Enhanced PDF Append Function
// ─────────────────────────────
function appendPDF($fpdi, $filePath, $description = "Unknown PDF")
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

        // Add a separator page for appendices
        $fpdi->AddPage();
        $fpdi->SetFont('times', 'B', 14);
        $fpdi->Cell(0, 20, '', 0, 1); // spacing
        $fpdi->Cell(0, 10, 'APPENDIX: ' . strtoupper($description), 0, 1, 'C');
        $fpdi->Line(15, $fpdi->GetY() + 2, 195, $fpdi->GetY() + 2);

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

        // Track successful appendices
        $appendix_count = 0;

        // Append Additional Information PDF if exists
        if (!empty($event['Ev_AdditionalInfo'])) {
            $additional_info_path = $event['Ev_AdditionalInfo'];
            if (appendPDF($finalPdf, $additional_info_path, "Additional Event Information")) {
                $appendix_count++;
            }
        }

        // Append COCU Statement PDFs
        if (!empty($cocu_pdfs) && is_array($cocu_pdfs)) {
            $cocu_counter = 1;
            foreach ($cocu_pdfs as $cocu_file) {
                if (!empty($cocu_file)) {
                    $description = "COCU Statement " . $cocu_counter;
                    if (appendPDF($finalPdf, $cocu_file, $description)) {
                        $appendix_count++;
                        $cocu_counter++;
                    }
                }
            }
        }

        // Add appendices summary page if any were added
        if ($appendix_count > 0) {
            $finalPdf->AddPage();
            $finalPdf->SetFont('times', 'B', 14);
            $finalPdf->Cell(0, 20, 'APPENDICES SUMMARY', 0, 1, 'C');
            $finalPdf->SetFont('times', '', 12);
            $finalPdf->Cell(0, 10, "Total appendices included: $appendix_count", 0, 1, 'L');
            $finalPdf->Ln(5);

            // List appendices
            $summary_text = "This document contains the following appendices:\n";
            if (!empty($event['Ev_AdditionalInfo']) && file_exists($event['Ev_AdditionalInfo'])) {
                $summary_text .= "• Additional Event Information\n";
            }

            $cocu_count = count(array_filter($cocu_pdfs ?? [], function ($file) {
                return !empty($file) && file_exists($file);
            }));

            if ($cocu_count > 0) {
                $summary_text .= "• COCU Statements ($cocu_count documents)\n";
            }

            $finalPdf->MultiCell(0, 6, $summary_text, 0, 'L');
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