<?php
/**
 * PDL_Helpdesk — Normal User Dashboard View
 * Shows the user's own ticket stats and recent activity.
 */
?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-slate-800 to-slate-700 dark:from-slate-900 dark:to-slate-800 rounded-2xl p-6 mb-6 flex items-center justify-between">
    <div>
        <h2 class="font-display text-xl font-semibold text-white mb-1">
            Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>,
            <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> 👋
        </h2>
        <p class="text-slate-400 text-sm">Here's a summary of your support tickets.</p>
    </div>
    <a href="<?= BASE_URL ?>?page=tickets/create"
       class="shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-white transition-all"
       style="background:linear-gradient(135deg,#0d9488,#0f766e)">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Ticket
    </a>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php
    $cards = [
        ['label'=>'Total',       'value'=>$stats['total'],       'color'=>'text-slate-600 dark:text-slate-300',   'bg'=>'bg-slate-100 dark:bg-slate-700'],
        ['label'=>'Open',        'value'=>$stats['open'],        'color'=>'text-blue-600 dark:text-blue-400',     'bg'=>'bg-blue-50 dark:bg-blue-900/20'],
        ['label'=>'In Progress', 'value'=>$stats['in_progress'], 'color'=>'text-amber-600 dark:text-amber-400',   'bg'=>'bg-amber-50 dark:bg-amber-900/20'],
        ['label'=>'Solved',      'value'=>$stats['solved'],      'color'=>'text-emerald-600 dark:text-emerald-400','bg'=>'bg-emerald-50 dark:bg-emerald-900/20'],
        ['label'=>'Closed',      'value'=>$stats['closed'],      'color'=>'text-slate-500 dark:text-slate-400',   'bg'=>'bg-slate-100 dark:bg-slate-700'],
    ];
    foreach ($cards as $card): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover text-center">
        <p class="text-3xl font-display font-semibold <?= $card['color'] ?> mb-1"><?= $card['value'] ?></p>
        <p class="text-xs text-slate-400 font-medium"><?= $card['label'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recent Tickets -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Recent Tickets</h3>
        <a href="<?= BASE_URL ?>?page=tickets&filter=mine"
           class="text-xs text-teal-600 dark:text-teal-400 hover:underline">View all →</a>
    </div>

    <?php if (empty($recentTickets)): ?>
    <div class="flex flex-col items-center justify-center py-14 text-slate-400">
        <svg class="w-12 h-12 mb-3 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
        </svg>
        <p class="text-sm font-medium mb-1">No tickets yet</p>
        <p class="text-xs mb-4">Create your first support ticket to get started.</p>
        <a href="<?= BASE_URL ?>?page=tickets/create"
           class="px-4 py-2 rounded-xl text-sm font-medium text-white"
           style="background:linear-gradient(135deg,#0d9488,#0f766e)">
            Create Ticket
        </a>
    </div>
    <?php else: ?>
    <div class="divide-y divide-slate-100 dark:divide-slate-700">
        <?php foreach ($recentTickets as $t):
            $statusMap = ['open'=>'badge-open','in_progress'=>'badge-in_progress','solved'=>'badge-solved','closed'=>'badge-closed'];
            $sCls = $statusMap[$t['status']] ?? 'badge-closed';
        ?>
        <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $t['ticket_id'] ?>"
           class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors group">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                    <?= htmlspecialchars($t['title']) ?>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    <?= htmlspecialchars($t['ticket_code']) ?> · <?= date('M d, Y H:i', strtotime($t['created_at'])) ?>
                </p>
            </div>
            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md <?= $sCls ?>">
                <?= ucfirst(str_replace('_',' ',$t['status'])) ?>
            </span>
            <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-teal-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
