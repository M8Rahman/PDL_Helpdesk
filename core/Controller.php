<?php
/**
 * PDL_Helpdesk — Base Controller
 *
 * All module controllers extend this class.
 * Provides view rendering, JSON responses, and redirect helpers.
 */

abstract class Controller
{
    /**
     * Render a view file inside the main application layout.
     *
     * @param string $view   Path relative to modules/  e.g. 'tickets/views/index'
     * @param array  $data   Variables to extract into the view scope
     * @param string $layout Layout template: 'main' | 'auth'
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Make all data keys available as variables in the view
        extract($data, EXTR_SKIP);

        // Capture the inner view content
        $viewFile = ROOT_PATH . 'modules/' . $view . '.php';

        if (!file_exists($viewFile)) {
            error_log("[PDL_Helpdesk] View not found: $viewFile");
            echo "<p style='color:red;padding:20px;'>View not found: <code>{$view}</code></p>";
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Pass content into the layout
        $layoutFile = ROOT_PATH . 'shared/layouts/' . $layout . '_layout.php';

        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            // Fallback: render content without layout
            echo $content;
        }
    }

    /**
     * Send a JSON response (for AJAX endpoints).
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to a page route.
     *
     * @param string $page  e.g. 'dashboard' or 'tickets/view&id=5'
     */
    protected function redirect(string $page, array $queryParams = []): void
    {
        $url = BASE_URL . '?page=' . $page;
        foreach ($queryParams as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode((string) $value);
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * Set a flash message and redirect.
     */
    protected function redirectWithFlash(string $page, string $type, string $message, array $queryParams = []): void
    {
        Auth::setFlash($type, $message);
        $this->redirect($page, $queryParams);
    }

    /**
     * Validate a CSRF token from POST data.
     * Kills the request if invalid.
     */
    protected function validateCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Auth::validateCsrfToken($token)) {
            $this->redirectWithFlash('dashboard', 'error', 'Invalid request. Please try again.');
        }
    }

    /**
     * Get a sanitized POST value.
     */
    protected function post(string $key, mixed $default = null): mixed
    {
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }

    /**
     * Get a sanitized GET value.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }

    /**
     * Basic input sanitization. Views should use htmlspecialchars() for output.
     */
    protected function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim(strip_tags($value));
        }
        return $value;
    }

    /**
     * Get the current page number from GET params (for pagination).
     */
    protected function currentPage(): int
    {
        return max(1, (int) ($_GET['p'] ?? 1));
    }
}
