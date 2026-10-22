@props([
    'id',
    'name',
    'owner' => '',
    'modified' => '',
    'size' => '',
    'mimeType' => '',
    'url' => '',
    'downloadUrl' => '',
    'trash' => false,   // Trash page shows Restore + Delete Permanently instead of the menu
])

@php
    $canManage = auth()->user()?->isAdmin() ?? false;
    $previewUrl = ($url && $url !== '#') ? $url : ($id ? route('api.files.preview', $id) : '#');
    $downUrl = $downloadUrl ?: ($id ? route('api.files.download', $id) : '#');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $inferredMime = match($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'video/ogg',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'json' => 'application/json',
        'csv' => 'text/csv',
        default => '',
    };
    $effectiveMime = $mimeType ?: $inferredMime;
    $previewPayload = [
        'id' => $id,
        'name' => $name,
        'mimeType' => $effectiveMime,
        'size' => $size,
        'url' => $previewUrl,
        'downloadUrl' => $downUrl,
    ];
@endphp

{{-- PRD 6.5 list mode: checkbox | icon + name | owner | last modified | size | menu.
     Requires page x-data: `selected` (array, x-model), defines `view`. --}}
<div x-data class="group grid h-12 items-center gap-3 rounded-lg px-3 text-sm transition-colors hover:bg-slate-100"
    :class="$store.ui.detailOpen ? 'grid-cols-[24px_1fr_40px]' : 'grid-cols-[24px_minmax(0,1fr)_90px_130px_80px_40px]'">
    @if($canManage)
        <input type="checkbox" value="{{ $id }}" x-model="selected"
            aria-label="{{ __('stoman.common.select') }} {{ $name }}"
            class="h-4 w-4 rounded border-slate-300 accent-primary-600 transition-opacity focus-ring"
            :class="selected.includes('{{ $id }}') ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100'" />
    @else
        <span class="h-4 w-4"></span>
    @endif

    <button type="button" @click="$store.ui.openDetail()"
        class="flex min-w-0 items-center gap-3 text-left focus-ring rounded">
        <x-icon name="file" :size="20" class="shrink-0 text-slate-400" />
        <span class="truncate font-medium text-slate-900">{{ $name }}</span>
    </button>

    <span class="hidden truncate text-slate-600 lg:block">{{ $owner }}</span>
    <span class="hidden truncate text-slate-600 md:block">{{ $modified }}</span>
    <span class="hidden truncate text-slate-600 sm:block">{{ $size }}</span>

    @if($trash)
        @if($canManage)
            <div class="flex items-center justify-end gap-1">
                <button type="button"
                    @click="window.stomanApi.restore(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Restored'); setTimeout(() => window.location.reload(), 300); })"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-white hover:text-slate-600 focus-ring"
                    aria-label="{{ __('stoman.common.restore') }}">
                    <x-icon name="restore" :size="16" />
                </button>
                <button type="button"
                    @click="if(confirm('Delete permanently?')) window.stomanApi.destroy(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Deleted permanently'); setTimeout(() => window.location.reload(), 300); })"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-danger-600 transition-colors hover:bg-danger-50 focus-ring"
                    aria-label="{{ __('stoman.common.delete_permanently') }}">
                    <x-icon name="trash" :size="16" />
                </button>
            </div>
        @else
            <div></div>
        @endif
    @else
        <div class="flex items-center justify-end">
            <x-dropdown width="w-56" align="right" :label="__('stoman.common.preview')">
                <x-slot:trigger>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-white focus-ring">
                        <x-icon name="dots" :size="16" />
                    </span>
                </x-slot:trigger>
                <x-slot:panel>
                    <x-menu-item icon="eye" x-on:click="$dispatch('open-preview', {{ \Illuminate\Support\Js::from($previewPayload) }})">{{ __('stoman.common.view') }}</x-menu-item>
                    <x-menu-item icon="download" href="{{ $downUrl }}" download>{{ __('stoman.common.download') }}</x-menu-item>
                    @if($canManage)
                        <x-menu-item icon="pencil" @click="const n = prompt('Enter new name:', '{{ $name }}'); if(n && n !== '{{ $name }}') window.stomanApi.rename('{{ $id }}', n).then(() => window.location.reload());">{{ __('stoman.common.rename') }}</x-menu-item>
                        <x-menu-item icon="archive" @click="window.stomanApi.archive(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Archived'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.archive') }}</x-menu-item>
                        <div class="my-1 h-px bg-slate-200"></div>
                        <x-menu-item icon="trash" danger @click="if(confirm('Hapus file ini?')) window.stomanApi.destroy(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Dihapus'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.delete') }}</x-menu-item>
                    @endif
                </x-slot:panel>
            </x-dropdown>
        </div>
    @endif
</div>
