@props([
    'id' => null,
    'name',
    'meta' => '',
    'url' => '#',
    'mimeType' => '',
    'downloadUrl' => '',
    'size' => '',
])

@php
    $canManage = auth()->user()?->isAdmin() ?? false;
    $previewUrl = ($url && $url !== '#') ? $url : ($id ? route('api.files.preview', $id) : '#');
    $downloadUrl = $downloadUrl ?: ($id ? route('api.files.download', $id) : '#');
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
    $isImage = str_starts_with($effectiveMime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    $previewPayload = [
        'id' => $id,
        'name' => $name,
        'mimeType' => $effectiveMime,
        'size' => $size,
        'url' => $previewUrl,
        'downloadUrl' => $downloadUrl,
    ];
@endphp

{{-- PRD 6.5 grid mode: card with 120px thumbnail + name + modified.
     Clicking selects the file and opens the detail panel. --}}
<div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card transition-all hover:shadow-card-hover">
    <div class="absolute right-2 top-2 z-10 flex items-center gap-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100">
        <x-dropdown width="w-56" align="right" :label="__('stoman.common.view')">
            <x-slot:trigger>
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/90 text-slate-400 shadow-card transition-colors hover:bg-slate-100 focus-ring cursor-pointer">
                    <x-icon name="dots" :size="16" />
                </span>
            </x-slot:trigger>
            <x-slot:panel>
                @if($id)
                    <x-menu-item icon="eye" x-on:click="$dispatch('open-preview', {{ \Illuminate\Support\Js::from($previewPayload) }})">{{ __('stoman.common.view') }}</x-menu-item>
                    <x-menu-item icon="download" href="{{ $downloadUrl }}" download>{{ __('stoman.common.download') }}</x-menu-item>
                    @if($canManage)
                        <x-menu-item icon="pencil" @click="const n = prompt('Enter new name:', '{{ $name }}'); if(n && n !== '{{ $name }}') window.stomanApi.rename('{{ $id }}', n).then(() => window.location.reload());">{{ __('stoman.common.rename') }}</x-menu-item>
                        <x-menu-item icon="archive" @click="window.stomanApi.archive(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Archived'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.archive') }}</x-menu-item>
                        <div class="my-1 h-px bg-slate-200"></div>
                        <x-menu-item icon="trash" danger @click="if(confirm('Hapus file ini?')) window.stomanApi.destroy(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Dihapus'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.delete') }}</x-menu-item>
                    @endif
                @endif
            </x-slot:panel>
        </x-dropdown>
    </div>

    <button type="button" x-on:click="$store.ui.openDetail()"
        @if($id) x-on:dblclick.prevent="$dispatch('open-preview', {{ \Illuminate\Support\Js::from($previewPayload) }})" @endif
        class="block w-full text-left focus-ring cursor-pointer">
        <span class="flex h-[120px] w-full items-center justify-center bg-slate-50 overflow-hidden">
            @if($id && $isImage)
                <img src="{{ $previewUrl }}" alt="{{ $name }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';" />
                <span class="hidden h-full w-full items-center justify-center">
                    <x-icon name="image" :size="40" class="text-slate-400" />
                </span>
            @elseif(str_starts_with($effectiveMime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov']))
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-500">
                    <x-icon name="video" :size="28" />
                </span>
            @elseif(str_starts_with($effectiveMime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg']))
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-500">
                    <x-icon name="music" :size="28" />
                </span>
            @elseif($effectiveMime === 'application/pdf' || $ext === 'pdf')
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                    <x-icon name="file" :size="28" />
                </span>
            @else
                <x-icon name="file" :size="40" class="text-slate-400" />
            @endif
        </span>
        <span class="block p-3">
            <span class="block truncate text-sm font-semibold text-slate-900">{{ $name }}</span>
            <span class="mt-0.5 block truncate text-xs text-slate-600">{{ $meta }}</span>
        </span>
    </button>
</div>
