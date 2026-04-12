<?php
/**
 * PDL_Helpdesk — Sidebar Component
 * Fixed: full-row hover highlight, active border indicator, smooth transitions.
 */

$currentPage = $_GET['page'] ?? 'dashboard';
$currentFilter = $_GET['filter'] ?? '';
$user = Auth::user();
$role = $user['role'] ?? 'normal_user';

/**
 * Returns CSS classes for a nav item.
 * Active = teal left-border + teal bg tint. Hover = slate bg.
 */
if (!function_exists('sidebarItem')) {
    function sidebarItem(string $pageKey, string $current, string $filterKey = '', string $currentFilter = ''): string
    {
        $isActive = str_starts_with($current, $pageKey)
            && ($filterKey === '' || $currentFilter === $filterKey);

        if ($isActive) {
            return 'bg-teal-500/10 text-teal-300 font-medium border-l-2 border-teal-400 pl-[10px]';
        }
        return 'text-slate-400 hover:bg-white/7 hover:text-slate-200 border-l-2 border-transparent pl-[10px]';
    }
}
?>

<aside
    id="sidebar"
    :class="sidebarCollapsed ? 'w-[64px]' : 'w-[240px]'"
    class="relative flex flex-col bg-[#141e2e] shrink-0 transition-[width] duration-250 ease-in-out overflow-hidden z-30 border-r border-white/5"
>
    <!-- ── Brand ──────────────────────────────────────────────── -->
    <div class="flex items-center gap-3 h-[60px] px-4 border-b border-white/6 shrink-0">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
             style="background:linear-gradient(135deg,#0d9488,#1e3a5f)">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>
        <div class="nav-label overflow-hidden whitespace-nowrap">
            <p class="text-white font-semibold text-sm leading-tight">PDL Helpdesk</p>
            <p class="text-slate-500 text-[11px]">Pantex Dress Ltd.</p>
        </div>
    </div>

    <!-- ── Navigation ────────────────────────────────────────── -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2 space-y-0.5">

        <!-- Dashboard -->
        <a href="<?= BASE_URL ?>?page=dashboard"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('dashboard', $currentPage) ?>">
            <svg class="w-[18px] h-[18px] shrink-0 ml-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="nav-label text-[13px]">Dashboard</span>
        </a>

        <!-- TICKETS section -->
        <p class="nav-label px-3 pt-4 pb-1.5 text-[10px] font-semibold text-slate-600 uppercase tracking-widest">
            Tickets
        </p>

        <!-- My Tickets -->
        <a href="<?= BASE_URL ?>?page=tickets&filter=mine"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('tickets', $currentPage, 'mine', $currentFilter) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
            </svg>
            <span class="nav-label text-[13px]">My Tickets</span>
        </a>

        <!-- Department Queue (IT/MIS/Admin only) -->
        <?php if (in_array($role, ['it','mis','admin','super_admin'])): ?>
        <a href="<?= BASE_URL ?>?page=tickets&filter=department"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('tickets', $currentPage, 'department', $currentFilter) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span class="nav-label text-[13px]">Department Queue</span>
        </a>
        <?php endif; ?>

        <!-- All Tickets (Admin) -->
        <?php if (in_array($role, ['admin','super_admin'])): ?>
        <a href="<?= BASE_URL ?>?page=tickets&filter=all"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('tickets', $currentPage, 'all', $currentFilter) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="nav-label text-[13px]">All Tickets</span>
        </a>
        <?php endif; ?>

        <!-- New Ticket -->
        <a href="<?= BASE_URL ?>?page=tickets/create"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('tickets/create', $currentPage) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="nav-label text-[13px]">New Ticket</span>
        </a>

        <!-- MANAGEMENT section (Admin only) -->
        <?php if (in_array($role, ['admin','super_admin'])): ?>
        <p class="nav-label px-3 pt-4 pb-1.5 text-[10px] font-semibold text-slate-600 uppercase tracking-widest">
            Management
        </p>

        <a href="<?= BASE_URL ?>?page=users"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('users', $currentPage) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="nav-label text-[13px]">Users</span>
        </a>

        <a href="<?= BASE_URL ?>?page=reports"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('reports', $currentPage) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="nav-label text-[13px]">Reports</span>
        </a>

        <a href="<?= BASE_URL ?>?page=audit"
           class="flex items-center gap-3 py-2.5 pr-3 rounded-lg text-sm transition-all duration-150 <?= sidebarItem('audit', $currentPage) ?>">
            <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="nav-label text-[13px]">Audit Logs</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- ── Collapse Toggle ────────────────────────────────────── -->
    <div class="border-t border-white/6 p-2.5 shrink-0">
        <button
            @click="sidebarCollapsed = !sidebarCollapsed; $nextTick(() => { document.querySelectorAll('.nav-label').forEach(el => el.style.display = sidebarCollapsed ? 'none' : '') })"
            class="w-full flex items-center gap-3 px-2.5 py-2 rounded-lg text-slate-600 hover:text-slate-300 hover:bg-white/6 transition-all text-sm"
        >
            <svg class="w-[18px] h-[18px] shrink-0 transition-transform duration-250"
                 :class="sidebarCollapsed ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
            <span class="nav-label text-[12px]">Collapse</span>
        </button>
    </div>

</aside>
