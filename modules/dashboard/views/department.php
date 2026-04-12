<?php
/**
 * PDL_Helpdesk — Department Dashboard (IT / MIS)
 * Queue view, daily stats, and weekly trend chart.
 */

// Prepare weekly trend data
$trendMap = [];
foreach ($weeklyTrend as $row) {
    $trendMap[$row['day']] = (int)$row['total'];
}
$wLabels = [];
$wValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $wLabels[] = date('D', strtotime($d));
    $wValues[] = $trendMap[$d] ?? 0;
}

$priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

if (!function_exists('deptStatusBadge')) {
    function deptStatusBadge(string $s): string {
        $map = ['open'=>'badge-open','in_progress'=>'badge-in_progress','solved'=>'badge-solved','closed'=>'badge-closed'];
        return "<span class='inline-flex px-2 py-0.5 text-xs font-medium rounded-md ".($map[$s]??'badge-closed')."'>".ucfirst(str_replace('_',' ',$s))."</span>";
    }
}

if (!function_exists('deptPriorityDot')) {
    function deptPriorityDot(string $p): string {
        $colors = ['critical'=>'bg-red-500','high'=>'bg-orange-400','medium'=>'bg-amber-400','low'=>'bg-slate-400'];
        return "<span class='inline-block w-2 h-2 rounded-full ".($colors[$p]??'bg-slate-400')."' title='".ucfirst($p)."'></span>";
    }
}
?>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['label'=>'Total Queue',       'value'=>$stats['total'],             'color'=>'text-blue-600 dark:text-blue-400',    'bg'=>'bg-blue-50 dark:bg-blue-900/20',    'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['label'=>'Open',              'value'=>$stats['open'],              'color'=>'text-amber-600 dark:text-amber-400',   'bg'=>'bg-amber-50 dark:bg-amber-900/20',  'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['label'=>'Solved Today',      'value'=>$stats['solved_today'],      'color'=>'text-emerald-600 dark:text-emerald-400','bg'=>'bg-emerald-50 dark:bg-emerald-900/20','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'Avg Resolution',    'value'=>$stats['avg_resolution_hours'].'h', 'color'=>'text-violet-600 dark:text-violet-400', 'bg'=>'bg-violet-50 dark:bg-violet-900/20', 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    foreach ($cards as $card): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 card-hover">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium"><?= $card['label'] ?></p>
            <div class="w-9 h-9 rounded-xl flex items-center justify-center <?= $card['bg'] ?>">
                <svg class="w-5 h-5 <?= $card['color'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?= $card['icon'] ?>"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-display font-semibold text-slate-800 dark:text-slate-100"><?= $card['value'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Ticket Queue + Chart -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- Queue Table -->
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                <?= htmlspecialchars($department) ?> Ticket Queue
            </h3>
            <a href="<?= BASE_URL ?>?page=tickets&filter=department"
               class="text-xs text-teal-600 dark:text-teal-400 hover:underline">View all →</a>
        </div>

        <?php if (empty($queue)): ?>
        <div class="flex flex-col items-center justify-center py-12 text-slate-400">
            <svg class="w-10 h-10 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm">No open tickets. Great work! 🎉</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($queue as $t): ?>
            <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $t['ticket_id'] ?>"
               class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors group">
                <?= deptPriorityDot($t['priority']) ?>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                        <?= htmlspecialchars($t['title']) ?>
                    </p>
                    <p class="text-xs text-slate-400">
                        <?= htmlspecialchars($t['ticket_code']) ?> ·
                        <?= htmlspecialchars($t['creator_name']) ?> ·
                        <?= date('M d, H:i', strtotime($t['created_at'])) ?>
                    </p>
                </div>
                <?= deptStatusBadge($t['status']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Weekly Trend Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">This Week</h3>
        <div class="h-48">
            <canvas id="weekChart"></canvas>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 grid grid-cols-2 gap-3 text-center">
            <div>
                <p class="text-2xl font-display font-semibold text-slate-800 dark:text-slate-100"><?= $stats['in_progress'] ?></p>
                <p class="text-xs text-slate-400">In Progress</p>
            </div>
            <div>
                <p class="text-2xl font-display font-semibold text-slate-800 dark:text-slate-100"><?= $stats['avg_resolution_hours'] ?>h</p>
                <p class="text-xs text-slate-400">Avg Resolution</p>
            </div>
        </div>
    </div>

</div>

<script>
const isDark   = document.documentElement.classList.contains('dark');
const textClr  = isDark ? '#94a3b8' : '#64748b';
const gridClr  = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(100,116,139,0.1)';

new Chart(document.getElementById('weekChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($wLabels) ?>,
        datasets: [{
            label: 'Tickets',
            data: <?= json_encode($wValues) ?>,
            borderColor: '#0d9488',
            backgroundColor: 'rgba(13,148,136,0.12)',
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: '#0d9488',
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: textClr, font: { size: 11 } } },
            y: { grid: { color: gridClr }, ticks: { color: textClr, precision: 0 }, beginAtZero: true }
        }
    }
});
</script>
