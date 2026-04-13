<?php
/**
 * PDL_Helpdesk — Ticket Export Controller
 *
 * Handles PDF export of tickets.
 *
 * Routes:
 *   GET ?page=tickets/export&mode=all           → export all tickets
 *   GET ?page=tickets/export&mode=filtered&...  → export with current filters
 *
 * Requires mPDF:
 *   composer require mpdf/mpdf
 *
 * mPDF will be auto-loaded from vendor/autoload.php (placed in project root).
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'modules/tickets/models/TicketModel.php';

class TicketExportController extends Controller
{
    private TicketModel $model;

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    // ── Entry Point ───────────────────────────────────────────

    public function export(): void
    {
        Auth::requireLogin();
        RBAC::require('report.export');

        $mode = $this->get('mode', 'all'); // 'all' | 'filtered'

        // ── Collect tickets ────────────────────────────────────
        if ($mode === 'filtered') {
            $filters = $this->collectFilters();
            // No pagination — export everything matching filters
            $result  = $this->model->getPaginated($filters, 1, PHP_INT_MAX);
            $tickets = $result['rows'];
        } else {
            // All tickets sorted oldest → newest
            $tickets = $this->model->getAllForPdfExport();
        }

        if (empty($tickets)) {
            // Redirect back with flash instead of generating empty PDF
            Auth::setFlash('info', 'No tickets found for the selected criteria.');
            header('Location: ' . BASE_URL . '?page=tickets&filter=all');
            exit;
        }

        // ── Load attachment data for each ticket ───────────────
        foreach ($tickets as &$ticket) {
            $ticket['attachments'] = $this->model->getAttachments((int) $ticket['ticket_id']);
        }
        unset($ticket);

        // ── Log the export ─────────────────────────────────────
        AuditLog::record(
            'report.exported',
            'Ticket PDF exported (' . $mode . '). ' . count($tickets) . ' tickets.'
        );

        // ── Generate PDF ───────────────────────────────────────
        $this->generatePdf($tickets, $mode);
    }

    // ── PDF Generation ────────────────────────────────────────

    // private function generatePdf(array $tickets, string $mode): void
    // {
    //     // Load mPDF
    //     $autoload = ROOT_PATH . 'vendor/autoload.php';
    //     if (!file_exists($autoload)) {
    //         http_response_code(500);
    //         echo '<p style="font-family:sans-serif;padding:40px;color:#b91c1c;">
    //               <strong>mPDF not installed.</strong><br>
    //               Run: <code>composer require mpdf/mpdf</code> in the project root.
    //               </p>';
    //         exit;
    //     }
    //     require_once $autoload;

    //     $exportDate  = date('d M Y, H:i');
    //     $totalCount  = count($tickets);
    //     $modeLabel   = $mode === 'filtered' ? 'Filtered Export' : 'Full Export';
    //     $filename    = 'PDL_Tickets_' . strtoupper($mode) . '_' . date('Ymd_His') . '.pdf';

    //     // ── mPDF Setup ─────────────────────────────────────────
    //     $mpdf = new \Mpdf\Mpdf([
    //         'mode'          => 'utf-8',
    //         'format'        => 'A4-L',          // Landscape A4
    //         'margin_top'    => 28,              // room for header
    //         'margin_bottom' => 16,              // room for footer
    //         'margin_left'   => 14,
    //         'margin_right'  => 14,
    //         'tempDir'       => ROOT_PATH . 'logs/',   // writable temp dir
    //     ]);

    //     $mpdf->SetTitle('PDL Helpdesk — Ticket Export');
    //     $mpdf->SetAuthor('PDL Helpdesk System');
    //     $mpdf->shrink_tables_to_fit = 1;

    //     // ── Header (compact, appears on every page) ────────────
    //     $headerHtml = '
    //     <table width="100%" style="border-bottom:1.5px solid #0d9488;padding-bottom:5px;margin-bottom:0;">
    //         <tr>
    //             <td style="font-family:Arial,sans-serif;font-size:11pt;font-weight:bold;color:#1e293b;">
    //                 PDL Helpdesk
    //                 <br><span style="font-size:8pt;font-weight:normal;color:#64748b;">Pantex Dress Ltd.</span>
    //             </td>
    //             <td align="right" style="font-family:Arial,sans-serif;font-size:8pt;color:#64748b;vertical-align:bottom;">
    //                 Exported: ' . $exportDate . '<br>
    //                 Total tickets: ' . $totalCount . ' &nbsp;|&nbsp; ' . $modeLabel . '
    //             </td>
    //         </tr>
    //     </table>';

    //     $mpdf->SetHTMLHeader($headerHtml);

    //     // ── Footer (Page X of Y) ───────────────────────────────
    //     $footerHtml = '
    //     <table width="100%" style="border-top:1px solid #e2e8f0;padding-top:3px;">
    //         <tr>
    //             <td style="font-family:Arial,sans-serif;font-size:7pt;color:#94a3b8;">
    //                 PDL Helpdesk &mdash; Confidential &mdash; Internal Use Only
    //             </td>
    //             <td align="right" style="font-family:Arial,sans-serif;font-size:7pt;color:#94a3b8;">
    //                 Page {PAGENO} of {nbpg}
    //             </td>
    //         </tr>
    //     </table>';

    //     $mpdf->SetHTMLFooter($footerHtml);

    //     // ── Styles ────────────────────────────────────────────
    //     $css = '
    //     * { font-family: Arial, sans-serif; box-sizing: border-box; }
    //     body { font-size: 10pt; color: #1e293b; margin: 0; padding: 0; }

    //     /* ── Ticket wrapper (one per page) ── */
    //     .ticket-page {
    //         page-break-after: always;
    //     }
    //     .ticket-page:last-child {
    //         page-break-after: auto;
    //     }

    //     /* ── Section 1: Ticket Info ── */
    //     .ticket-header {
    //         background: #f8fafc;
    //         border: 1px solid #e2e8f0;
    //         border-radius: 6px;
    //         padding: 10px 14px;
    //         margin-bottom: 12px;
    //     }
    //     .ticket-id {
    //         font-size: 8pt;
    //         font-weight: bold;
    //         color: #0d9488;
    //         text-transform: uppercase;
    //         letter-spacing: 0.05em;
    //         margin-bottom: 3px;
    //     }
    //     .ticket-title {
    //         font-size: 14pt;
    //         font-weight: bold;
    //         color: #0f172a;
    //         line-height: 1.3;
    //     }

    //     /* Fields grid — commented-out fields are defined but hidden */
    //     .fields-table {
    //         width: 100%;
    //         border-collapse: collapse;
    //         margin-top: 8px;
    //     }
    //     .fields-table td {
    //         padding: 3px 8px 3px 0;
    //         font-size: 8.5pt;
    //         vertical-align: top;
    //     }
    //     .field-label {
    //         color: #64748b;
    //         font-weight: bold;
    //         width: 110px;
    //         white-space: nowrap;
    //     }
    //     .field-value {
    //         color: #1e293b;
    //     }

    //     /* ── Section 2: Description ── */
    //     .section-label {
    //         font-size: 7.5pt;
    //         font-weight: bold;
    //         color: #64748b;
    //         text-transform: uppercase;
    //         letter-spacing: 0.08em;
    //         border-bottom: 1px solid #e2e8f0;
    //         padding-bottom: 3px;
    //         margin-bottom: 8px;
    //         margin-top: 14px;
    //     }
    //     .description-box {
    //         font-size: 9.5pt;
    //         line-height: 1.6;
    //         color: #334155;
    //         white-space: pre-wrap;
    //         word-wrap: break-word;
    //     }

    //     /* ── Section 3: Attachments ── */
    //     .attachments-section {
    //         margin-top: 14px;
    //     }
    //     .img-grid-1 { width: 100%; }
    //     .img-grid-2 { width: 49%; display: inline-block; margin-right: 1%; vertical-align: top; }
    //     .img-grid-3, .img-grid-4 { width: 32%; display: inline-block; margin-right: 1%; vertical-align: top; }
    //     .img-grid-n { width: 24%; display: inline-block; margin-right: 0.5%; vertical-align: top; }

    //     .img-wrapper {
    //         border: 1px solid #e2e8f0;
    //         border-radius: 4px;
    //         overflow: hidden;
    //         margin-bottom: 6px;
    //         text-align: center;
    //     }
    //     .img-wrapper img {
    //         max-width: 100%;
    //         height: auto;
    //         display: block;
    //     }
    //     .img-caption {
    //         font-size: 7pt;
    //         color: #94a3b8;
    //         padding: 3px 4px;
    //         text-align: center;
    //         background: #f8fafc;
    //         border-top: 1px solid #f1f5f9;
    //     }

    //     /* Continuation indicator */
    //     .continued-label {
    //         font-size: 8pt;
    //         color: #94a3b8;
    //         font-style: italic;
    //         text-align: right;
    //         margin-top: 4px;
    //     }
    //     ';

    //     // ── Build HTML for each ticket (1 ticket = 1 page) ────
    //     $html = '';

    //     foreach ($tickets as $idx => $ticket) {
    //         $isLast      = ($idx === count($tickets) - 1);
    //         $attachments = $ticket['attachments'] ?? [];
    //         $imgCount    = count($attachments);

    //         // Determine image grid class
    //         $imgClass = match(true) {
    //             $imgCount === 1 => 'img-grid-1',
    //             $imgCount === 2 => 'img-grid-2',
    //             $imgCount <= 4  => 'img-grid-3',
    //             default         => 'img-grid-n',
    //         };

    //         // ── Section 1: Ticket Info ─────────────────────────
    //         $ticketInfoHtml = '
    //         <div class="ticket-header">
    //             <div class="ticket-id">' . htmlspecialchars($ticket['ticket_code']) . '</div>
    //             <div class="ticket-title">' . htmlspecialchars($ticket['title']) . '</div>

    //             <!-- FIELDS TABLE
    //                  The following fields are present in code but commented out.
    //                  Uncomment individually when needed.
    //             -->
    //             <!--
    //             <table class="fields-table">
    //                 <tr>
    //                     <td class="field-label">Department:</td>
    //                     <td class="field-value">' . htmlspecialchars($ticket['assigned_department']) . '</td>
    //                     <td class="field-label">Priority:</td>
    //                     <td class="field-value">' . ucfirst($ticket['priority']) . '</td>
    //                 </tr>
    //                 <tr>
    //                     <td class="field-label">Created At:</td>
    //                     <td class="field-value">' . date('d M Y, H:i', strtotime($ticket['created_at'])) . '</td>
    //                     <td class="field-label">Updated At:</td>
    //                     <td class="field-value">' . date('d M Y, H:i', strtotime($ticket['updated_at'])) . '</td>
    //                 </tr>
    //                 <tr>
    //                     <td class="field-label">Assigned Dept:</td>
    //                     <td class="field-value">' . htmlspecialchars($ticket['assigned_department']) . '</td>
    //                 </tr>
    //             </table>
    //             -->
    //         </div>';

    //         // ── Section 2: Description ─────────────────────────
    //         $descHtml = '
    //         <div class="section-label">Description</div>
    //         <div class="description-box">'
    //             . nl2br(htmlspecialchars($ticket['description']))
    //         . '</div>';

    //         // ── Section 3: Attachments ─────────────────────────
    //         $attachHtml = '';
    //         if ($imgCount > 0) {
    //             $attachHtml .= '<div class="attachments-section">';
    //             $attachHtml .= '<div class="section-label">Attachments (' . $imgCount . ')</div>';

    //             foreach ($attachments as $att) {
    //                 // Build absolute filesystem path to image
    //                 $imagePath = ROOT_PATH . 'uploads/' . $att['file_path'];

    //                 if (!file_exists($imagePath)) {
    //                     continue; // Skip missing files silently
    //                 }

    //                 $mimeType   = $att['mime_type'] ?? 'image/jpeg';
    //                 $imageData  = base64_encode(file_get_contents($imagePath));
    //                 $imageSrc   = 'data:' . $mimeType . ';base64,' . $imageData;
    //                 $captionTxt = htmlspecialchars($att['file_name']);

    //                 $attachHtml .= '
    //                 <div class="img-wrapper ' . $imgClass . '">
    //                     <img src="' . $imageSrc . '" alt="' . $captionTxt . '">
    //                     <div class="img-caption">' . $captionTxt . '</div>
    //                 </div>';
    //             }

    //             $attachHtml .= '</div>'; // /attachments-section
    //         }

    //         // ── Assemble ticket page ───────────────────────────
    //         $pageBreak = $isLast ? '' : 'page-break-after:always;';
    //         $html .= '<div class="ticket-page" style="' . $pageBreak . '">'
    //               . $ticketInfoHtml
    //               . $descHtml
    //               . $attachHtml
    //               . '</div>';
    //     }

    //     // ── Write to mPDF ──────────────────────────────────────
    //     $mpdf->WriteHTML('<style>' . $css . '</style>' . $html);

    //     // ── Output as download ─────────────────────────────────
    //     $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    //     exit;
    // }

    private function generatePdf(array $tickets, string $mode): void
    {
        // Check if mPDF is installed
        $autoload = ROOT_PATH . 'vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('mPDF not installed. Run: composer require mpdf/mpdf');
        }
        require_once $autoload;

        // Increase PCRE limits BEFORE creating mPDF
        ini_set('pcre.backtrack_limit', 5000000);
        ini_set('pcre.recursion_limit', 5000000);

        $exportDate  = date('d M Y, H:i');
        $totalCount  = count($tickets);
        $modeLabel   = $mode === 'filtered' ? 'Filtered Export' : 'Full Export';
        $filename    = 'PDL_Tickets_' . strtoupper($mode) . '_' . date('Ymd_His') . '.pdf';

        // Create temp directory if it doesn't exist
        $tempDir = ROOT_PATH . 'logs/temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // mPDF Setup
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_top'    => 28,
            'margin_bottom' => 16,
            'margin_left'   => 14,
            'margin_right'  => 14,
            'tempDir'       => $tempDir,
            'autoScriptToLang' => true,    // Auto-detect scripts (Bengali, Arabic, etc.)
            'autoLangToFont'   => true,    // Auto-assign appropriate fonts
            'default_font'     => 'dejavusans', // Better Unicode support than default
        ]);

        $mpdf->SetTitle('PDL Helpdesk — Ticket Export');
        $mpdf->SetAuthor('PDL Helpdesk System');
        $mpdf->shrink_tables_to_fit = 1;

        // Simple header
        $headerHtml = '
        <table width="100%" style="border-bottom:1.5px solid #0d9488;padding-bottom:5px;">
            <tr>
                <td style="font-family:Arial,sans-serif;font-size:11pt;font-weight:bold;">
                    PDL Helpdesk
                    <br><span style="font-size:8pt;font-weight:normal;color:#64748b;">Pantex Dress Ltd.</span>
                </td>
                <td align="right" style="font-size:8pt;color:#64748b;">
                    Exported: ' . $exportDate . '<br>
                    Total: ' . $totalCount . ' | ' . $modeLabel . '
                </td>
            </tr>
        </table>';

        $mpdf->SetHTMLHeader($headerHtml);

        // Footer
        $footerHtml = '
        <table width="100%" style="border-top:1px solid #e2e8f0;padding-top:3px;">
            <tr>
                <td style="font-size:7pt;color:#94a3b8;">PDL Helpdesk — Confidential</td>
                <td align="right" style="font-size:7pt;color:#94a3b8;">Page {PAGENO} of {nbpg}</td>
            </tr>
        </table>';

        $mpdf->SetHTMLFooter($footerHtml);

        // CSS (minimized to reduce HTML size)
        $css = '
        * { font-family: "DejaVu Sans", "Segoe UI", "Noto Sans Bengali", "Nikosh", "Siyam Rupali", Arial, sans-serif;}
        body { font-size: 10pt; color: #1e293b; }
        .ticket-page { page-break-after: always; }
        .ticket-page:last-child { page-break-after: auto; }
        .ticket-header {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .ticket-id {
            font-size: 8pt;
            font-weight: bold;
            color: #0d9488;
            margin-bottom: 3px;
        }
        .ticket-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
        }
        .section-label {
            font-size: 7.5pt;
            font-weight: bold;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 8px;
            margin-top: 14px;
        }
        .description-box {
            font-size: 9.5pt;
            line-height: 1.6;
            color: #334155;
            white-space: pre-wrap;
        }
        .img-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 6px;
            text-align: center;
            page-break-inside: avoid;
            display: inline-block;
            width: 48%;
            margin-right: 2%;
            vertical-align: top;
        }
        .img-wrapper img {
            max-width: 100%;
            height: auto;
        }
        .img-caption {
            font-size: 7pt;
            color: #94a3b8;
            padding: 3px;
            text-align: center;
            background: #f8fafc;
        }
        ';

        // Build HTML in chunks to avoid PCRE limit
        $mpdf->WriteHTML('<style>' . $css . '</style>');
        
        $chunkSize = 5; // Process 5 tickets at a time
        $chunks = array_chunk($tickets, $chunkSize);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkHtml = '';
            foreach ($chunk as $idx => $ticket) {
                $isLastInChunk = ($idx === count($chunk) - 1);
                $isLastOverall = ($chunkIndex === count($chunks) - 1 && $isLastInChunk);
                
                $attachments = $ticket['attachments'] ?? [];
                
                $chunkHtml .= '<div class="ticket-page"' . ($isLastOverall ? '' : ' style="page-break-after:always;"') . '>';
                
                // Ticket header
                $chunkHtml .= '
                <div class="ticket-header">
                    <div class="ticket-id">' . htmlspecialchars($ticket['ticket_code']) . '</div>
                    <div class="ticket-title">' . htmlspecialchars($ticket['title']) . '</div>
                </div>';
                
                // Description
                $chunkHtml .= '
                <div class="section-label">Description</div>
                <div class="description-box">' . nl2br(htmlspecialchars(substr($ticket['description'], 0, 5000))) . '</div>';
                
                // Attachments (limit to first 5 images per ticket)
                if (!empty($attachments)) {
                    $chunkHtml .= '<div class="section-label">Attachments</div>';
                    $imgCount = 0;
                    foreach ($attachments as $att) {
                        if ($imgCount >= 5) break; // Limit to 5 images per ticket
                        $imagePath = ROOT_PATH . 'uploads/' . $att['file_path'];
                        
                        if (file_exists($imagePath) && filesize($imagePath) < 500000) { // Skip images larger than 500KB
                            $imageData = base64_encode(file_get_contents($imagePath));
                            $imageSrc = 'data:' . $att['mime_type'] . ';base64,' . $imageData;
                            
                            $chunkHtml .= '
                            <div class="img-wrapper">
                                <img src="' . $imageSrc . '" alt="' . htmlspecialchars($att['file_name']) . '">
                                <div class="img-caption">' . htmlspecialchars($att['file_name']) . '</div>
                            </div>';
                            $imgCount++;
                        }
                    }
                }
                
                $chunkHtml .= '</div>';
            }
            
            // Write each chunk separately
            $mpdf->WriteHTML($chunkHtml);
            
            // Free memory after each chunk
            unset($chunkHtml);
        }

        // $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    // ── Helper: Collect filters from GET params ───────────────

    private function collectFilters(): array
    {
        $filters = [];

        if ($s = $this->get('status'))   $filters['status']    = $s;
        if ($p = $this->get('priority')) $filters['priority']  = $p;
        if ($q = $this->get('q'))        $filters['search']    = $q;
        if ($s = $this->get('sort'))     $filters['sort']      = $s;

        // Department filter
        if ($d = $this->get('dept')) {
            $filters['department'] = strtoupper($d);
        }

        // Filter mode: mine / department / all
        $filterMode = $this->get('filter', 'all');
        if ($filterMode === 'mine') {
            $filters['created_by'] = Auth::id();
        }

        return $filters;
    }
}
