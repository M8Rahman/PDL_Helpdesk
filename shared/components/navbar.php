<?php
/**
 * PDL_Helpdesk — Navbar Component
 * Improved: logo visibility, Spiffy Script font, clean light mode.
 */

$user        = Auth::user();
$unreadCount = Auth::isLoggedIn() ? Notification::countUnread($user['user_id']) : 0;

$roleLabels = [
    'normal_user' => 'User',
    'it'          => 'IT',
    'mis'         => 'MIS',
    'admin'       => 'Admin',
    'super_admin' => 'Super Admin',
];
$roleLabel = $roleLabels[$user['role']] ?? ucfirst($user['role'] ?? '');

$roleBadgeClass = match($user['role'] ?? '') {
    'super_admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    'admin'       => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    'it'          => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
    'mis'         => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
    default       => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
};

$initials = '';
foreach (explode(' ', $user['full_name'] ?? 'U') as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
    if (strlen($initials) >= 2) break;
}
?>

<!-- Load custom font CSS -->
<link rel="stylesheet" href="<?= STATIC_URL ?>css/fonts.css">

<header class="sticky top-0 z-20 h-[60px] flex items-center
               bg-white/90 dark:bg-slate-900/90
               backdrop-blur-md
               border-b border-slate-200 dark:border-slate-800
               px-5">

    <div class="flex items-center justify-between w-full gap-4">

        <!-- Left: Page title -->
        <div class="flex items-center gap-3 min-w-0">
            <!-- Mobile hamburger -->
            <button @click="mobileOpen = !mobileOpen"
                    class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="min-w-0">
                <h1 class="text-[15px] font-semibold text-slate-800 dark:text-slate-100 truncate leading-tight">
                    <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
                </h1>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-1.5 shrink-0">

            <!-- Dark mode toggle -->
            <button @click="darkMode = !darkMode"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                    :title="darkMode ? 'Light mode' : 'Dark mode'">
                <svg x-show="darkMode" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                </svg>
                <svg x-show="!darkMode" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>

            <!-- Notification bell -->
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open; if(open) loadNotifications()"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all relative">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <?php if ($unreadCount > 0): ?>
                    <span class="absolute top-1.5 right-1.5 min-w-[16px] h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-0.5">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                    <?php endif; ?>
                </button>

                <!-- Notification dropdown -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-lg shadow-slate-200/60 dark:shadow-black/40 border border-slate-200 dark:border-slate-700 overflow-hidden z-50"
                     style="display:none;">
                    <?php require ROOT_PATH . 'shared/components/notification_panel.php'; ?>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-px h-6 bg-slate-200 dark:bg-slate-700 mx-1"></div>

            <!-- User menu -->
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                    <!-- Avatar -->
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[11px] font-bold shrink-0"
                         style="background:linear-gradient(135deg,#0d9488,#1e3a5f)">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div class="hidden sm:block text-left">
                        <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-200 leading-tight">
                            <?= htmlspecialchars($user['full_name'] ?? 'User') ?>
                        </p>
                        <span class="text-[11px] px-1.5 py-0.5 rounded-md font-medium <?= $roleBadgeClass ?>">
                            <?= $roleLabel ?>
                        </span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150 ml-0.5"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-800 rounded-xl shadow-lg shadow-slate-200/60 dark:shadow-black/40 border border-slate-200 dark:border-slate-700 py-1 z-50"
                     style="display:none;">
                    <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-700">
                        <p class="text-[11px] text-slate-400">Signed in as</p>
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">
                            @<?= htmlspecialchars($user['username'] ?? '') ?>
                        </p>
                    </div>
                    <a href="<?= BASE_URL ?>?page=auth/logout"
                       onclick="return confirm('Sign out of PDL Helpdesk?')"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
