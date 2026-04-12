<?php
/**
 * PDL_Helpdesk — Edit User View
 * Includes profile edit + password reset in separate forms.
 */
?>

<div class="max-w-xl mx-auto space-y-5">
    <a href="<?= BASE_URL ?>?page=users"
       class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Users
    </a>

    <!-- User Stats Banner -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4">
        <?php
        $initials = '';
        foreach (explode(' ', $user['full_name']) as $part) {
            $initials .= strtoupper(mb_substr($part, 0, 1));
            if (strlen($initials) >= 2) break;
        }
        ?>
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-lg font-semibold shrink-0"
             style="background:linear-gradient(135deg,#0d9488,#1e3a5f)">
            <?= htmlspecialchars($initials) ?>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-display font-semibold text-slate-800 dark:text-slate-100 truncate"><?= htmlspecialchars($user['full_name']) ?></p>
            <p class="text-sm text-slate-400">@<?= htmlspecialchars($user['username']) ?> · <?= htmlspecialchars($user['email']) ?></p>
            <p class="text-xs text-slate-400 mt-1">
                Joined <?= date('M d, Y', strtotime($user['created_at'])) ?>
                <?php if ($user['last_login_at']): ?> · Last login <?= date('M d, Y H:i', strtotime($user['last_login_at'])) ?><?php endif; ?>
            </p>
        </div>
        <div class="text-right shrink-0">
            <p class="text-2xl font-display font-semibold text-slate-800 dark:text-slate-100"><?= $ticketSummary['total'] ?></p>
            <p class="text-xs text-slate-400">tickets</p>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-display font-semibold text-slate-800 dark:text-slate-100 text-sm">Edit Profile</h3>
        </div>

        <form method="POST" action="<?= BASE_URL ?>?page=users/update" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
            <input type="hidden" name="user_id"   value="<?= $user['user_id'] ?>">

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full Name</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Role</label>
                        <select name="role"
                                <?= $user['role'] === 'super_admin' && Auth::role() !== 'super_admin' ? 'disabled' : '' ?>
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                            <?php foreach (['normal_user'=>'Normal User','it'=>'IT','mis'=>'MIS','admin'=>'Admin','super_admin'=>'Super Admin'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $user['role'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Department</label>
                        <select name="department"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                            <?php foreach (['GENERAL','IT','MIS','CLICK'] as $dept): ?>
                            <option value="<?= $dept ?>" <?= $user['department'] === $dept ? 'selected' : '' ?>><?= $dept ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-3">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:-translate-y-px"
                        style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Reset Password Form -->
    <?php if (RBAC::can('user.reset_password') && !($user['role'] === 'super_admin' && Auth::role() !== 'super_admin')): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-display font-semibold text-slate-800 dark:text-slate-100 text-sm">Reset Password</h3>
            <p class="text-xs text-slate-400 mt-0.5">Set a new password for this user.</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>?page=users/reset-password"
              onsubmit="return confirm('Reset password for this user?')">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
            <input type="hidden" name="user_id"   value="<?= $user['user_id'] ?>">

            <div class="p-6">
                <input type="password" name="new_password" required minlength="8"
                       placeholder="New password (min 8 characters)"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                <button type="submit"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-amber-600 hover:bg-amber-500 transition-colors">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Danger Zone -->
    <?php if (RBAC::can('user.deactivate') && $user['user_id'] !== Auth::id() && $user['role'] !== 'super_admin'): ?>
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-red-200 dark:border-red-900/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-100 dark:border-red-900/30">
            <h3 class="font-display font-semibold text-red-600 dark:text-red-400 text-sm">Danger Zone</h3>
        </div>
        <div class="p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                    <?= $user['is_active'] ? 'Deactivate Account' : 'Activate Account' ?>
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    <?= $user['is_active']
                        ? 'The user will no longer be able to log in.'
                        : 'Restore the user\'s access to the system.' ?>
                </p>
            </div>
            <form method="POST" action="<?= BASE_URL ?>?page=users/toggle">
                <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                <input type="hidden" name="user_id"   value="<?= $user['user_id'] ?>">
                <button type="submit"
                        onclick="return confirm('<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors
                            <?= $user['is_active']
                                ? 'bg-red-600 hover:bg-red-500 text-white'
                                : 'bg-emerald-600 hover:bg-emerald-500 text-white' ?>">
                    <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
