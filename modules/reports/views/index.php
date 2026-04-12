<?php
/**
 * PDL_Helpdesk — Reports & Analytics View
 */

// Prepare chart data
$statusLabels  = [];
$statusData    = [];
$statusColors  = ['open'=>'#3b82f6','in_progress'=>'#f59e0b','solved'=>'#10b981','closed'=>'#94a3b8'];
foreach ($byStatus as $row) {
    $statusLabels[] = ucfirst(str_replace('_',' ',$row['status']));
    $statusData[]   = (int)$row['total'];
}

$priorityColors = ['critical'=>'#ef4444','high'=>'#f97316','medium'=>'#eab308','low'=>'#22c55e'];
$priorityLabels = array_map(fn($r) => ucfirst($r['priority']), $byPriority);
$priorityData   = array_map(fn($r) => (int)$r['total'], $byPriority);
$priorityBgColors = array_map(fn($r) => $priorityColors[$r['priority']] ?? '#94a3b8', $byPriority);
?>

<!-- Header + Export -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="font-display text-lg font-semibold text-slate-800 dark:text-slate-100">Reports & Analytics</h2>
        <p class="text-sm text-slate-400 mt-0.5">
            Showing data from <?= date('M d, Y', strtotime($dateFrom)) ?> to <?= date('M d, Y', strtotime($dateTo)) ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>?page=reports/export&format=csv&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
       class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-white transition-all hover:-translate-y-px"
       style="background:linear-gradient(135deg,#0d9488,#0f766e)">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
</div>

<!-- Date Range Filter -->
<form method="GET" action="<?= BASE_URL ?>" class="flex flex-wrap items-center gap-2 mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3">
    <input type="hidden" name="page" value="reports">
    <span class="text-sm text-slate-500 dark:text-slate-400 font-medium">Date range:</span>
    <input type="date" name="date_from" value="<?= $dateFrom ?>"
           class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
    <span class="text-slate-400 text-sm">to</span>
    <input type="date" name="date_to" value="<?= $dateTo ?>"
           class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
    <button type="submit"
            class="px-4 py-1.5 rounded-lg text-sm font-medium bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700 transition-colors">
        Apply
    </button>
    <?php foreach ([7=>>'Last 7 days', 30=>'Last 30 days', 90=>'Last 90 days'] as $days => $label): ?>
    <a href="<?= BASE_URL ?>?page=reports&date_from=<?= date('Y-m-d',strtotime("-{$days} days")) ?>&date_to=<?= date('Y-m-d') ?>"
       class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</form>

<!-- Volume Chart (full width) -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 mb-5">
    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Daily Ticket Volume</h3>
    <div class="h-52">
        <canvas id="volumeChart"></canvas>
    </div>
</div>

<!-- Row: Status Pie + Priority Bar -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Status Distribution</h3>
        <div class="h-44"><canvas id="statusChart"></canvas></div>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <?php foreach ($byStatus as $i => $row): ?>
            <div class="flex items-center justify-between text-xs">
                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full" style="background:<?= array_values($statusColors)[$i % 4] ?>"></span>
                    <?= ucfirst(str_replace('_',' ',$row['status'])) ?>
                </span>
                <span class="font-semibold text-slate-700 dark:text-slate-200"><?= $row['total'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-4">Priority Distribution</h3>
        <div class="h-44"><canvas id="priorityChart"></canvas></div>
    </div>
</div>

<!-- Department Table -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Department Breakdown</h3>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Department</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Resolved</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Active</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg Resolution</th>
                <th class="px-5 py-3 w-36"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($byDepartment as $dept): ?>
            <?php
                $resolvedPct = $dept['total'] > 0 ? round($dept['resolved'] / $dept['total'] * 100) : 0;
            ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                <td class="px-5 py-4">
                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                        <?= htmlspecialchars($dept['department']) ?>
                    </span>
                </td>
                <td class="px-5 py-4 text-right font-semibold text-slate-700 dark:text-slate-200"><?= $dept['total'] ?></td>
                <td class="px-5 py-4 text-right text-emerald-600 dark:text-emerald-400 font-medium"><?= $dept['resolved'] ?></td>
                <td class="px-5 py-4 text-right text-amber-600 dark:text-amber-400 font-medium"><?= $dept['active'] ?></td>
                <td class="px-5 py-4 text-right text-slate-500 dark:text-slate-400 text-xs">
                    <?= $dept['avg_hours'] ? $dept['avg_hours'] . 'h' : '—' ?>
                </td>
                <td class="px-5 py-4">
                    <!-- Resolution progress bar -->
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width:<?= $resolvedPct ?>%"></div>
                        </div>
                        <span class="text-xs text-slate-400 w-8 text-right"><?= $resolvedPct ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Employee Performance Table -->
<?php if (!empty($performance)): ?>
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Employee Performance</h3>
        <p class="text-xs text-slate-400 mt-0.5">Sorted by tickets resolved in this period.</p>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Rank</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Resolved</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg Hours</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Best</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($performance as $i => $p): ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                <td class="px-5 py-4">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        <?= $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                          : ($i === 1 ? 'bg-slate-200 text-slate-600 dark:bg-slate-600 dark:text-slate-200'
                          : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400') ?>">
                        <?= $i + 1 ?>
                    </span>
                </td>
                <td class="px-5 py-4">
                    <p class="font-medium text-slate-700 dark:text-slate-200"><?= htmlspecialchars($p['full_name']) ?></p>
                    <p class="text-xs text-slate-400"><?= strtoupper($p['department']) ?> · <?= ucfirst($p['role']) ?></p>
                </td>
                <td class="px-5 py-4 text-right">
                    <span class="font-semibold text-slate-700 dark:text-slate-200"><?= $p['resolved_count'] ?></span>
                </td>
                <td class="px-5 py-4 text-right text-slate-500 dark:text-slate-400"><?= $p['avg_hours'] ?>h</td>
                <td class="px-5 py-4 text-right text-emerald-600 dark:text-emerald-400 text-xs"><?= $p['min_hours'] ?>h</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script>
const isDark    = document.documentElement.classList.contains('dark');
const textColor = isDark ? '#94a3b8' : '#64748b';
const gridColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(100,116,139,0.08)';

// Volume Bar
new Chart(document.getElementById('volumeChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($volumeLabels) ?>,
        datasets: [{
            label: 'Tickets Created',
            data: <?= json_encode($volumeValues) ?>,
            backgroundColor: 'rgba(13,148,136,0.7)',
            borderRadius: 4, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 }, maxRotation: 45 } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
        }
    }
});

// Status Doughnut
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: ['#3b82f6','#f59e0b','#10b981','#94a3b8'],
            borderWidth: 0, hoverOffset: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// Priority Bar
new Chart(document.getElementById('priorityChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($priorityLabels) ?>,
        datasets: [{
            data: <?= json_encode($priorityData) ?>,
            backgroundColor: <?= json_encode($priorityBgColors) ?>,
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
        }
    }
});
</script>
