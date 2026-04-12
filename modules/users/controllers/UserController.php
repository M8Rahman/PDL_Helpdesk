<?php
/**
 * PDL_Helpdesk — User Controller
 * Admin-only: list, create, edit, toggle active, reset password.
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'modules/users/models/UserModel.php';

class UserController extends Controller
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    // ── List ─────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();
        RBAC::require('user.view_list');

        $filters = [];
        if ($s = $this->get('q'))           $filters['search']    = $s;
        if ($r = $this->get('role'))        $filters['role']      = $r;
        if (($a = $this->get('active', '')) !== '') $filters['is_active'] = $a;

        $page   = $this->currentPage();
        $result = $this->model->getPaginated($filters, $page, USERS_PER_PAGE);

        $this->render('users/views/index', [
            'pageTitle' => 'User Management',
            'users'     => $result['rows'],
            'total'     => $result['total'],
            'page'      => $page,
            'pages'     => (int) ceil($result['total'] / USERS_PER_PAGE),
            'filters'   => $filters,
        ]);
    }

    // ── Create Form ───────────────────────────────────────────

    public function create(): void
    {
        Auth::requireLogin();
        RBAC::require('user.create');

        $this->render('users/views/create', [
            'pageTitle' => 'Create User',
        ]);
    }

    // ── Store (POST) ──────────────────────────────────────────

    public function store(): void
    {
        Auth::requireLogin();
        RBAC::require('user.create');
        $this->validateCsrf();

        $data = [
            'full_name'  => $this->post('full_name', ''),
            'username'   => $this->post('username', ''),
            'email'      => $this->post('email', ''),
            'password'   => $_POST['password'] ?? '',
            'role'       => $this->post('role', 'normal_user'),
            'department' => $this->post('department', 'GENERAL'),
        ];

        $errors = $this->validateUserData($data);

        if ($this->model->usernameExists($data['username'])) {
            $errors[] = 'Username already taken.';
        }
        if ($this->model->emailExists($data['email'])) {
            $errors[] = 'Email address already registered.';
        }

        if (!empty($errors)) {
            Auth::setFlash('error', implode(' ', $errors));
            $this->redirect('users/create');
            return;
        }

        $newUserId = $this->model->create($data);

        AuditLog::record(
            'user.created',
            "User '{$data['username']}' (ID:{$newUserId}) created by admin.",
        );

        $this->redirectWithFlash('users', 'success', "User '{$data['username']}' created successfully.");
    }

    // ── Edit Form ─────────────────────────────────────────────

    public function edit(): void
    {
        Auth::requireLogin();
        RBAC::require('user.edit');

        $userId = (int) $this->get('id', 0);
        $user   = $this->model->getById($userId);

        if (!$user) {
            $this->redirectWithFlash('users', 'error', 'User not found.');
            return;
        }

        // Super admin cannot be edited
        if ($user['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
            $this->redirectWithFlash('users', 'error', 'Super Admin accounts cannot be edited.');
            return;
        }

        $ticketSummary = $this->model->getTicketSummary($userId);

        $this->render('users/views/edit', [
            'pageTitle'     => 'Edit User — ' . $user['full_name'],
            'user'          => $user,
            'ticketSummary' => $ticketSummary,
        ]);
    }

    // ── Update (POST) ─────────────────────────────────────────

    public function update(): void
    {
        Auth::requireLogin();
        RBAC::require('user.edit');
        $this->validateCsrf();

        $userId  = (int) $this->post('user_id', 0);
        $target  = $this->model->getById($userId);

        if (!$target) {
            $this->redirectWithFlash('users', 'error', 'User not found.');
            return;
        }

        // Protect super admin from being edited by non-super-admins
        if ($target['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
            $this->redirectWithFlash('users', 'error', 'Super Admin accounts cannot be edited.');
            return;
        }

        $data = [
            'full_name'  => $this->post('full_name', ''),
            'email'      => $this->post('email', ''),
            'role'       => $this->post('role', 'normal_user'),
            'department' => $this->post('department', 'GENERAL'),
        ];

        // Validate email uniqueness (excluding self)
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithFlash('users/edit', 'error', 'Invalid email address.', ['id' => $userId]);
            return;
        }
        if ($this->model->emailExists($data['email'], $userId)) {
            $this->redirectWithFlash('users/edit', 'error', 'Email already in use.', ['id' => $userId]);
            return;
        }

        $this->model->update($userId, $data);

        AuditLog::record('user.updated', "User ID:{$userId} profile updated.");

        $this->redirectWithFlash('users', 'success', "User '{$data['full_name']}' updated.");
    }

    // ── Toggle Active (POST) ──────────────────────────────────

    public function toggleActive(): void
    {
        Auth::requireLogin();
        RBAC::require('user.deactivate');
        $this->validateCsrf();

        $userId = (int) $this->post('user_id', 0);
        $target = $this->model->getById($userId);

        if (!$target) {
            $this->redirectWithFlash('users', 'error', 'User not found.');
            return;
        }

        // Super admin can never be deactivated
        if ($target['role'] === 'super_admin') {
            $this->redirectWithFlash('users', 'error', 'The Super Admin account cannot be deactivated.');
            return;
        }

        // Cannot deactivate yourself
        if ($target['user_id'] === Auth::id()) {
            $this->redirectWithFlash('users', 'error', 'You cannot deactivate your own account.');
            return;
        }

        $newState = !((bool) $target['is_active']);
        $this->model->toggleActive($userId, $newState);

        $stateLabel = $newState ? 'activated' : 'deactivated';
        AuditLog::record('user.toggle_active', "User ID:{$userId} {$stateLabel}.");

        $this->redirectWithFlash('users', 'success', "User '{$target['full_name']}' {$stateLabel}.");
    }

    // ── Reset Password (POST) ─────────────────────────────────

    public function resetPassword(): void
    {
        Auth::requireLogin();
        RBAC::require('user.reset_password');
        $this->validateCsrf();

        $userId      = (int) $this->post('user_id', 0);
        $newPassword = $_POST['new_password'] ?? '';
        $target      = $this->model->getById($userId);

        if (!$target) {
            $this->redirectWithFlash('users', 'error', 'User not found.');
            return;
        }
        if ($target['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
            $this->redirectWithFlash('users', 'error', 'Cannot reset Super Admin password.');
            return;
        }
        if (strlen($newPassword) < 8) {
            $this->redirectWithFlash('users/edit', 'error', 'Password must be at least 8 characters.', ['id' => $userId]);
            return;
        }

        $this->model->resetPassword($userId, $newPassword);

        AuditLog::record('user.password_reset', "Password reset for User ID:{$userId} by admin.");

        $this->redirectWithFlash('users', 'success', "Password reset for '{$target['full_name']}'.");
    }

    // ── Validation Helper ─────────────────────────────────────

    private function validateUserData(array $data, bool $requirePassword = true): array
    {
        $errors      = [];
        $validRoles  = ['normal_user', 'it', 'mis', 'admin', 'super_admin'];
        $validDepts  = ['IT', 'MIS', 'CLICK', 'GENERAL'];

        if (strlen($data['full_name']) < 2) {
            $errors[] = 'Full name is required.';
        }
        if (!preg_match('/^[a-zA-Z0-9_\.]{3,80}$/', $data['username'] ?? '')) {
            $errors[] = 'Username must be 3–80 alphanumeric characters, underscores, or dots.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }
        if ($requirePassword && strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!in_array($data['role'], $validRoles)) {
            $errors[] = 'Invalid role selected.';
        }
        if (!in_array(strtoupper($data['department'] ?? ''), $validDepts)) {
            $errors[] = 'Invalid department selected.';
        }

        // Only super_admin can create another super_admin
        if ($data['role'] === 'super_admin' && Auth::role() !== 'super_admin') {
            $errors[] = 'Only a Super Admin can create another Super Admin.';
        }

        return $errors;
    }
}
