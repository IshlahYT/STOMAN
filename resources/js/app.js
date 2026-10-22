import Alpine from 'alpinejs';
import Focus from '@alpinejs/focus';
import $ from 'jquery';
import DataTable from 'datatables.net-dt';
import { api } from './api.js';

window.$ = window.jQuery = $;
window.DataTable = DataTable;
window.Alpine = Alpine;
Alpine.plugin(Focus);
window.stomanApi = api;

/* Global UI state (sidebar, drawer, detail panel, toasts). */
Alpine.store('ui', {
    sidebarCollapsed: localStorage.getItem('stoman.sidebar.collapsed') === '1',
    drawerOpen: false,
    detailOpen: false,
    toasts: [],
    seq: 0,

    toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('stoman.sidebar.collapsed', this.sidebarCollapsed ? '1' : '0');
    },
    toggleDrawer() {
        this.drawerOpen = !this.drawerOpen;
    },
    openDetail() {
        this.detailOpen = true;
    },
    closeDetail() {
        this.detailOpen = false;
    },
    toast(type, message) {
        const id = ++this.seq;
        this.toasts.push({ id, type, message });
        setTimeout(() => {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        }, 4000);
    },
});

/* Global modal switches so any trigger (sidebar menu, toolbar, cards) can open them. */
Alpine.store('modals', {
    upload: false,
    folder: false,
    share: false,
    preview: false,
});

/* Upload modal logic */
Alpine.data('uploadModal', () => ({
    files: [],
    uploading: false,
    seq: 0,

    addFiles(list) {
        for (const file of Array.from(list)) {
            this.files.push({
                id: ++this.seq,
                file,
                name: file.name,
                size: file.size,
                progress: 0,
                status: 'pending', // pending | uploading | done | failed
            });
        }
    },
    remove(id) {
        if (this.uploading) return;
        this.files = this.files.filter((f) => f.id !== id);
    },
    formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    },
    async start() {
        if (this.uploading || this.files.length === 0) return;
        this.uploading = true;
        let failed = 0;
        const currentFolderId = document.querySelector('[data-current-folder-id]')?.dataset?.currentFolderId || null;

        for (const item of this.files) {
            if (item.status === 'done') continue;
            item.status = 'uploading';
            item.progress = 0;
            try {
                await api.upload(item.file, {
                    folderId: currentFolderId,
                    onProgress: (p) => (item.progress = p),
                });
                item.status = 'done';
            } catch (e) {
                item.status = 'failed';
                failed++;
            }
        }
        this.uploading = false;
        Alpine.store('ui').toast(
            failed ? 'danger' : 'success',
            failed ? (window.STOMAN_I18N?.uploadFailed || 'Upload failed') : (window.STOMAN_I18N?.uploadComplete || 'Upload completed'),
        );
        if (failed === 0) {
            setTimeout(() => window.location.reload(), 600);
        }
    },
    retry(id) {
        const item = this.files.find((f) => f.id === id);
        if (item) {
            item.status = 'pending';
            item.progress = 0;
            this.start();
        }
    },
    reset() {
        if (this.uploading) return false;
        this.files = [];
        return true;
    },
}));

/* Create folder modal logic */
Alpine.data('folderModal', () => ({
    name: '',
    color: 'primary',
    touched: false,
    colors: ['primary', 'sky', 'violet', 'rose', 'amber', 'slate'],

    get error() {
        if (!this.touched) return null;
        const name = this.name.trim();
        if (!name) return 'required';
        if (name.length > 100) return 'too_long';
        if (/[\/\\:*?"<>|]/.test(name)) return 'invalid_chars';
        return null;
    },
    async submit() {
        this.touched = true;
        if (this.error || !this.name.trim()) return;
        const currentFolderId = document.querySelector('[data-current-folder-id]')?.dataset?.currentFolderId || null;
        try {
            await api.createFolder({ name: this.name.trim(), color: this.color, parent_id: currentFolderId });
            Alpine.store('modals').folder = false;
            Alpine.store('ui').toast('success', window.STOMAN_I18N?.folderCreated || 'Folder created');
            this.name = '';
            this.touched = false;
            setTimeout(() => window.location.reload(), 400);
        } catch (e) {
            Alpine.store('ui').toast('danger', e.message || 'Failed to create folder');
        }
    },
}));

/* Share / invite modal logic */
Alpine.data('shareModal', () => ({
    email: '',
    role: 'viewer',
    linkEnabled: false,
    members: [],

    addChip() {
        const value = this.email.trim().replace(/,$/, '');
        if (!value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return;
        if (!this.members.includes(value)) this.members.push(value);
        this.email = '';
    },
    removeChip(value) {
        this.members = this.members.filter((m) => m !== value);
    },
    async copyLink() {
        try {
            await navigator.clipboard.writeText(window.location.href);
            Alpine.store('ui').toast('success', window.STOMAN_I18N?.linkCopied || 'Link copied to clipboard');
        } catch (e) {
            Alpine.store('ui').toast('danger', window.STOMAN_I18N?.linkCopyFailed || 'Failed to copy link');
        }
    },
    async done() {
        if (this.email.trim()) {
            this.addChip();
        }
        if (this.members.length) {
            const currentFolderId = document.querySelector('[data-current-folder-id]')?.dataset?.currentFolderId || 'self';
            await api.share(currentFolderId, { members: this.members, role: this.role, link: this.linkEnabled });
            Alpine.store('ui').toast('success', 'Shares updated');
        }
        Alpine.store('modals').share = false;
    },
}));

/* File preview modal logic */
Alpine.data('previewModal', () => ({
    file: {
        id: null,
        name: '',
        mimeType: '',
        size: '',
        url: '',
        downloadUrl: '',
    },
    loading: false,
    textContent: null,
    imgError: false,

    get extension() {
        if (!this.file?.name) return '';
        const parts = this.file.name.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    },

    get category() {
        const mime = (this.file?.mimeType || '').toLowerCase();
        const ext = this.extension;

        if (
            mime.startsWith('image/') ||
            ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'].includes(ext)
        ) {
            return 'image';
        }
        if (
            mime.startsWith('video/') ||
            ['mp4', 'webm', 'ogg', 'mov', 'm4v', 'mkv'].includes(ext)
        ) {
            return 'video';
        }
        if (
            mime.startsWith('audio/') ||
            ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'].includes(ext)
        ) {
            return 'audio';
        }
        if (mime === 'application/pdf' || ext === 'pdf') {
            return 'pdf';
        }
        if (
            mime.startsWith('text/') ||
            ['application/json', 'application/javascript', 'application/xml'].includes(mime) ||
            ['txt', 'json', 'js', 'css', 'html', 'md', 'csv', 'xml', 'log', 'sh', 'php', 'py', 'sql', 'yaml', 'yml'].includes(ext)
        ) {
            return 'text';
        }
        return 'other';
    },

    open(data) {
        this.file = {
            id: data.id || null,
            name: data.name || '',
            mimeType: data.mimeType || '',
            size: data.size || '',
            url: data.url || (data.id ? `/api/files/${data.id}/preview` : ''),
            downloadUrl: data.downloadUrl || (data.id ? `/api/files/${data.id}/download` : ''),
        };
        this.imgError = false;
        this.textContent = null;
        Alpine.store('modals').preview = true;

        if (this.category === 'text' && this.file.url) {
            this.loading = true;
            fetch(this.file.url)
                .then((res) => {
                    if (!res.ok) throw new Error('Failed to load text');
                    return res.text();
                })
                .then((text) => {
                    this.textContent = text;
                    this.loading = false;
                })
                .catch(() => {
                    this.textContent = 'Could not load text preview.';
                    this.loading = false;
                });
        }
    },

    close() {
        Alpine.store('modals').preview = false;
        const container = document.getElementById('preview-modal-container');
        if (container) {
            const media = container.querySelectorAll('video, audio');
            media.forEach((el) => {
                try {
                    el.pause();
                } catch (_) {}
            });
        }
    },
}));

/* DataTables Integration Helper */
window.initStomanDataTable = function (selector, ajaxUrl, extraParams = {}) {
    const el = document.querySelector(selector);
    if (!el) return;

    // If already initialized with same params, skip
    const paramsKey = JSON.stringify(extraParams);
    if (el.dataset.dtInitialized && el.dataset.dtParams === paramsKey) return;

    // Destroy existing instance if params changed
    if (el.dataset.dtInitialized) {
        try {
            $(selector).DataTable().destroy();
        } catch (_) {}
    }

    el.dataset.dtInitialized = 'true';
    el.dataset.dtParams = paramsKey;

    const token = api.getJwtToken();
    const headers = {};
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const dt = new DataTable(selector, {
        serverSide: true,
        processing: true,
        ajax: {
            url: ajaxUrl,
            headers: headers,
            data: function (d) {
                return Object.assign(d, extraParams);
            },
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false, width: '32px' },
            { data: 'name', name: 'name' },
            { data: 'owner', name: 'user.name', orderable: false },
            { data: 'modified', name: 'updated_at' },
            { data: 'formatted_size', name: 'size' },
            { data: 'actions', orderable: false, searchable: false, width: '130px' },
        ],
        order: [[3, 'desc']],
        pageLength: 10,
        dom: 't<"flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 px-1"<"text-sm text-slate-500"i><"dt-length-box flex items-center gap-2"l><"dt-pagination"p>>',
        language: {
            search: '',
            searchPlaceholder: 'Search files…',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ items',
            infoEmpty: 'No items found',
            emptyTable: 'No files to display',
            paginate: {
                previous: 'Prev',
                next: 'Next',
            },
        },
    });

    $(selector).on('click', '.btn-dt-trash', function () {
        const id = $(this).data('id');
        if (!confirm('Hapus file ini?')) return;
        api.destroy([id]).then(() => {
            Alpine.store('ui').toast('success', 'Dihapus');
            dt.ajax.reload(null, false);
        });
    });

    $(selector).on('click', '.btn-dt-restore', function () {
        const id = $(this).data('id');
        api.restore([id]).then(() => {
            Alpine.store('ui').toast('success', 'Restored');
            dt.ajax.reload(null, false);
        });
    });

    $(selector).on('click', '.btn-dt-delete-permanently', function () {
        const id = $(this).data('id');
        if (confirm('Permanently delete this file?')) {
            api.destroy([id]).then(() => {
                Alpine.store('ui').toast('success', 'Deleted permanently');
                dt.ajax.reload(null, false);
            });
        }
    });

    $(selector).on('click', '.btn-dt-preview', function (e) {
        e.preventDefault();
        const data = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            mimeType: $(this).data('mime'),
            size: $(this).data('size'),
            url: $(this).data('url'),
            downloadUrl: $(this).data('download'),
        };
        window.dispatchEvent(new CustomEvent('open-preview', { detail: data }));
    });

    $(selector).on('change', '.dt-checkbox', function () {
        const alpineEl = el.closest('[x-data]');
        if (alpineEl && window.Alpine) {
            const data = window.Alpine.$data(alpineEl);
            if (data && Array.isArray(data.selected)) {
                const val = $(this).val();
                if ($(this).is(':checked')) {
                    if (!data.selected.includes(val)) data.selected.push(val);
                } else {
                    data.selected = data.selected.filter((id) => id != val);
                }
            }
        }
    });

    dt.on('draw', function () {
        const alpineEl = el.closest('[x-data]');
        if (alpineEl && window.Alpine) {
            const data = window.Alpine.$data(alpineEl);
            if (data && Array.isArray(data.selected)) {
                $(selector).find('.dt-checkbox').each(function () {
                    $(this).prop('checked', data.selected.includes($(this).val()));
                });
            }
        }
    });

    return dt;
};

Alpine.start();

/* Global keyboard shortcuts */
document.addEventListener('keydown', (e) => {
    const el = document.activeElement;
    const typing =
        el &&
        (el.tagName === 'INPUT' ||
            el.tagName === 'TEXTAREA' ||
            el.tagName === 'SELECT' ||
            el.isContentEditable);

    // Ctrl/Cmd + K -> global search
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        document.getElementById('global-search')?.focus();
        return;
    }

    if (e.key === 'Escape') {
        if (window.Alpine?.store('ui')?.detailOpen) {
            window.Alpine.store('ui').closeDetail();
        }
        return;
    }

    if (typing || e.ctrlKey || e.metaKey || e.altKey) return;

    if (e.key.toLowerCase() === 'n') {
        e.preventDefault();
        document.getElementById('btn-new')?.click();
    } else if (e.key.toLowerCase() === 'u') {
        e.preventDefault();
        document.querySelector('[data-upload-trigger]')?.click();
    } else if (e.key === 'Delete') {
        document.querySelector('[data-delete-selected]')?.click();
    }
});
