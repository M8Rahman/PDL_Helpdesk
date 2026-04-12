<?php
/**
 * PDL_Helpdesk — User List View
 */

$roleBadge = function(string $role): string {
    $map = [
        'super_admin' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
        'admin'       => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'it'          => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
        'mis'         => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
        'normal_user' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    ];
    $labels = ['super_admin'=>'Super Admin','admin'=>'Admin','it'=>'IT','mis'=>'MIS','normal_user'=>'User'];
    $cls    = $map[$role] ?? $map['normal_user'];
    $label  = $labels[$role] ?? ucfirst($role);
    return "<span class='inline-flex px-2 py-0.5 text-xs font-semibold rounded-md {$cls}'>{$label}</span>";
};
?>

<!-- Header -->
<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="font-display text-lg font-semibold text-slate-800 dark:text-slate-100">User Management</h2>
        <p class="text-sm text-slate-400 mt-0.5"><?= number_format($total) ?> user<?= $total !== 1 ? 's' : '' ?> registered</p>
    </div>
    <?php if (RBAC::can('user.create')): ?>
    <a href="<?= BASE_URL ?>?page=users/create"
       class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium text-white transition-all hover:-translate-y-px"
       style="background:linear-gradient(135deg,#0d9488,#0f766e)">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
        </svg>
        Add User
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>" class="flex flex-wrap gap-2 mb-5">
    <input type="hidden" name="page" value="users">

    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
           placeholder="Search name, username, email…"
           class="flex-1 min-w-48 px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">

    <select name="role"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">All Roles</option>
        <option value="normal_user" <?= ($_GET['role'] ?? '') === 'normal_user' ? 'selected' : '' ?>>User</option>
        <option value="it"          <?= ($_GET['role'] ?? '') === 'it'          ? 'selected' : '' ?>>IT</option>
        <option value="mis"         <?= ($_GET['role'] ?? '') === 'mis'         ? 'selected' : '' ?>>MIS</option>
        <option value="admin"       <?= ($_GET['role'] ?? '') === 'admin'       ? 'selected' : '' ?>>Admin</option>
        <option value="super_admin" <?= ($_GET['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
    </select>

    <select name="active"
            class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500">
        <option value="">All Status</option>
        <option value="1" <?= ($_GET['active'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= ($_GET['active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit"
            class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700 transition-colors">
        Filter
    </button>
    <a href="<?= BASE_URL ?>?page=users"
       class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
        Clear
    </a>
</form>

<!-- Table -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
    <?php if (empty($users)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-slate-400">
        <svg class="w-12 h-12 mb-3 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <p class="text-sm font-medium">No users found</p>
    </div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">User</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">Role</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">Department</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">Last Login</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <?php foreach ($users as $u):
                $initials = '';
                foreach (explode(' ', $u['full_name']) as $part) {
                    $initials .= strtoupper(mb_substr($part, 0, 1));
                    if (strlen($initials) >= 2) break;
                }
                $isSelf = $u['user_id'] === Auth::id();
                $isSuperAdmin = $u['role'] === 'super_admin';
            ?>
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group">
                <!-- User cell -->
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-semibold shrink-0"
                             style="background:linear-gradient(135deg,<?= $u['is_active'] ? '#0d9488,#1e3a5f' : '#94a3b8,#64748b' ?>)">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div>
                            <p class="font-medium text-slate-700 dark:text-slate-200">
                                <?= htmlspecialchars($u['full_name']) ?>
                                <?php if ($isSelf): ?>
                                <span class="text-xs text-slate-400">(you)</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                </td>

                <td class="px-4 py-4 hidden md:table-cell">
                    <?= $roleBadge($u['role']) ?>
                </td>

                <td class="px-4 py-4 hidden lg:table-cell">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        <?= htmlspecialchars($u['department']) ?>
                    </span>
                </td>

                <td class="px-4 py-4">
                    <?php if ($u['is_active']): ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                    </span>
                    <?php endif; ?>
                </td>

                <td class="px-4 py-4 hidden lg:table-cell text-xs text-slate-400">
                    <?= $u['last_login_at'] ? date('M d, Y H:i', strtotime($u['last_login_at'])) : 'Never' ?>
                </td>

                <!-- Actions -->
                <td class="px-4 py-4 text-right">
                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">

                        <?php if (RBAC::can('user.edit') && !($isSuperAdmin && Auth::role() !== 'super_admin')): ?>
                        <a href="<?= BASE_URL ?>?page=users/edit&id=<?= $u['user_id'] ?>"
                           class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                           title="Edit user">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if (RBAC::can('user.deactivate') && !$isSelf && !$isSuperAdmin): ?>
                        <form method="POST" action="<?= BASE_URL ?>?page=users/toggle" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                            <input type="hidden" name="user_id"   value="<?= $u['user_id'] ?>">
                            <button type="submit"
                                    title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                    onclick="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')"
                                    class="p-1.5 rounded-lg transition-colors
                                        <?= $u['is_active']
                                            ? 'text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20'
                                            : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20' ?>">
                                <?php if ($u['is_active']): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <?php else: ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?php endif; ?>
                            </button>
                        </form>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="flex items-center justify-between mt-4">
    <p class="text-xs text-slate-400">
        Showing <?= (($page - 1) * USERS_PER_PAGE) + 1 ?>–<?= min($page * USERS_PER_PAGE, $total) ?> of <?= $total ?>
    </p>
    <div class="flex items-center gap-1">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= BASE_URL ?>?page=users&p=<?= $i ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-colors
               <?= $i === $page ? 'bg-teal-600 text-white font-semibold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
