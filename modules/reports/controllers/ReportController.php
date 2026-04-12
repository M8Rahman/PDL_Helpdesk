<?php
/**
 * PDL_Helpdesk — Report Controller
 *
 * FIXED: Added try/catch, verified RBAC path, fixed date iterator issue.
 * Reports are accessible to admin and super_admin roles.
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'modules/reports/models/ReportModel.php';

class ReportController extends Controller
{
    private ReportModel $model;

    public function __construct()
    {
        $this->model = new ReportModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        RBAC::require('report.view');

        // Default: last 30 days
        $dateFrom = $this->get('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo   = $this->get('date_to',   date('Y-m-d'));

        // Validate date format
        if (!$this->isValidDate($dateFrom)) $dateFrom = date('Y-m-d', strtotime('-30 days'));
        if (!$this->isValidDate($dateTo))   $dateTo   = date('Y-m-d');

        // Ensure from <= to
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        try {
            $byDepartment = $this->model->getByDepartment($dateFrom, $dateTo);
            $byStatus     = $this->model->getByStatus($dateFrom, $dateTo);
            $byPriority   = $this->model->getByPriority($dateFrom, $dateTo);
            $performance  = $this->model->getEmployeePerformance($dateFrom, $dateTo);
            $dailyVolume  = $this->model->getDailyVolume($dateFrom, $dateTo);
        } catch (\Exception $e) {
            error_log('[PDL_Helpdesk] ReportController::index error: ' . $e->getMessage());
            $byDepartment = $byStatus = $byPriority = $performance = $dailyVolume = [];
        }

        // Build chart data: fill gaps day by day
        $volumeMap = [];
        foreach ($dailyVolume as $row) {
            $volumeMap[$row['day']] = (int) $row['created'];
        }

        $volumeLabels = [];
        $volumeValues = [];

        try {
            $d   = new DateTime($dateFrom);
            $end = new DateTime($dateTo);
            $end->modify('+1 day'); // make end inclusive

            while ($d < $end) {
                $key = $d->format('Y-m-d');
                $volumeLabels[] = $d->format('M d');
                $volumeValues[] = $volumeMap[$key] ?? 0;
                $d->modify('+1 day');

                // Safety: don't loop more than 366 days
                if (count($volumeLabels) > 366) break;
            }
        } catch (\Exception $e) {
            $volumeLabels = [];
            $volumeValues = [];
        }

        $this->render('reports/views/index', [
            'pageTitle'    => 'Reports & Analytics',
            'dateFrom'     => $dateFrom,
            'dateTo'       => $dateTo,
            'byDepartment' => $byDepartment,
            'byStatus'     => $byStatus,
            'byPriority'   => $byPriority,
            'performance'  => $performance,
            'volumeLabels' => $volumeLabels,
            'volumeValues' => $volumeValues,
        ]);
    }

    /**
     * Export tickets as CSV.
     */
    public function export(): void
    {
        Auth::requireLogin();
        RBAC::require('report.export');

        $format = $this->get('format', 'csv');

        $filters = [];
        if ($d = $this->get('dept'))      $filters['department'] = strtoupper($d);
        if ($s = $this->get('status'))    $filters['status']     = $s;
        if ($f = $this->get('date_from')) $filters['date_from']  = $f;
        if ($t = $this->get('date_to'))   $filters['date_to']    = $t;

        try {
            $rows = $this->model->getAllForExport($filters);
        } catch (\Exception $e) {
            error_log('[PDL_Helpdesk] ReportController::export error: ' . $e->getMessage());
            $rows = [];
        }

        AuditLog::record('report.exported', 'Report exported as ' . strtoupper($format) . '.');

        $this->exportCsv($rows);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function exportCsv(array $rows): void
    {
        $filename = 'PDL_Report_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        fputcsv($out, [
            'Ticket Code', 'Title', 'Department', 'Status', 'Priority',
            'Created By', 'Resolved By', 'Created At', 'Resolved At', 'Resolution (Hours)',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['ticket_code'],
                $row['title'],
                $row['assigned_department'],
                ucfirst(str_replace('_', ' ', $row['status'])),
                ucfirst($row['priority']),
                $row['created_by']   ?? '',
                $row['resolved_by']  ?? '',
                $row['created_at']   ?? '',
                $row['resolved_at']  ?? '',
                $row['resolution_hours'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}
