/**
 * PDL_Helpdesk — Global Application JS
 * Dark mode persistence, flash messages, utilities.
 */

// ── Apply dark mode before first paint ────────────────────────
(function () {
    if (localStorage.getItem('pdl_theme') === 'dark') {
        document.documentElement.classList.add('dark');
    }
})();

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-dismiss flash messages after 5s ──────────────────
    document.querySelectorAll('[data-flash]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-4px)';
            setTimeout(function () { el.remove(); }, 420);
        }, 5000);
    });

    // ── Confirm on data-confirm elements ──────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ── Request browser notification permission once ───────────
    if ('Notification' in window && Notification.permission === 'default') {
        document.addEventListener('click', function req() {
            Notification.requestPermission();
            document.removeEventListener('click', req);
        }, { once: true });
    }

});

// ── Notification helpers (called from notification_panel.php) ──

function loadNotifications() {
    fetch('<?php /* BASE_URL set via inline script in layout */ ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    // Implemented inline in notification_panel.php — this stub exists for reference
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'just now';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

function pushBrowserNotification(title, body, url) {
    if ('Notification' in window && Notification.permission === 'granted') {
        const n = new Notification(title, { body });
        if (url) n.onclick = function () { window.focus(); window.location.href = url; };
    }
}
