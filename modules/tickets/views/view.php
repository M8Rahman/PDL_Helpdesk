<?php
/**
 * PDL_Helpdesk — Ticket Detail View
 * Timeline, comments thread, attachments, status/transfer controls.
 */

$role      = Auth::role();
$userId    = Auth::id();
$canAction = RBAC::can('ticket.change_status') && RBAC::canAccessDepartment($ticket['assigned_department']);
$canEdit   = RBAC::can('ticket.edit_any') ||
             ($ticket['created_by'] == $userId && $ticket['status'] === 'open');
$canTransfer = RBAC::can('ticket.transfer') && RBAC::canAccessDepartment($ticket['assigned_department']);

// Status classes
$statusClass = [
    'open'        => 'badge-open',
    'in_progress' => 'badge-in_progress',
    'solved'      => 'badge-solved',
    'closed'      => 'badge-closed',
][$ticket['status']] ?? 'badge-closed';

$priorityClass = 'badge-' . $ticket['priority'];

// Role labels for comments
$roleLabel = fn($r) => match($r) {
    'it'          => 'IT',
    'mis'         => 'MIS',
    'admin'       => 'Admin',
    'super_admin' => 'Super Admin',
    default       => 'User',
};
?>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-xs text-slate-400 mb-4">
    <a href="<?= BASE_URL ?>?page=tickets" class="hover:text-teal-600 transition-colors">Tickets</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($ticket['ticket_code']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- ── Left: Ticket Content ─────────────────────────────── -->
    <div class="lg:col-span-2 space-y-4">

        <!-- Ticket Header Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex-1 min-w-0">
                    <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100 leading-snug">
                        <?= htmlspecialchars($ticket['title']) ?>
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">
                        Opened by <strong class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($ticket['creator_name']) ?></strong>
                        on <?= date('M d, Y \a\t H:i', strtotime($ticket['created_at'])) ?>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">
                        <?= ucfirst(str_replace('_',' ',$ticket['status'])) ?>
                    </span>
                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?= $priorityClass ?>">
                        <?= ucfirst($ticket['priority']) ?>
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div class="prose prose-sm dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4">
                <?= nl2br(htmlspecialchars($ticket['description'])) ?>
            </div>

            <!-- Edit Button -->
            <?php if ($canEdit): ?>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="<?= BASE_URL ?>?page=tickets/edit&id=<?= $ticket['ticket_id'] ?>"
                   class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit ticket
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Attachments -->
        <?php if (!empty($attachments)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">
                Attachments (<?= count($attachments) ?>)
            </h3>
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                <?php foreach ($attachments as $att): ?>
                <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($att['file_path']) ?>"
                   target="_blank"
                   class="group relative rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 aspect-square block">
                    <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($att['file_path']) ?>"
                         alt="<?= htmlspecialchars($att['file_name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                         loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Comments Thread -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Comments (<?= count($comments) ?>)
                </h3>
            </div>

            <?php if (!empty($comments)): ?>
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <?php foreach ($comments as $c):
                    $isOwn = $c['user_id'] == $userId;
                    $initials = '';
                    foreach (explode(' ', $c['full_name']) as $part) {
                        $initials .= strtoupper(mb_substr($part,0,1));
                        if (strlen($initials) >= 2) break;
                    }
                    // Only show internal notes to staff
                    if ($c['is_internal'] && !in_array($role, ['it','mis','admin','super_admin'])) continue;
                ?>
                <div class="flex gap-3 px-5 py-4 <?= $c['is_internal'] ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' ?>">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-semibold shrink-0"
                         style="background:linear-gradient(135deg,<?= $isOwn ? '#0d9488,#0f766e' : '#475569,#334155' ?>)">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <?= htmlspecialchars($c['full_name']) ?>
                            </span>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                                <?= $roleLabel($c['role']) ?>
                            </span>
                            <?php if ($c['is_internal']): ?>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                Internal Note
                            </span>
                            <?php endif; ?>
                            <span class="text-xs text-slate-400 ml-auto">
                                <?= date('M d, Y H:i', strtotime($c['created_at'])) ?>
                            </span>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
                            <?= nl2br(htmlspecialchars($c['comment'])) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center py-8 text-slate-400">
                <p class="text-sm">No comments yet. Be the first to comment.</p>
            </div>
            <?php endif; ?>

            <!-- Add Comment Form -->
            <?php if (RBAC::can('ticket.comment')): ?>
            <div class="px-5 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700">
                <form method="POST" action="<?= BASE_URL ?>?page=tickets/comment" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                    <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">

                    <textarea name="comment" rows="3" required
                              id="commentBox"
                              placeholder="Add a comment… (Ctrl+V to paste screenshots)"
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none placeholder-slate-400 transition"></textarea>

                    <div class="flex items-center justify-between mt-2.5">
                        <div class="flex items-center gap-3">
                            <?php if (in_array($role, ['it','mis','admin','super_admin'])): ?>
                            <label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer select-none">
                                <input type="checkbox" name="is_internal" value="1"
                                       class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                Internal note
                            </label>
                            <?php endif; ?>
                        </div>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-sm font-medium text-white transition-all hover:-translate-y-px"
                                style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                            Post Comment
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- end left -->

    <!-- ── Right: Sidebar Info ──────────────────────────────── -->
    <div class="space-y-4">

        <!-- Details Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4">Details</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-400">Department</dt>
                    <dd class="font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($ticket['assigned_department']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Status</dt>
                    <dd><span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md <?= $statusClass ?>"><?= ucfirst(str_replace('_',' ',$ticket['status'])) ?></span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Priority</dt>
                    <dd><span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md <?= $priorityClass ?>"><?= ucfirst($ticket['priority']) ?></span></dd>
                </div>
                <?php if ($ticket['resolver_name']): ?>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Resolved by</dt>
                    <dd class="font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($ticket['resolver_name']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($ticket['resolved_at']): ?>
                <div class="flex justify-between">
                    <dt class="text-slate-400">Resolved at</dt>
                    <dd class="text-slate-600 dark:text-slate-300"><?= date('M d, Y H:i', strtotime($ticket['resolved_at'])) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Status Change (IT/MIS/Admin) -->
        <?php if ($canAction && !in_array($ticket['status'], ['closed'])): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Update Status</h3>
            <form method="POST" action="<?= BASE_URL ?>?page=tickets/status">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                <select name="status"
                        class="w-full px-3 py-2 mb-3 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <option value="open"        <?= $ticket['status']==='open'        ? 'selected':'' ?>>Open</option>
                    <option value="in_progress" <?= $ticket['status']==='in_progress' ? 'selected':'' ?>>In Progress</option>
                    <option value="solved"      <?= $ticket['status']==='solved'      ? 'selected':'' ?>>Solved</option>
                    <?php if (RBAC::can('ticket.close')): ?>
                    <option value="closed"      <?= $ticket['status']==='closed'      ? 'selected':'' ?>>Closed</option>
                    <?php endif; ?>
                </select>
                <button type="submit"
                        class="w-full py-2.5 rounded-xl text-sm font-medium text-white transition-all hover:-translate-y-px"
                        style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                    Update Status
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Transfer Department (IT/MIS/Admin) -->
        <?php if ($canTransfer && !in_array($ticket['status'], ['solved','closed'])): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Transfer Department</h3>
            <form method="POST" action="<?= BASE_URL ?>?page=tickets/transfer">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                <select name="department"
                        class="w-full px-3 py-2 mb-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500">
                    <?php foreach (['IT','MIS','CLICK'] as $dept): ?>
                    <option value="<?= $dept ?>" <?= $ticket['assigned_department']===$dept ? 'selected':'' ?>>
                        <?= $dept ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <textarea name="reason" rows="2" placeholder="Reason for transfer (optional)…"
                          class="w-full px-3 py-2 mb-3 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none placeholder-slate-400"></textarea>
                <button type="submit"
                        class="w-full py-2.5 rounded-xl text-sm font-medium bg-slate-700 dark:bg-slate-600 text-white hover:bg-slate-600 dark:hover:bg-slate-500 transition-colors">
                    Transfer Ticket
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Transfer History -->
        <?php if (!empty($transfers)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Transfer History</h3>
            <div class="space-y-2">
                <?php foreach ($transfers as $tr): ?>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    <span class="font-medium text-slate-600 dark:text-slate-300"><?= htmlspecialchars($tr['from_department']) ?></span>
                    <span class="mx-1">→</span>
                    <span class="font-medium text-slate-600 dark:text-slate-300"><?= htmlspecialchars($tr['to_department']) ?></span>
                    <span class="mx-1">·</span>
                    <span><?= htmlspecialchars($tr['transferred_by_name']) ?></span>
                    <div class="text-[11px] text-slate-400"><?= date('M d, Y H:i', strtotime($tr['transferred_at'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- end right -->
</div>
