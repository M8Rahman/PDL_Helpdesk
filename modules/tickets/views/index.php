<?php
/**
 * PDL_Helpdesk — Ticket List View
 *
 * UPDATED:
 * - Added Department filter dropdown (admin/super_admin only)
 * - Added Export dropdown button (All Tickets / Filtered Tickets)
 */

if (!function_exists('listStatusBadge')) {
    function listStatusBadge(string $s): string {
        $map = ['open'=>'badge-open','in_progress'=>'badge-in_progress','solved'=>'badge-solved','closed'=>'badge-closed'];
        return "<span class='inline-flex px-2 py-0.5 text-xs font-medium rounded-md ".($map[$s]??'badge-closed')."'>".ucfirst(str_replace('_',' ',$s))."</span>";
    }
}

if (!function_exists('listPriorityBadge')) {
    function listPriorityBadge(string $p): string {
        return "<span class='inline-flex px-2 py-0.5 text-xs font-medium rounded-md badge-{$p}'>".ucfirst($p)."</span>";
    }
}

// Build export URL query string (carries current filters forward)
$exportBase    = BASE_URL . '?page=tickets/export';
$exportParams  = http_build_query(array_filter([
    'filter'   => $filter,
    'status'   => $_GET['status']   ?? '',
    'priority' => $_GET['priority'] ?? '',
    'dept'     => $_GET['dept']     ?? '',
    'sort'     => $_GET['sort']     ?? '',
    'q'        => $_GET['q']        ?? '',
]));
$exportFiltered = $exportBase . '&mode=filtered&' . $exportParams;
$exportAll      = $exportBase . '&mode=all';
?>

<!-- ═══════════════════════════════════════
     HEADER
════════════════════════════════════════ -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="font-display text-lg font-semibold text-slate-800 dark:text-slate-100">
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <p class="text-sm text-slate-400 mt-0.5">
            <?= number_format($total) ?> ticket<?= $total !== 1 ? 's' : '' ?> found
        </p>
    </div>

    <div class="flex items-center gap-2">

        <!-- ── Export dropdown (admin only) ── -->
        <?php if (RBAC::can('report.export')): ?>
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium
                           border border-slate-200 dark:border-slate-600
                           text-slate-600 dark:text-slate-300
                           hover:bg-slate-100 dark:hover:bg-slate-700
                           transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150"
                     :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown menu -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800
                        rounded-xl shadow-lg shadow-slate-200/60 dark:shadow-black/40
                        border border-slate-200 dark:border-slate-700 py-1 z-50"
                 style="display:none;">

                <!-- Option 1: Export ALL tickets -->
                <a href="<?= htmlspecialchars($exportAll) ?>"
                   target="_blank"
                   class="flex items-start gap-3 px-4 py-3
                          hover:bg-slate-50 dark:hover:bg-slate-700/50
                          transition-colors cursor-pointer">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400 mt-0.5 shrink-0"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Export All Tickets
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            All tickets, oldest → newest
                        </p>
                    </div>
                </a>

                <div class="mx-3 my-1 border-t border-slate-100 dark:border-slate-700"></div>

                <!-- Option 2: Export FILTERED tickets -->
                <a href="<?= htmlspecialchars($exportFiltered) ?>"
                   target="_blank"
                   class="flex items-start gap-3 px-4 py-3
                          hover:bg-slate-50 dark:hover:bg-slate-700/50
                          transition-colors cursor-pointer">
                    <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400 mt-0.5 shrink-0"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                            Export Filtered Tickets
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Matches current filter selection
                        </p>
                    </div>
                </a>

            </div>
        </div>
        <?php endif; ?>

        <!-- ── New Ticket button ── -->
        <?php if (RBAC::can('ticket.create')): ?>
        <a href="<?= BASE_URL ?>?page=tickets/create"
           class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-white
                  transition-all hover:-translate-y-px"
           style="background:linear-gradient(135deg,#0d9488,#0f766e)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Ticket
        </a>
        <?php endif; ?>

    </div>
</div>

<!-- ═══════════════════════════════════════
     FILTERS
════════════════════════════════════════ -->
<form method="GET" action="<?= BASE_URL ?>" class="flex flex-wrap gap-2 mb-5">
    <input type="hidden" name="page"   value="tickets">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">

    <!-- Search -->
    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
           placeholder="Search tickets…"
           class="flex-1 min-w-48 px-3 py-2 text-sm rounded-xl
                  border border-slate-200 dark:border-slate-600
                  bg-white dark:bg-slate-800
                  text-slate-700 dark:text-slate-200
                  focus:outline-none focus:ring-2 focus:ring-teal-500">

    <!-- Status -->
    <select name="status"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600
                   bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200
                   focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">All Statuses</option>
        <option value="open"        <?= ($_GET['status'] ?? '') === 'open'        ? 'selected' : '' ?>>Open</option>
        <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="solved"      <?= ($_GET['status'] ?? '') === 'solved'      ? 'selected' : '' ?>>Solved</option>
        <option value="closed"      <?= ($_GET['status'] ?? '') === 'closed'      ? 'selected' : '' ?>>Closed</option>
    </select>

    <!-- Priority -->
    <select name="priority"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600
                   bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200
                   focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">All Priorities</option>
        <option value="critical" <?= ($_GET['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
        <option value="high"     <?= ($_GET['priority'] ?? '') === 'high'     ? 'selected' : '' ?>>High</option>
        <option value="medium"   <?= ($_GET['priority'] ?? '') === 'medium'   ? 'selected' : '' ?>>Medium</option>
        <option value="low"      <?= ($_GET['priority'] ?? '') === 'low'      ? 'selected' : '' ?>>Low</option>
    </select>

    <!-- Sort -->
    <select name="sort"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600
                   bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200
                   focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="created_desc" <?= ($_GET['sort'] ?? 'created_desc') === 'created_desc' ? 'selected' : '' ?>>Newest First</option>
        <option value="created_asc"  <?= ($_GET['sort'] ?? '') === 'created_asc'  ? 'selected' : '' ?>>Oldest First</option>
        <option value="priority"     <?= ($_GET['sort'] ?? '') === 'priority'     ? 'selected' : '' ?>>By Priority</option>
    </select>

    <!-- ── Department filter (admin / super_admin only) ── -->
    <?php if (RBAC::can('ticket.view_all')): ?>
    <select name="dept"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600
                   bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200
                   focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">All Departments</option>
        <option value="IT"    <?= strtoupper($_GET['dept'] ?? '') === 'IT'    ? 'selected' : '' ?>>IT</option>
        <option value="MIS"   <?= strtoupper($_GET['dept'] ?? '') === 'MIS'   ? 'selected' : '' ?>>MIS</option>
        <option value="CLICK" <?= strtoupper($_GET['dept'] ?? '') === 'CLICK' ? 'selected' : '' ?>>CLICK</option>
    </select>
    <?php endif; ?>

    <button type="submit"
            class="px-4 py-2 rounded-xl text-sm font-medium
                   bg-slate-800 dark:bg-slate-600 text-white
                   hover:bg-slate-700 transition-colors">
        Filter
    </button>
    <a href="<?= BASE_URL ?>?page=tickets&filter=<?= $filter ?>"
       class="px-4 py-2 rounded-xl text-sm font-medium
              text-slate-500 dark:text-slate-400
              hover:text-slate-700 dark:hover:text-slate-200
              hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
        Clear
    </a>
</form>

<!-- ═══════════════════════════════════════
     TICKET TABLE
════════════════════════════════════════ -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <?php if (empty($tickets)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-slate-400">
        <svg class="w-12 h-12 mb-3 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-sm font-medium">No tickets found</p>
        <p class="text-xs mt-1">Try adjusting your filters.</p>
    </div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ticket</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">Department</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">Priority</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">Created</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($tickets as $t): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group">

                <!-- Title + Code -->
                <td class="px-5 py-4">
                    <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $t['ticket_id'] ?>" class="block">
                        <p class="font-medium text-slate-700 dark:text-slate-200
                                  group-hover:text-teal-600 dark:group-hover:text-teal-400
                                  transition-colors truncate max-w-xs">
                            <?= htmlspecialchars($t['title']) ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <?= htmlspecialchars($t['ticket_code']) ?>
                            <?php if (RBAC::can('ticket.view_all')): ?>
                            · <?= htmlspecialchars($t['creator_name']) ?>
                            <?php endif; ?>
                        </p>
                    </a>
                </td>

                <!-- Department -->
                <td class="px-4 py-4 hidden md:table-cell">
                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md
                                 bg-slate-100 dark:bg-slate-700
                                 text-slate-600 dark:text-slate-300">
                        <?= htmlspecialchars($t['assigned_department']) ?>
                    </span>
                </td>

                <!-- Status -->
                <td class="px-4 py-4"><?= listStatusBadge($t['status']) ?></td>

                <!-- Priority -->
                <td class="px-4 py-4 hidden lg:table-cell"><?= listPriorityBadge($t['priority']) ?></td>

                <!-- Created -->
                <td class="px-4 py-4 hidden lg:table-cell text-xs text-slate-400">
                    <?= date('M d, Y', strtotime($t['created_at'])) ?>
                </td>

                <!-- Arrow -->
                <td class="px-4 py-4 text-right">
                    <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $t['ticket_id'] ?>"
                       class="text-slate-300 dark:text-slate-600 group-hover:text-teal-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════
     PAGINATION
════════════════════════════════════════ -->
<?php if ($pages > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-xs text-slate-400">
        Showing <?= (($page - 1) * TICKETS_PER_PAGE) + 1 ?>–<?= min($page * TICKETS_PER_PAGE, $total) ?>
        of <?= $total ?>
    </p>
    <div class="flex items-center gap-1">
        <?php for ($i = 1; $i <= $pages; $i++):
            $href = BASE_URL . '?page=tickets&filter=' . $filter . '&p=' . $i
                  . (($_GET['dept']     ?? '') ? '&dept='     . urlencode($_GET['dept'])     : '')
                  . (($_GET['status']   ?? '') ? '&status='   . urlencode($_GET['status'])   : '')
                  . (($_GET['priority'] ?? '') ? '&priority=' . urlencode($_GET['priority']) : '')
                  . (($_GET['sort']     ?? '') ? '&sort='     . urlencode($_GET['sort'])     : '')
                  . (($_GET['q']        ?? '') ? '&q='        . urlencode($_GET['q'])        : '');
        ?>
        <a href="<?= $href ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-colors
               <?= $i === $page
                   ? 'bg-teal-600 text-white font-semibold'
                   : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
