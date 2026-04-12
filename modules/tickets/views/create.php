<?php
/**
 * PDL_Helpdesk — Create Ticket View
 * Includes drag-drop upload, paste screenshot, inline validation.
 */
?>

<div class="max-w-2xl mx-auto">

    <!-- Back Link -->
    <a href="<?= BASE_URL ?>?page=tickets"
       class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Tickets
    </a>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h2 class="font-display font-semibold text-slate-800 dark:text-slate-100">Create New Ticket</h2>
            <p class="text-sm text-slate-400 mt-1">Describe your issue in detail to help us resolve it faster.</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>?page=tickets/store" enctype="multipart/form-data" id="createForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

            <div class="p-6 space-y-5">

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="title">
                        Issue Title <span class="text-red-400">*</span>
                    </label>
                    <input type="text" id="title" name="title" required minlength="5"
                           placeholder="Briefly describe the issue…"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent placeholder-slate-400 transition">
                    <p class="text-xs text-slate-400 mt-1">Minimum 5 characters.</p>
                </div>

                <!-- Department & Priority Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="department">
                            Department <span class="text-red-400">*</span>
                        </label>
                        <select id="department" name="department" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                            <option value="IT">IT Support</option>
                            <option value="MIS">MIS</option>
                            <?php if (RBAC::can('ticket.create_for_click')): ?>
                            <option value="CLICK">CLICK (External Dev)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="priority">
                            Priority <span class="text-red-400">*</span>
                        </label>
                        <select id="priority" name="priority" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🟠 High</option>
                            <option value="critical">🔴 Critical</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" for="description">
                        Description <span class="text-red-400">*</span>
                    </label>
                    <textarea id="description" name="description" rows="6" required minlength="10"
                              placeholder="Provide as much detail as possible: what happened, when, what you were doing, any error messages…"
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700/50 text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent placeholder-slate-400 transition resize-none"></textarea>
                </div>

                <!-- Attachment Upload -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Screenshots / Attachments
                    </label>

                    <!-- Drop Zone -->
                    <div id="dropZone"
                         class="relative border-2 border-dashed border-slate-200 dark:border-slate-600 rounded-xl p-6 text-center transition-all cursor-pointer
                                hover:border-teal-400 hover:bg-teal-50/40 dark:hover:bg-teal-900/10"
                         onclick="document.getElementById('fileInput').click()">
                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-slate-400">
                            <span class="text-teal-600 dark:text-teal-400 font-medium">Click to upload</span>
                            or drag & drop here
                        </p>
                        <p class="text-xs text-slate-400 mt-1">PNG, JPG, GIF, WEBP · Max 10MB each · Up to 5 files</p>
                        <p class="text-xs text-slate-400">You can also <span class="text-teal-600 dark:text-teal-400">paste screenshots</span> directly (Ctrl+V)</p>

                        <input type="file" id="fileInput" name="attachments[]"
                               multiple accept="image/*" class="hidden">
                    </div>

                    <!-- Preview grid -->
                    <div id="previewGrid" class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-2 hidden"></div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-3">
                <a href="<?= BASE_URL ?>?page=tickets"
                   class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:-translate-y-px active:translate-y-0"
                        style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                    Submit Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Drag and Drop ─────────────────────────────────────────────
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const preview   = document.getElementById('previewGrid');

['dragover','dragenter'].forEach(ev => {
    dropZone.addEventListener(ev, e => {
        e.preventDefault();
        dropZone.classList.add('border-teal-400','bg-teal-50/60');
    });
});
['dragleave','drop'].forEach(ev => {
    dropZone.addEventListener(ev, e => {
        e.preventDefault();
        dropZone.classList.remove('border-teal-400','bg-teal-50/60');
    });
});
dropZone.addEventListener('drop', e => {
    addFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => addFiles(fileInput.files));

// ── Screenshot Paste ──────────────────────────────────────────
document.addEventListener('paste', e => {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    const imageFiles = [];
    for (const item of items) {
        if (item.type.startsWith('image/')) {
            imageFiles.push(item.getAsFile());
        }
    }
    if (imageFiles.length) addFiles(imageFiles);
});

// ── File Management ───────────────────────────────────────────
let allFiles = [];

function addFiles(files) {
    const arr = Array.from(files);
    if (allFiles.length + arr.length > 5) {
        alert('Maximum 5 files allowed.');
        return;
    }
    arr.forEach(f => {
        if (f.size > 10 * 1024 * 1024) { alert(`${f.name} exceeds 10MB.`); return; }
        if (!f.type.startsWith('image/')) { alert(`${f.name} is not an image.`); return; }
        allFiles.push(f);
        renderPreview(f, allFiles.length - 1);
    });
    syncInputFiles();
}

function renderPreview(file, idx) {
    preview.classList.remove('hidden');
    const reader = new FileReader();
    reader.onload = ev => {
        const wrap = document.createElement('div');
        wrap.className = 'relative group rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 aspect-square';
        wrap.innerHTML = `
            <img src="${ev.target.result}" class="w-full h-full object-cover">
            <button type="button" onclick="removeFile(${idx})"
                    class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-xs font-bold">
                ✕
            </button>
            <p class="absolute bottom-0 left-0 right-0 bg-black/40 text-white text-[10px] px-1 py-0.5 truncate">${file.name}</p>`;
        wrap.id = `preview-${idx}`;
        preview.appendChild(wrap);
    };
    reader.readAsDataURL(file);
}

function removeFile(idx) {
    allFiles.splice(idx, 1);
    const el = document.getElementById(`preview-${idx}`);
    if (el) el.remove();
    if (!allFiles.length) preview.classList.add('hidden');
    syncInputFiles();
}

function syncInputFiles() {
    const dt = new DataTransfer();
    allFiles.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
}

// ── Client-Side Validation ────────────────────────────────────
document.getElementById('createForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const desc  = document.getElementById('description').value.trim();
    if (title.length < 5) { e.preventDefault(); alert('Title must be at least 5 characters.'); return; }
    if (desc.length < 10) { e.preventDefault(); alert('Please provide a more detailed description.'); return; }
});
</script>
