<?php
use setasign\Fpdi\Tcpdf\Fpdi;

// ─────────────────────────────
// 1. Helper to Append PDF File
// ─────────────────────────────
function appendPDF($fpdi, $filePath)
{
    if (!file_exists($filePath)) {
        error_log("File not found: $filePath");
        return;
    }

    if (mime_content_type($filePath) !== 'application/pdf') {
        error_log("Not a valid PDF: $filePath");
        return;
    }

    $pageCount = $fpdi->setSourceFile($filePath);
    for ($i = 1; $i <= $pageCount; $i++) {
        $tplId = $fpdi->importPage($i);
        $size = $fpdi->getTemplateSize($tplId);
        $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $fpdi->useTemplate($tplId);
    }
}

// ─────────────────────────────
// 2. Merge for Proposal Report
// ─────────────────────────────
function mergeAppendices($temp_file, $event, $budget_summary, $cocu_pdfs)
{
    $finalPdf = new Fpdi();

    // Merge generated TCPDF pages
    $pageCount = $finalPdf->setSourceFile($temp_file);
    for ($i = 1; $i <= $pageCount; $i++) {
        $tplId = $finalPdf->importPage($i);
        $size = $finalPdf->getTemplateSize($tplId);
        $finalPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $finalPdf->useTemplate($tplId);
    }

    // Append Additional Info PDF
    if (!empty($event['Ev_AdditionalInfo']) && file_exists($event['Ev_AdditionalInfo'])) {
        appendPDF($finalPdf, $event['Ev_AdditionalInfo']);
    }

    // Append COCU PDFs
    if (!empty($cocu_pdfs) && is_array($cocu_pdfs)) {
        foreach ($cocu_pdfs as $file) {
            if (!empty($file) && file_exists($file)) {
                appendPDF($finalPdf, $file);
            }
        }
    }

    // Output merged proposal
    $event_id_safe = str_replace("/", "_", $event['Ev_ID']);
    $finalPdf->Output("Event_{$event_id_safe}.pdf", 'D');
}
