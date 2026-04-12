<?php
/**
 * PDL_Helpdesk — Dashboard Controller
 * Routes to the correct dashboard view based on the user's role.
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'modules/dashboard/models/DashboardModel.php';

class DashboardController extends Controller
{
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    public function index(): void
    {
        Auth::requireLogin();

        $role = Auth::role();
        $user = Auth::user();

        // Route to the correct dashboard variant
        match (true) {
            in_array($role, ['admin', 'super_admin']) => $this->adminDashboard($user),
            in_array($role, ['it', 'mis'])            => $this->departmentDashboard($user),
            default                                   => $this->userDashboard($user),
        };
    }

    // ── Admin Dashboard ───────────────────────────────────────

    private function adminDashboard(array $user): void
    {
        $stats            = $this->model->getAdminStats();
        $byDepartment     = $this->model->getTicketsByDepartment();
        $statusBreakdown  = $this->model->getStatusBreakdown();
        $dailyTrend       = $this->model->getDailyTicketTrend(14);
        $topPerformers    = $this->model->getTopPerformers(5);
        $recentTickets    = $this->model->getRecentAllTickets(8);

        $this->render('dashboard/views/admin', [
            'pageTitle'       => 'Admin Dashboard',
            'stats'           => $stats,
            'byDepartment'    => $byDepartment,
            'statusBreakdown' => $statusBreakdown,
            'dailyTrend'      => $dailyTrend,
            'topPerformers'   => $topPerformers,
            'recentTickets'   => $recentTickets,
            'user'            => $user,
        ]);
    }

    // ── IT / MIS Department Dashboard ────────────────────────

    private function departmentDashboard(array $user): void
    {
        // Derive department from role if not explicitly set or set to GENERAL.
        // IT role → IT department, MIS role → MIS department.
        $department = strtoupper($user['department']);
        if (empty($department) || $department === 'GENERAL') {
            $department = strtoupper($user['role']); // 'it' → 'IT', 'mis' → 'MIS'
        }

        $stats        = $this->model->getDepartmentStats($department);
        $queue        = $this->model->getDepartmentQueue($department, 8);
        $weeklyTrend  = $this->model->getDepartmentWeeklyTrend($department);

        $this->render('dashboard/views/department', [
            'pageTitle'   => $department . ' Department Dashboard',
            'department'  => $department,
            'stats'       => $stats,
            'queue'       => $queue,
            'weeklyTrend' => $weeklyTrend,
            'user'        => $user,
        ]);
    }

    // ── Normal User Dashboard ─────────────────────────────────

    private function userDashboard(array $user): void
    {
        $userId        = (int) $user['user_id'];
        $stats         = $this->model->getUserStats($userId);
        $recentTickets = $this->model->getUserRecentTickets($userId, 5);

        $this->render('dashboard/views/user', [
            'pageTitle'     => 'My Dashboard',
            'stats'         => $stats,
            'recentTickets' => $recentTickets,
            'user'          => $user,
        ]);
    }
}
