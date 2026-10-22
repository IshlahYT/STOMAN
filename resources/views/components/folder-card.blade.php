@props([
    'id' => null,
    'name',
    'meta' => '',
    'url' => '#',
    'starred' => false,
])

{{-- PRD 6.4: folder card - 168px, 48px icon, name + metadata,
     three-dot menu top-right (visible on hover). Star removed per requirements. --}}
@php
    $canManage = auth()->user()?->isAdmin() ?? false;
@endphp

<div class="group relative flex h-[168px] flex-col items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white p-4 text-center shadow-card transition-all hover:shadow-card-hover">
    <div class="absolute right-2 top-2 flex items-center gap-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100">
        <x-dropdown width="w-56" align="right" :label="__('stoman.common.preview')">
            <x-slot:trigger>
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 focus-ring">
                    <x-icon name="dots" :size="16" />
                </span>
            </x-slot:trigger>
            <x-slot:panel>
                <x-menu-item icon="enter" href="{{ $url }}">{{ __('stoman.common.open') }}</x-menu-item>
                @if($canManage)
                    @if($id)
                        <x-menu-item icon="pencil"
                            @click="const n = prompt('Enter new name:', '{{ $name }}'); if(n && n !== '{{ $name }}') window.stomanApi.rename('{{ $id }}', n).then(() => window.location.reload());">
                            {{ __('stoman.common.rename') }}
                        </x-menu-item>
                        <x-menu-item icon="archive"
                            @click="window.stomanApi.archive(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Archived'); setTimeout(() => window.location.reload(), 300); })">
                            {{ __('stoman.common.archive') }}
                        </x-menu-item>
                        <div class="my-1 h-px bg-slate-200"></div>
                        <x-menu-item icon="trash" danger
                            @click="if(confirm('Hapus folder ini?')) window.stomanApi.destroy(['{{ $id }}']).then(() => { $store.ui.toast('success', 'Dihapus'); setTimeout(() => window.location.reload(), 300); })">
                            {{ __('stoman.common.delete') }}
                        </x-menu-item>
                    @endif
                @endif
            </x-slot:panel>
        </x-dropdown>
    </div>

    <a href="{{ $url }}" class="flex flex-col items-center gap-2 focus-ring rounded-xl">
        <x-icon name="folder" :size="48" class="text-primary-600" />
        <span class="w-full truncate text-sm font-semibold text-slate-900">{{ $name }}</span>
        <span class="w-full truncate text-xs text-slate-600">{{ $meta }}</span>
    </a>
</div>
