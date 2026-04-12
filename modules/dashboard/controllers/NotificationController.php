<?php
/**
 * PDL_Helpdesk — Notification Controller
 * AJAX-only endpoints for the notification dropdown.
 */

require_once ROOT_PATH . 'core/Controller.php';

class NotificationController extends Controller
{
    public function fetch(): void
    {
        Auth::requireLogin();

        $notifications = Notification::getUnread(Auth::id(), 20);
        $this->json(['notifications' => $notifications]);
    }

    public function markRead(): void
    {
        Auth::requireLogin();

        $id = (int)$this->get('id', 0);
        if ($id > 0) {
            Notification::markRead($id, Auth::id());
        }
        $this->json(['success' => true]);
    }

    public function markAllRead(): void
    {
        Auth::requireLogin();

        Notification::markAllRead(Auth::id());
        $this->json(['success' => true]);
    }
}
