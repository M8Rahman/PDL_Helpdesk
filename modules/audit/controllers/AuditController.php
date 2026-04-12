<?php
/**
 * PDL_Helpdesk — Audit Log Controller
 */

require_once ROOT_PATH . 'core/Controller.php';

class AuditController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        RBAC::require('audit.view');

        $filters = [];
        if ($u = $this->get('user_id'))    $filters['user_id']   = (int) $u;
        if ($t = $this->get('ticket_id'))  $filters['ticket_id'] = (int) $t;
        if ($a = $this->get('action'))     $filters['action']    = $a;
        if ($f = $this->get('date_from'))  $filters['date_from'] = $f;
        if ($d = $this->get('date_to'))    $filters['date_to']   = $d;

        $page   = $this->currentPage();
        $result = AuditLog::getPaginated($page, LOGS_PER_PAGE, $filters);
        $total  = $result['total'];
        $pages  = (int) ceil($total / LOGS_PER_PAGE);

        // Distinct action types for filter dropdown
        $db      = Database::getInstance();
        $actions = $db->query('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC')->fetchAll();

        $this->render('audit/views/index', [
            'pageTitle' => 'Audit Logs',
            'logs'      => $result['rows'],
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'filters'   => $filters,
            'actions'   => array_column($actions, 'action'),
        ]);
    }
}
