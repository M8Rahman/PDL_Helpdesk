<!DOCTYPE html>
<html lang="en" class="h-full"
      x-data="{ darkMode: localStorage.getItem('pdl_theme') === 'dark', sidebarCollapsed: false, mobileOpen: false }"
      :class="{ 'dark': darkMode }"
      x-init="$watch('darkMode', v => localStorage.setItem('pdl_theme', v ? 'dark' : 'light'))">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'PDL Helpdesk') ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            900: '#134e4a',
                        },
                    },
                    fontFamily: {
                        sans:    ['"Inter"', 'system-ui', 'sans-serif'],
                        display: ['"Inter"', 'system-ui', 'sans-serif'],
                        mono:    ['"JetBrains Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x/dist/chart.umd.min.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* ── Status Badges ── */
        .badge-open        { background:#dbeafe; color:#1d4ed8; }
        .badge-in_progress { background:#fef3c7; color:#b45309; }
        .badge-solved      { background:#d1fae5; color:#065f46; }
        .badge-closed      { background:#f1f5f9; color:#475569; }
        .dark .badge-open        { background:rgba(30,58,95,0.7);  color:#93c5fd; }
        .dark .badge-in_progress { background:rgba(69,26,3,0.7);  color:#fcd34d; }
        .dark .badge-solved      { background:rgba(6,78,59,0.7);  color:#6ee7b7; }
        .dark .badge-closed      { background:rgba(30,41,59,0.7); color:#94a3b8; }

        /* ── Priority Badges ── */
        .badge-low      { background:#f0fdf4; color:#15803d; }
        .badge-medium   { background:#fefce8; color:#a16207; }
        .badge-high     { background:#fff7ed; color:#c2410c; }
        .badge-critical { background:#fef2f2; color:#b91c1c; }
        .dark .badge-low      { background:rgba(20,83,45,0.6);  color:#86efac; }
        .dark .badge-medium   { background:rgba(69,26,3,0.6);  color:#fcd34d; }
        .dark .badge-high     { background:rgba(67,20,7,0.6);  color:#fdba74; }
        .dark .badge-critical { background:rgba(69,10,10,0.6); color:#fca5a5; }

        /* ── Card hover ── */
        .card-hover { transition: box-shadow 0.18s ease, transform 0.18s ease; }
        .card-hover:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.09); transform: translateY(-1px); }
        .dark .card-hover:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.35); }

        /* ── Page enter ── */
        .page-content { animation: pageIn 0.28s ease both; }
        @keyframes pageIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

        /* ── LIGHT MODE surfaces ── */
        /* Background: warm light gray instead of harsh white */
        body:not(.dark) .app-bg  { background: #f1f5f9; }
        body.dark        .app-bg  { background: #0f172a; }

        /* Card surface */
        .card-surface {
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        .dark .card-surface {
            background: #1e293b;
            border-color: #334155;
        }

        /* ── Inputs consistent style ── */
        .form-control {
            width: 100%;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            color: #1e293b;
            font-size: 0.875rem;
            font-family: inherit;
            transition: border-color 0.18s, box-shadow 0.18s;
            outline: none;
        }
        .form-control:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
        }
        .dark .form-control {
            background: #1e293b;
            border-color: #334155;
            color: #e2e8f0;
        }
        .dark .form-control:focus {
            border-color: #0d9488;
        }
        .form-control::placeholder { color: #94a3b8; }

        /* ── Sidebar nav label hide when collapsed ── */
        #sidebar.collapsed .nav-label { display: none !important; }
    </style>
</head>

<body class="h-full app-bg text-slate-800 dark:text-slate-100">
<div class="flex h-full">

    <!-- ── Sidebar ─────────────────────────────────────────── -->
    <?php require ROOT_PATH . 'shared/components/sidebar.php'; ?>

    <!-- ── Main ───────────────────────────────────────────── -->
    <div class="flex flex-col flex-1 overflow-hidden min-w-0">

        <!-- ── Navbar ─────────────────────────────────────── -->
        <?php require ROOT_PATH . 'shared/components/navbar.php'; ?>

        <!-- ── Flash Messages ─────────────────────────────── -->
        <?php
        $flashSuccess = Auth::getFlash('success');
        $flashError   = Auth::getFlash('error');
        $flashInfo    = Auth::getFlash('info');
        ?>
        <?php if ($flashSuccess || $flashError || $flashInfo): ?>
        <div class="px-6 pt-4 space-y-2">
            <?php if ($flashSuccess): ?>
            <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-900/25 border border-emerald-200 dark:border-emerald-700/50 rounded-xl text-emerald-700 dark:text-emerald-300 text-sm font-medium" data-flash>
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-900/25 border border-red-200 dark:border-red-700/50 rounded-xl text-red-700 dark:text-red-300 text-sm font-medium" data-flash>
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= htmlspecialchars($flashError) ?>
            </div>
            <?php endif; ?>
            <?php if ($flashInfo): ?>
            <div class="flex items-center gap-3 px-4 py-3 bg-blue-50 dark:bg-blue-900/25 border border-blue-200 dark:border-blue-700/50 rounded-xl text-blue-700 dark:text-blue-300 text-sm font-medium" data-flash>
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= htmlspecialchars($flashInfo) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Page Content ───────────────────────────────── -->
        <main class="flex-1 overflow-y-auto p-6 page-content">
            <?= $content ?>
        </main>

    </div>
</div>

<script src="<?= STATIC_URL ?>js/app.js"></script>
</body>
</html>
