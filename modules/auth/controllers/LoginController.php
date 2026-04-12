<?php
/**
 * PDL_Helpdesk — Login Controller
 * Handles authentication: show form, process login, logout.
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'modules/auth/models/AuthUserModel.php';

class LoginController extends Controller
{
    private AuthUserModel $userModel;

    public function __construct()
    {
        $this->userModel = new AuthUserModel();
    }

    /**
     * GET: Display the login form.
     */
    public function showLogin(): void
    {
        $this->render('auth/views/login', [
            'pageTitle' => 'Sign In — PDL Helpdesk',
            'error'     => Auth::getFlash('error'),
        ], 'auth');
    }

    /**
     * POST: Process login credentials.
     */
    public function handleLogin(): void
    {
        $this->validateCsrf();

        $identifier = $this->post('identifier', '');
        $password   = $_POST['password'] ?? '';   // raw — not strip_tags

        // Basic presence validation
        if (empty($identifier) || empty($password)) {
            Auth::setFlash('error', 'Please enter your username and password.');
            $this->redirect('auth/login');
            return;
        }

        $user = $this->userModel->findByUsernameOrEmail($identifier);

        if ($user === null || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
            // Generic message prevents username enumeration
            Auth::setFlash('error', 'Invalid credentials. Please try again.');

            AuditLog::record(
                'auth.login_failed',
                "Failed login attempt for identifier: '{$identifier}'."
            );

            $this->redirect('auth/login');
            return;
        }

        // Successful login
        Auth::login($user);
        $this->userModel->updateLastLogin($user['user_id']);

        AuditLog::record(
            'auth.login',
            "User '{$user['username']}' logged in successfully."
        );

        $this->redirect('dashboard');
    }

    /**
     * GET: Log the user out and redirect to login.
     */
    public function logout(): void
    {
        if (Auth::isLoggedIn()) {
            $username = Auth::user()['username'] ?? 'unknown';
            AuditLog::record('auth.logout', "User '{$username}' logged out.");
            Auth::logout();
        }

        $this->redirect('auth/login');
    }
}
