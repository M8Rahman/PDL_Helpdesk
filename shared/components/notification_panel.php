<?php
/**
 * PDL_Helpdesk — Notification Panel Component
 * Rendered inside the navbar notification dropdown via AlpineJS.
 */
?>
<div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-700">
    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Notifications</h3>
    <button onclick="pdlMarkAllRead()"
            class="text-xs text-teal-600 dark:text-teal-400 hover:text-teal-700 font-medium transition-colors">
        Mark all read
    </button>
</div>

<div id="notif-list" class="divide-y divide-slate-100 dark:divide-slate-700 max-h-72 overflow-y-auto">
    <div id="notif-loading" class="flex items-center justify-center gap-2 py-8 text-slate-400 text-sm">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        Loading…
    </div>
</div>

<div class="px-4 py-2.5 border-t border-slate-100 dark:border-slate-700">
    <a href="<?= BASE_URL ?>?page=tickets&filter=mine"
       class="text-xs text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
        View all my tickets →
    </a>
</div>

<script>
(function () {
    const BASE = <?= json_encode(BASE_URL) ?>;
    let notifLoaded = false;

    // Called by navbar when dropdown opens
    window.loadNotifications = function () {
        if (notifLoaded) return;
        notifLoaded = true;

        fetch(BASE + '?page=notifications/fetch', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            const list    = document.getElementById('notif-list');
            const loading = document.getElementById('notif-loading');
            if (loading) loading.remove();

            if (!data.notifications || data.notifications.length === 0) {
                list.innerHTML =
                    '<div class="flex flex-col items-center justify-center py-8 text-slate-400">' +
                    '<svg class="w-8 h-8 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" ' +
                    'd="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>' +
                    '</svg><p class="text-xs">No new notifications</p></div>';
                return;
            }

            list.innerHTML = data.notifications.map(function (n) {
                var dot = n.is_read
                    ? ''
                    : '<span class="w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0 mt-1.5"></span>';
                return '<a href="' + BASE + '?page=tickets/view&id=' + n.ticket_id + '" ' +
                    'onclick="pdlMarkRead(' + n.notification_id + ')" ' +
                    'class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors cursor-pointer">' +
                    dot +
                    '<div class="min-w-0 flex-1">' +
                    '<p class="text-xs text-slate-700 dark:text-slate-200 leading-snug">' + escHtml(n.message) + '</p>' +
                    '<p class="text-[11px] text-slate-400 mt-0.5">' + timeAgo(n.created_at) + '</p>' +
                    '</div></a>';
            }).join('');
        })
        .catch(function () {
            var list = document.getElementById('notif-list');
            if (list) list.innerHTML = '<p class="text-xs text-red-400 px-4 py-4">Failed to load notifications.</p>';
        });
    };

    window.pdlMarkRead = function (id) {
        fetch(BASE + '?page=notifications/read&id=' + id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    };

    window.pdlMarkAllRead = function () {
        fetch(BASE + '?page=notifications/read-all', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function () {
            var list = document.getElementById('notif-list');
            if (list) {
                list.querySelectorAll('.bg-teal-500').forEach(function (dot) { dot.remove(); });
            }
            notifLoaded = false; // allow refresh on next open
        });
    };
})();
</script>
