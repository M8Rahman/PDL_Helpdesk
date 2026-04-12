<?php
/**
 * PDL_Helpdesk — Create User View
 */
?>

<div class="max-w-xl mx-auto">
    <a href="<?= BASE_URL ?>?page=users"
       class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Users
    </a>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h2 class="font-display font-semibold text-slate-800 dark:text-slate-100">Create New User</h2>
            <p class="text-sm text-slate-400 mt-1">Add a new user to the PDL Helpdesk system.</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>?page=users/store" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

            <div class="p-6 space-y-5">

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Full Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="full_name" required
                           placeholder="e.g. Rafiq Ahmed"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                </div>

                <!-- Username + Email -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Username <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="username" required
                               placeholder="e.g. rafiq.ahmed"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                        <p class="text-xs text-slate-400 mt-1">Letters, numbers, underscore, dot</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Email <span class="text-red-400">*</span>
                        </label>
                        <input type="email" name="email" required
                               placeholder="user@pantexdress.local"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Initial Password <span class="text-red-400">*</span>
                    </label>
                    <input type="password" name="password" required minlength="8"
                           placeholder="Minimum 8 characters"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                    <p class="text-xs text-slate-400 mt-1">The user can change this after first login.</p>
                </div>

                <!-- Role + Department -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Role <span class="text-red-400">*</span>
                        </label>
                        <select name="role" id="roleSelect" onchange="updateDepartmentOptions()"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                            <option value="normal_user">Normal User</option>
                            <option value="it">IT</option>
                            <option value="mis">MIS</option>
                            <option value="admin">Admin</option>
                            <?php if (Auth::role() === 'super_admin'): ?>
                            <option value="super_admin">Super Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Department <span class="text-red-400">*</span>
                        </label>
                        <select name="department" id="deptSelect"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                            <option value="GENERAL">General</option>
                            <option value="IT">IT</option>
                            <option value="MIS">MIS</option>
                        </select>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="flex items-start gap-2.5 p-3.5 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        IT users should be assigned to the IT department. MIS users to MIS.
                        Normal users and Admins use General.
                    </p>
                </div>

            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3">
                <a href="<?= BASE_URL ?>?page=users"
                   class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:-translate-y-px"
                        style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-suggest matching department when role changes
function updateDepartmentOptions() {
    const role = document.getElementById('roleSelect').value;
    const dept = document.getElementById('deptSelect');
    const map  = { it: 'IT', mis: 'MIS', admin: 'GENERAL', normal_user: 'GENERAL', super_admin: 'GENERAL' };
    if (map[role]) dept.value = map[role];
}
</script>
