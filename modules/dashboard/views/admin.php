<?php
/**
 * PDL_Helpdesk — Admin Dashboard View
 * Full analytics overview: stats, charts, performers, recent tickets.
 */

// Prepare chart data as JSON for Chart.js
$statusLabels  = [];
$statusData    = [];
$statusColors  = [
    'open'        => '#3b82f6',
    'in_progress' => '#f59e0b',
    'solved'      => '#10b981',
    'closed'      => '#94a3b8',
];
foreach ($statusBreakdown as $row) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
    $statusData[]   = (int)$row['total'];
}

$deptLabels  = array_column($byDepartment, 'department');
$deptTotal   = array_column($byDepartment, 'total');
$deptActive  = array_column($byDepartment, 'active');
$deptResolved = array_column($byDepartment, 'resolved');

// Fill missing days in trend with 0
$trendMap = [];
foreach ($dailyTrend as $row) {
    $trendMap[$row['day']] = (int)$row['total'];
}
$trendLabels = [];
$trendValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $trendLabels[] = date('M d', strtotime($d));
    $trendValues[] = $trendMap[$d] ?? 0;
}

// Status badge helper
if (!function_exists('statusBadge')) {
    function statusBadge(string $status): string {
        $map = [
            'open'        => 'badge-open',
            'in_progress' => 'badge-in_progress',
            'solved'      => 'badge-solved',
            'closed'      => 'badge-closed',
        ];
        $label = ucfirst(str_replace('_', ' ', $status));
        $cls   = $map[$status] ?? 'badge-closed';
        return "<span class='inline-flex px-2 py-0.5 text-xs font-medium rounded-md {$cls}'>{$label}</span>";
    }
}

if (!function_exists('priorityBadge')) {
    function priorityBadge(string $p): string {
        $cls = "badge-{$p}";
        return "<span class='inline-flex px-2 py-0.5 text-xs font-medium rounded-md {$cls}'>" . ucfirst($p) . "</span>";
    }
}
?>

<!-- ── Stat Cards ────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <?php
    $cards = [
        ['label'=>'Total Tickets',   'value'=>$stats['total_tickets'],  'icon'=>'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z', 'color'=>'text-blue-600 dark:text-blue-400',  'bg'=>'bg-blue-50 dark:bg-blue-900/20'],
        ['label'=>'Open',            'value'=>$stats['open'],           'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'color'=>'text-amber-600 dark:text-amber-400',  'bg'=>'bg-amber-50 dark:bg-amber-900/20'],
        ['label'=>'Resolved Today',  'value'=>$stats['solved_today'],   'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'text-emerald-600 dark:text-emerald-400', 'bg'=>'bg-emerald-50 dark:bg-emerald-900/20'],
        ['label'=>'Avg Resolution',  'value'=>$stats['avg_resolution'].'h', 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color'=>'text-violet-600 dark:text-violet-400',  'bg'=>'bg-violet-50 dark:bg-violet-900/20'],
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

<!-- ── Charts Row ─────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    <!-- Daily Trend (Bar) -->
    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Ticket Volume — Last 14 Days</h3>
        <div class="h-48">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Status Pie -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Status Breakdown</h3>
        <div class="h-40">
            <canvas id="statusChart"></canvas>
        </div>
        <!-- Legend -->
        <div class="mt-3 grid grid-cols-2 gap-1.5">
            <?php foreach ($statusBreakdown as $i => $row): ?>
            <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                <span class="w-2 h-2 rounded-full shrink-0" style="background:<?= array_values($statusColors)[$i % 4] ?>"></span>
                <?= ucfirst(str_replace('_',' ',$row['status'])) ?> (<?= $row['total'] ?>)
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Department Comparison (Bar) ──────────────────────── -->
<div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 mb-6">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Department Performance</h3>
    <div class="h-44">
        <canvas id="deptChart"></canvas>
    </div>
</div>

<!-- ── Bottom Row ─────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <!-- Top Performers -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Top Performers — Last 30 Days</h3>
        <?php if (empty($topPerformers)): ?>
        <p class="text-sm text-slate-400 py-4 text-center">No resolved tickets in the last 30 days.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($topPerformers as $i => $p): ?>
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                    <?= $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400' ?>">
                    <?= $i + 1 ?>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">
                        <?= htmlspecialchars($p['full_name']) ?>
                    </p>
                    <p class="text-xs text-slate-400"><?= strtoupper($p['department']) ?></p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200"><?= $p['resolved_count'] ?> resolved</p>
                    <p class="text-xs text-slate-400"><?= round((float)$p['avg_hours'], 1) ?>h avg</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Tickets -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Recent Tickets</h3>
            <a href="<?= BASE_URL ?>?page=tickets&filter=all"
               class="text-xs text-teal-600 dark:text-teal-400 hover:underline">View all →</a>
        </div>
        <div class="space-y-2">
            <?php foreach ($recentTickets as $t): ?>
            <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $t['ticket_id'] ?>"
               class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                        <?= htmlspecialchars($t['title']) ?>
                    </p>
                    <p class="text-xs text-slate-400">
                        <?= htmlspecialchars($t['ticket_code']) ?> · <?= htmlspecialchars($t['assigned_department']) ?>
                        · <?= htmlspecialchars($t['creator_name']) ?>
                    </p>
                </div>
                <?= statusBadge($t['status']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Chart.js Scripts -->
<script>
const isDark = document.documentElement.classList.contains('dark');
const textColor  = isDark ? '#94a3b8' : '#64748b';
const gridColor  = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(100,116,139,0.1)';

// ── Daily Trend Bar ──────────────────────────────────────
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
            label: 'Tickets',
            data: <?= json_encode($trendValues) ?>,
            backgroundColor: 'rgba(13,148,136,0.7)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 } } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, font: { size: 11 }, precision: 0 }, beginAtZero: true }
        }
    }
});

// ── Status Pie ───────────────────────────────────────────
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: ['#3b82f6','#f59e0b','#10b981','#94a3b8'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// ── Department Comparison Bar ────────────────────────────
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($deptLabels) ?>,
        datasets: [
            { label: 'Active', data: <?= json_encode($deptActive) ?>, backgroundColor: '#f59e0b', borderRadius: 4 },
            { label: 'Resolved', data: <?= json_encode($deptResolved) ?>, backgroundColor: '#10b981', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: textColor, font: { size: 11 }, boxWidth: 10 } } },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
        }
    }
});
</script>
