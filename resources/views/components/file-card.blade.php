@props([
    'id' => null,
    'name',
    'meta' => '',
    'url' => '#',
])

{{-- PRD 6.5 grid mode: card with 120px thumbnail + name + modified.
     Clicking selects the file and opens the detail panel. --}}
<div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card transition-all hover:shadow-card-hover">
    <div class="absolute right-2 top-2 z-10 flex items-center gap-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100">
        <x-dropdown width="w-56" align="right" :label="__('stoman.common.preview')">
            <x-slot:trigger>
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/90 text-slate-400 shadow-card transition-colors hover:bg-slate-100 focus-ring">
                    <x-icon name="dots" :size="16" />
                </span>
            </x-slot:trigger>
            <x-slot:panel>
                @php
                    $canManage = auth()->user()?->isAdmin() ?? false;
                @endphp
                @if($id)
                    <x-menu-item icon="eye" href="{{ route('api.files.preview', $id) }}" target="_blank">{{ __('stoman.common.preview') }}</x-menu-item>
                    <x-menu-item icon="download" href="{{ route('api.files.download', $id) }}" download>{{ __('stoman.common.download') }}</x-menu-item>
                    @if($canManage)
                        <x-menu-item icon="star" @click="window.stomanApi.star('{{ $id }}', true).then(() => { $store.ui.toast('success', 'Starred'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.star') }}</x-menu-item>
                        <x-menu-item icon="pencil" @click="const n = prompt('Enter new name:', '{{ $name }}'); if(n && n !== '{{ $name }}') window.stomanApi.rename('{{ $id }}', n).then(() => window.location.reload());">{{ __('stoman.common.rename') }}</x-menu-item>
                        <x-menu-item icon="share" @click="$store.modals.share = true">{{ __('stoman.common.share') }}</x-menu-item>
                        <x-menu-item icon="archive" @click="window.stomanApi.archive(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Archived'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.archive') }}</x-menu-item>
                        <div class="my-1 h-px bg-slate-200"></div>
                        <x-menu-item icon="trash" danger @click="window.stomanApi.destroy(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Moved to trash'); setTimeout(() => window.location.reload(), 300); })">{{ __('stoman.common.trash') }}</x-menu-item>
                    @endif
                @else
                    @if($canManage)
                        <x-menu-item icon="share" @click="$store.modals.share = true">{{ __('stoman.common.share') }}</x-menu-item>
                    @endif
                @endif
            </x-slot:panel>
        </x-dropdown>
    </div>

    <button type="button" @click="$store.ui.openDetail()"
        class="block w-full text-left focus-ring">
        <span class="flex h-[120px] w-full items-center justify-center bg-slate-50">
            <x-icon name="file" :size="40" class="text-slate-400" />
        </span>
        <span class="block p-3">
            <span class="block truncate text-sm font-semibold text-slate-900">{{ $name }}</span>
            <span class="mt-0.5 block truncate text-xs text-slate-600">{{ $meta }}</span>
        </span>
    </button>
</div>
