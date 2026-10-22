<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-6 lg:p-8 overflow-y-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-extrabold text-[#0B2E6F] dark:text-white">Change Password</h1>
        <p class="text-xs text-[#64748B] dark:text-slate-400">Update your account security password.</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-[#DCE6F2] dark:border-slate-700 shadow-sm p-6 max-w-md">
        <form class="space-y-4">
            <!-- New Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">NEW PASSWORD *</label>
                </div>
                <div class="relative" x-data="{ showPass: false }">
                    <input :type="showPass ? 'text' : 'password'" required placeholder="••••••••••••••••" class="w-full px-4 py-3 rounded-2xl border-2 border-blue-500 dark:border-blue-600 dark:bg-slate-900 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-600 pr-12 transition-all">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-slate-700 dark:text-slate-400 focus:outline-none" tabindex="-1">
                        <i x-show="!showPass" class="fa-solid fa-eye text-sm"></i>
                        <i x-show="showPass" class="fa-solid fa-eye-slash text-sm" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm New Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">CONFIRM NEW PASSWORD *</label>
                <div class="relative" x-data="{ showPass: false }">
                    <input :type="showPass ? 'text' : 'password'" required placeholder="••••••••••••••••" class="w-full px-4 py-3 rounded-2xl border-2 border-blue-500 dark:border-blue-600 dark:bg-slate-900 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-600 pr-12 transition-all">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-slate-700 dark:text-slate-400 focus:outline-none" tabindex="-1">
                        <i x-show="!showPass" class="fa-solid fa-eye text-sm"></i>
                        <i x-show="showPass" class="fa-solid fa-eye-slash text-sm" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <button type="button" onclick="alert('Password changed successfully!')" class="w-full py-3.5 rounded-xl bg-[#2563EB] hover:bg-[#0F3D91] text-white font-bold text-xs shadow hover:shadow-lg transition">
                Update Password
            </button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
