<?php
/**
 * PDL_Helpdesk — Audit Log View
 * Filterable, paginated audit event log.
 */

// Map action keys to badge colors
if (!function_exists('auditActionBadge')) {
    function auditActionBadge(string $action): string {
        if (str_starts_with($action, 'auth.'))      $cls = 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300';
        elseif (str_starts_with($action, 'ticket.')) $cls = 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
        elseif (str_starts_with($action, 'user.'))   $cls = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
        else $cls = 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
        return "<span class='inline-flex px-2 py-0.5 text-xs font-mono font-medium rounded-md {$cls}'>{$action}</span>";
    }
}
?>

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="font-display text-lg font-semibold text-slate-800 dark:text-slate-100">Audit Logs</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= number_format($total) ?> event<?= $total !== 1 ? 's' : '' ?> recorded</p>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>" class="flex flex-wrap gap-2 mb-5">
    <input type="hidden" name="page" value="audit">

    <select name="action"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">All Actions</option>
        <?php foreach ($actions as $a): ?>
        <option value="<?= htmlspecialchars($a) ?>" <?= ($_GET['action'] ?? '') === $a ? 'selected' : '' ?>>
            <?= htmlspecialchars($a) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <input type="number" name="ticket_id" value="<?= htmlspecialchars($_GET['ticket_id'] ?? '') ?>"
           placeholder="Ticket ID"
           class="w-28 px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">

    <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>"
           class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">

    <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>"
           class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">

    <button type="submit"
            class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700 transition-colors">
        Filter
    </button>
    <a href="<?= BASE_URL ?>?page=audit"
       class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
        Clear
    </a>
</form>

<!-- Log Table -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <?php if (empty($logs)): ?>
    <div class="flex flex-col items-center justify-center py-14 text-slate-400">
        <svg class="w-10 h-10 mb-3 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">No log entries match your filters.</p>
    </div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Timestamp</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">User</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">Ticket</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">IP Address</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                <td class="px-5 py-3 text-xs text-slate-400 whitespace-nowrap font-mono">
                    <?= date('M d H:i:s', strtotime($log['created_at'])) ?>
                </td>
                <td class="px-4 py-3">
                    <?= auditActionBadge($log['action']) ?>
                </td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <?php if ($log['full_name']): ?>
                    <div>
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($log['full_name']) ?></p>
                        <p class="text-xs text-slate-400">@<?= htmlspecialchars($log['username'] ?? '') ?></p>
                    </div>
                    <?php else: ?>
                    <span class="text-xs text-slate-400">System</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 max-w-xs">
                    <p class="text-xs text-slate-600 dark:text-slate-300 truncate" title="<?= htmlspecialchars($log['description']) ?>">
                        <?= htmlspecialchars($log['description']) ?>
                    </p>
                    <?php if ($log['old_value'] || $log['new_value']): ?>
                    <p class="text-xs text-slate-400 mt-0.5 font-mono">
                        <?php if ($log['old_value']): ?>
                        <span class="text-red-400">- <?= htmlspecialchars(substr($log['old_value'], 0, 40)) ?></span>
                        <?php endif; ?>
                        <?php if ($log['new_value']): ?>
                        <span class="text-emerald-500 ml-1">+ <?= htmlspecialchars(substr($log['new_value'], 0, 40)) ?></span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 hidden lg:table-cell">
                    <?php if ($log['ticket_id']): ?>
                    <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $log['ticket_id'] ?>"
                       class="text-xs text-teal-600 dark:text-teal-400 hover:underline">
                        #<?= $log['ticket_id'] ?>
                    </a>
                    <?php else: ?>
                    <span class="text-xs text-slate-400">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 hidden lg:table-cell text-xs text-slate-400 font-mono">
                    <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-xs text-slate-400">
        Page <?= $page ?> of <?= $pages ?> · <?= number_format($total) ?> total events
    </p>
    <div class="flex items-center gap-1">
        <?php
        // Show smart pagination: first, prev, current±2, next, last
        $showPages = array_unique(array_filter([
            1, 2,
            $page - 2, $page - 1, $page, $page + 1, $page + 2,
            $pages - 1, $pages,
        ], fn($p) => $p >= 1 && $p <= $pages));
        sort($showPages);
        $prev = null;
        foreach ($showPages as $i):
            if ($prev !== null && $i - $prev > 1): ?>
            <span class="px-1 text-slate-400 text-sm">…</span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>?page=audit&p=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-colors
                   <?= $i === $page ? 'bg-teal-600 text-white font-semibold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
                <?= $i ?>
            </a>
        <?php $prev = $i; endforeach; ?>
    </div>
</div>
<?php endif; ?>
