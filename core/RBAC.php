<?php
/**
 * PDL_Helpdesk — RBAC (Role-Based Access Control)
 *
 * Central permission registry.
 * Add new permissions here as the system grows.
 * Controllers call RBAC::can('permission') to gate actions.
 */

class RBAC
{
    /**
     * Permission map: permission_key => roles that are allowed.
     *
     * Roles: normal_user | it | mis | admin | super_admin
     */
    private static array $permissions = [

        // ── Tickets ──────────────────────────────────────────
        'ticket.create'            => ['normal_user', 'it', 'mis', 'admin', 'super_admin'],
        'ticket.create_for_click'  => ['normal_user', 'it', 'mis', 'admin', 'super_admin'],
        'ticket.view_own'          => ['normal_user', 'it', 'mis', 'admin', 'super_admin'],
        'ticket.view_department'   => ['it', 'mis', 'admin', 'super_admin'],
        'ticket.view_all'          => ['admin', 'super_admin'],
        'ticket.edit_own'          => ['normal_user'],
        'ticket.edit_any'          => ['admin', 'super_admin'],
        'ticket.change_status'     => ['it', 'mis', 'admin', 'super_admin'],
        'ticket.transfer'          => ['it', 'mis', 'admin', 'super_admin'],
        'ticket.close'             => ['admin', 'super_admin'],
        'ticket.comment'           => ['normal_user', 'it', 'mis', 'admin', 'super_admin'],
        'ticket.upload_attachment' => ['normal_user', 'it', 'mis', 'admin', 'super_admin'],

        // ── Users ────────────────────────────────────────────
        'user.view_list'           => ['admin', 'super_admin'],
        'user.create'              => ['admin', 'super_admin'],
        'user.edit'                => ['admin', 'super_admin'],
        'user.deactivate'          => ['admin', 'super_admin'],
        'user.reset_password'      => ['admin', 'super_admin'],
        'user.promote_super_admin' => ['super_admin'],

        // ── Dashboard ────────────────────────────────────────
        'dashboard.user'           => ['normal_user'],
        'dashboard.department'     => ['it', 'mis'],
        'dashboard.admin'          => ['admin', 'super_admin'],

        // ── Reports ──────────────────────────────────────────
        'report.view'              => ['admin', 'super_admin'],
        'report.export'            => ['admin', 'super_admin'],

        // ── Audit Logs ───────────────────────────────────────
        'audit.view'               => ['admin', 'super_admin'],

        // ── Notifications ────────────────────────────────────
        'notification.view_own'    => ['normal_user', 'it', 'mis', 'admin', 'super_admin'],
    ];

    /**
     * Check whether the currently logged-in user has a given permission.
     */
    public static function can(string $permission): bool
    {
        $role = Auth::role();

        if ($role === null) {
            return false;
        }

        // Super admin has all permissions by design
        if ($role === 'super_admin') {
            return true;
        }

        if (!isset(self::$permissions[$permission])) {
            return false;  // Unknown permission → deny
        }

        return in_array($role, self::$permissions[$permission], true);
    }

    /**
     * Abort with 403 if the current user lacks the given permission.
     */
    public static function require(string $permission): void
    {
        if (!self::can($permission)) {
            Auth::setFlash('error', 'Access denied: insufficient permissions.');
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }
    }

    /**
     * Check whether a role can view/work on tickets of a given department.
     * IT users → IT tickets, MIS users → MIS tickets.
     * Admins see all.
     */
    public static function canAccessDepartment(string $department): bool
    {
        $role       = Auth::role();
        $userDept   = Auth::department();

        if (in_array($role, ['admin', 'super_admin'], true)) {
            return true;
        }

        if ($role === 'it' && $department === 'IT') {
            return true;
        }

        if ($role === 'mis' && $department === 'MIS') {
            return true;
        }

        return false;
    }

    /**
     * Returns a list of all permissions granted to a given role.
     * Used for documentation / admin UI.
     */
    public static function getPermissionsForRole(string $role): array
    {
        $granted = [];
        foreach (self::$permissions as $permission => $roles) {
            if (in_array($role, $roles, true)) {
                $granted[] = $permission;
            }
        }
        return $granted;
    }
}
