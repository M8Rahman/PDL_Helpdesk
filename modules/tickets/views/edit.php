<?php
/**
 * PDL_Helpdesk — Edit Ticket View
 */
?>

<div class="max-w-2xl mx-auto">
    <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $ticket['ticket_id'] ?>"
       class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Ticket
    </a>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h2 class="font-display font-semibold text-slate-800 dark:text-slate-100">
                Edit Ticket — <?= htmlspecialchars($ticket['ticket_code']) ?>
            </h2>
        </div>

        <form method="POST" action="<?= BASE_URL ?>?page=tickets/update" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
            <input type="hidden" name="ticket_id"  value="<?= $ticket['ticket_id'] ?>">

            <div class="p-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Title <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="title" required minlength="5"
                           value="<?= htmlspecialchars($ticket['title']) ?>"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Priority
                    </label>
                    <select name="priority"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                        <?php foreach (['low','medium','high','critical'] as $p): ?>
                        <option value="<?= $p ?>" <?= $ticket['priority'] === $p ? 'selected' : '' ?>>
                            <?= ucfirst($p) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Description <span class="text-red-400">*</span>
                    </label>
                    <textarea name="description" rows="8" required minlength="10"
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none transition"><?= htmlspecialchars($ticket['description']) ?></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3">
                <a href="<?= BASE_URL ?>?page=tickets/view&id=<?= $ticket['ticket_id'] ?>"
                   class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:-translate-y-px"
                        style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
