<?php
/**
 * PDL_Helpdesk — Ticket Controller
 *
 * FIXED: Department Queue now correctly filters by user's role-based department.
 *   - IT role  → shows only IT tickets
 *   - MIS role → shows only MIS tickets
 *   - Admin    → shows all tickets (or filtered by ?dept= param)
 */

require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'modules/tickets/models/TicketModel.php';

class TicketController extends Controller
{
    private TicketModel $model;

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    // ── List ─────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();

        $user   = Auth::user();
        $role   = $user['role'];

        // Determine which filter tab is active
        $filter = $this->get('filter', 'mine');

        // ── Build filters & page title based on role + filter ──
        $filters   = [];
        $viewTitle = 'My Tickets';

        if ($filter === 'department') {
            // ── DEPARTMENT QUEUE ─────────────────────────────
            // Only accessible to IT, MIS, Admin, Super Admin
            if (!RBAC::can('ticket.view_department')) {
                // Normal users who somehow hit this URL → show their own
                $filters['created_by'] = $user['user_id'];
                $viewTitle = 'My Tickets';
            } else {
                // For IT role: department = IT
                // For MIS role: department = MIS
                // For admin/super_admin: show all UNLESS a ?dept= param is given
                // Derive department from role — covers cases where user's
                // department field was left as GENERAL in the database.
                if ($role === 'it') {
                    $filters['department'] = 'IT';
                    $viewTitle = 'IT Department Queue';
                } elseif ($role === 'mis') {
                    $filters['department'] = 'MIS';
                    $viewTitle = 'MIS Department Queue';
                } else {
                    // Admin — optionally filter by ?dept= query param
                    if ($d = $this->get('dept')) {
                        $filters['department'] = strtoupper($d);
                        $viewTitle = strtoupper($d) . ' Department Queue';
                    } else {
                        // Admin with no dept filter: show all open/in-progress
                        $viewTitle = 'All Department Queues';
                        // No department filter — admin sees everything
                    }
                }
            }

        } elseif ($filter === 'all' && RBAC::can('ticket.view_all')) {
            // ── ALL TICKETS (Admin only) ──────────────────────
            $viewTitle = 'All Tickets';
            // No restrictions on filters — admin sees everything

        } else {
            // ── MY TICKETS (default, all roles) ──────────────
            $filters['created_by'] = $user['user_id'];
            $viewTitle = 'My Tickets';
            $filter    = 'mine'; // normalize
        }

        // ── Additional column filters from query string ────────
        if ($s = $this->get('status'))   $filters['status']   = $s;
        if ($p = $this->get('priority')) $filters['priority'] = $p;
        if ($q = $this->get('q'))        $filters['search']   = $q;

        // Admin can filter department queue by dept in URL
        if ($filter === 'all' && ($d = $this->get('dept'))) {
            $filters['department'] = strtoupper($d);
        }

        $page   = $this->currentPage();
        $result = $this->model->getPaginated($filters, $page, TICKETS_PER_PAGE);
        $total  = $result['total'];
        $pages  = (int) ceil($total / TICKETS_PER_PAGE);

        $this->render('tickets/views/index', [
            'pageTitle' => $viewTitle,
            'tickets'   => $result['rows'],
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'filter'    => $filter,
            'filters'   => $filters,
        ]);
    }

    // ── Create Form ───────────────────────────────────────────

    public function create(): void
    {
        Auth::requireLogin();
        RBAC::require('ticket.create');

        $this->render('tickets/views/create', [
            'pageTitle' => 'Create New Ticket',
            'error'     => Auth::getFlash('error'),
        ]);
    }

    // ── Store (POST) ──────────────────────────────────────────

    public function store(): void
    {
        Auth::requireLogin();
        RBAC::require('ticket.create');
        $this->validateCsrf();

        $title       = $this->post('title', '');
        $description = trim($_POST['description'] ?? '');
        $department  = strtoupper($this->post('department', 'IT'));
        $priority    = $this->post('priority', 'medium');

        $errors = [];
        if (strlen($title) < 5)        $errors[] = 'Title must be at least 5 characters.';
        if (strlen($description) < 10) $errors[] = 'Please provide a more detailed description.';
        if (!in_array($department, ['IT','MIS','CLICK'])) $errors[] = 'Invalid department selected.';
        if (!in_array($priority, ['low','medium','high','critical'])) $errors[] = 'Invalid priority.';
        if ($department === 'CLICK' && !RBAC::can('ticket.create_for_click')) {
            $errors[] = 'You do not have permission to create CLICK tickets.';
        }

        if (!empty($errors)) {
            Auth::setFlash('error', implode(' ', $errors));
            $this->redirect('tickets/create');
            return;
        }

        $ticketId = $this->model->create([
            'title'       => $title,
            'description' => $description,
            'department'  => $department,
            'priority'    => $priority,
            'created_by'  => Auth::id(),
        ]);

        $ticket = $this->model->getById($ticketId);
        AuditLog::record('ticket.created', "Ticket #{$ticketId} '{$title}' created.", $ticketId);
        Notification::onTicketCreated($ticketId, $department, $ticket['ticket_code'], $title);

        $this->processFileUploads($ticketId, null);

        $this->redirectWithFlash('tickets/view', 'success', 'Ticket created successfully.', ['id' => $ticketId]);
    }

    // ── View ──────────────────────────────────────────────────

    public function view(): void
    {
        Auth::requireLogin();

        $ticketId = (int) $this->get('id', 0);
        $ticket   = $this->model->getById($ticketId);

        if (!$ticket) {
            Auth::setFlash('error', 'Ticket not found.');
            $this->redirect('tickets');
            return;
        }

        // Access check: normal users can only see own tickets
        if (!RBAC::can('ticket.view_all') && !RBAC::canAccessDepartment($ticket['assigned_department'])) {
            if ((int)$ticket['created_by'] !== Auth::id()) {
                Auth::setFlash('error', 'You do not have permission to view this ticket.');
                $this->redirect('tickets');
                return;
            }
        }

        $comments    = $this->model->getComments($ticketId);
        $attachments = $this->model->getAttachments($ticketId);
        $transfers   = $this->model->getTransfers($ticketId);

        $this->render('tickets/views/view', [
            'pageTitle'   => $ticket['ticket_code'] . ' — ' . $ticket['title'],
            'ticket'      => $ticket,
            'comments'    => $comments,
            'attachments' => $attachments,
            'transfers'   => $transfers,
        ]);
    }

    // ── Edit Form ─────────────────────────────────────────────

    public function edit(): void
    {
        Auth::requireLogin();

        $ticketId = (int) $this->get('id', 0);
        $ticket   = $this->model->getById($ticketId);

        if (!$ticket) {
            $this->redirectWithFlash('tickets', 'error', 'Ticket not found.');
            return;
        }

        $canEdit = RBAC::can('ticket.edit_any') ||
                   ((int)$ticket['created_by'] === Auth::id() && $ticket['status'] === 'open');

        if (!$canEdit) {
            $this->redirectWithFlash('tickets/view', 'error', 'You cannot edit this ticket.', ['id' => $ticketId]);
            return;
        }

        $this->render('tickets/views/edit', [
            'pageTitle' => 'Edit — ' . $ticket['ticket_code'],
            'ticket'    => $ticket,
        ]);
    }

    // ── Update (POST) ─────────────────────────────────────────

    public function update(): void
    {
        Auth::requireLogin();
        $this->validateCsrf();

        $ticketId    = (int) $this->post('ticket_id', 0);
        $ticket      = $this->model->getById($ticketId);

        if (!$ticket) {
            $this->redirectWithFlash('tickets', 'error', 'Ticket not found.');
            return;
        }

        $title       = $this->post('title', '');
        $description = trim($_POST['description'] ?? '');
        $priority    = $this->post('priority', 'medium');

        if (strlen($title) < 5 || strlen($description) < 10) {
            $this->redirectWithFlash('tickets/edit', 'error', 'Please fill all fields correctly.', ['id' => $ticketId]);
            return;
        }

        $this->model->updateDetails($ticketId, $title, $description, $priority);
        AuditLog::record('ticket.updated', "Ticket #{$ticketId} updated.", $ticketId,
            ['title' => $ticket['title']], ['title' => $title]);

        $this->redirectWithFlash('tickets/view', 'success', 'Ticket updated.', ['id' => $ticketId]);
    }

    // ── Change Status (POST) ──────────────────────────────────

    public function changeStatus(): void
    {
        Auth::requireLogin();
        RBAC::require('ticket.change_status');
        $this->validateCsrf();

        $ticketId  = (int) $this->post('ticket_id', 0);
        $newStatus = $this->post('status', '');
        $ticket    = $this->model->getById($ticketId);

        if (!$ticket || !in_array($newStatus, ['open','in_progress','solved','closed'])) {
            $this->redirectWithFlash('tickets', 'error', 'Invalid request.');
            return;
        }

        $oldStatus  = $ticket['status'];
        $resolvedBy = in_array($newStatus, ['solved','closed']) ? Auth::id() : null;

        $this->model->updateStatus($ticketId, $newStatus, $resolvedBy);
        AuditLog::ticketStatusChanged($ticketId, $oldStatus, $newStatus);

        if ($newStatus === 'solved') {
            Notification::onTicketSolved($ticketId, (int)$ticket['created_by'], $ticket['ticket_code']);
        } elseif ($newStatus === 'closed') {
            Notification::onTicketClosed($ticketId, (int)$ticket['created_by'], $ticket['ticket_code']);
        }

        $this->redirectWithFlash('tickets/view', 'success', 'Status updated to ' . ucfirst(str_replace('_',' ',$newStatus)) . '.', ['id' => $ticketId]);
    }

    // ── Transfer (POST) ───────────────────────────────────────

    public function transfer(): void
    {
        Auth::requireLogin();
        RBAC::require('ticket.transfer');
        $this->validateCsrf();

        $ticketId = (int) $this->post('ticket_id', 0);
        $newDept  = strtoupper($this->post('department', ''));
        $reason   = $this->post('reason', '');
        $ticket   = $this->model->getById($ticketId);

        if (!$ticket || !in_array($newDept, ['IT','MIS','CLICK'])) {
            $this->redirectWithFlash('tickets', 'error', 'Invalid transfer request.');
            return;
        }

        $oldDept = $ticket['assigned_department'];
        $this->model->transferDepartment($ticketId, $newDept);
        $this->model->logTransfer($ticketId, Auth::id(), $oldDept, $newDept, $reason);

        AuditLog::ticketTransferred($ticketId, $oldDept, $newDept);
        Notification::onTicketTransferred($ticketId, $newDept, $ticket['ticket_code']);

        $this->redirectWithFlash('tickets/view', 'success', "Ticket transferred to {$newDept}.", ['id' => $ticketId]);
    }

    // ── Add Comment (POST) ────────────────────────────────────

    public function addComment(): void
    {
        Auth::requireLogin();
        RBAC::require('ticket.comment');
        $this->validateCsrf();

        $ticketId   = (int) $this->post('ticket_id', 0);
        $commentTxt = trim($_POST['comment'] ?? '');
        $isInternal = (bool) $this->post('is_internal', false);
        $ticket     = $this->model->getById($ticketId);

        if (!$ticket || strlen($commentTxt) < 2) {
            $this->redirectWithFlash('tickets/view', 'error', 'Comment is too short.', ['id' => $ticketId]);
            return;
        }

        $this->model->addComment($ticketId, Auth::id(), $commentTxt, $isInternal);
        $this->processFileUploads($ticketId, null);

        AuditLog::record('ticket.comment_added', "Comment on ticket #{$ticketId}.", $ticketId);
        Notification::onTicketCommented($ticketId, (int)$ticket['created_by'],
            $ticket['ticket_code'], Auth::user()['full_name']);

        $this->redirectWithFlash('tickets/view', 'success', 'Comment added.', ['id' => $ticketId]);
    }

    // ── File Upload (AJAX) ────────────────────────────────────

    public function uploadAttachment(): void
    {
        Auth::requireLogin();
        RBAC::require('ticket.upload_attachment');

        $ticketId = (int) $this->post('ticket_id', 0);
        $count    = $this->processFileUploads($ticketId, null);

        $this->json(['success' => true, 'uploaded' => $count]);
    }

    // ── Private: File Processing ──────────────────────────────

    private function processFileUploads(int $ticketId, ?int $commentId): int
    {
        if (empty($_FILES['attachments'])) {
            return 0;
        }

        $files = $_FILES['attachments'];
        $count = 0;

        // Normalize single vs multiple file input
        if (!is_array($files['name'])) {
            $files = array_map(fn($v) => [$v], $files);
        }

        $uploadDir = UPLOAD_PATH . $ticketId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files['name'] as $i => $originalName) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > MAX_FILE_SIZE) continue;

            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($files['tmp_name'][$i]);

            if (!in_array($mimeType, ALLOWED_MIME_TYPES)) continue;

            $ext        = pathinfo($originalName, PATHINFO_EXTENSION);
            $storedName = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
            $destPath   = $uploadDir . $storedName;

            if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
                $this->model->addAttachment([
                    'ticket_id'   => $ticketId,
                    'comment_id'  => $commentId,
                    'uploaded_by' => Auth::id(),
                    'file_name'   => basename($originalName),
                    'stored_name' => $storedName,
                    'file_size'   => $files['size'][$i],
                    'mime_type'   => $mimeType,
                    'file_path'   => 'tickets/' . $ticketId . '/' . $storedName,
                ]);
                $count++;
            }
        }

        return $count;
    }
}
