@props(['variant' => 'card', 'user' => null]) {{-- card (sidebar) | avatar (topbar) --}}

@php
    // Resolve the authenticated user (passed in by the sidebar/topbar, or via
    // the guards). All app pages require authentication, so the card always
    // shows the logged-in user's real name and email — never the static
    // 'Guest' / 'Not signed in' translation strings.
    $user = $user ?? auth('web')->user() ?? auth('api')->user();
    $name = $user?->name;
    $email = $user?->email;
    $initials = strtoupper(mb_substr((string) $name, 0, 1));
@endphp

<x-dropdown width="w-56" align="right" class="{{ $variant === 'card' ? 'w-full' : '' }}"
    label="{{ $name }}">
    <x-slot:trigger>
        @if($variant === 'card')
            <div class="flex w-full items-center gap-3 rounded-lg p-2 text-left transition-colors hover:bg-slate-50">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
                    {{ $initials }}
                </span>
                <span class="min-w-0 flex-1" x-show="!$store.ui.sidebarCollapsed || $store.ui.drawerOpen">
                    <span class="flex items-center gap-1.5 truncate">
                        <span class="truncate text-sm font-semibold text-slate-900">{{ $name }}</span>
                        @if($user)
                            <span class="inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $user->isAdmin() ? 'Admin' : 'User' }}
                            </span>
                        @endif
                    </span>
                    <span class="block truncate text-xs text-slate-600">{{ $email }}</span>
                </span>
                <x-icon x-show="!$store.ui.sidebarCollapsed || $store.ui.drawerOpen" name="chevron-up-down" :size="16"
                    class="shrink-0 text-slate-400" />
            </div>
        @else
            <span
                class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 focus-ring">
                {{ $initials }}
            </span>
        @endif
    </x-slot:trigger>

    <x-slot:panel>
        <div class="px-3 py-2" x-show="{{ $variant === 'card' ? '$store.ui.sidebarCollapsed && !$store.ui.drawerOpen' : 'false' }}">
            <div class="flex items-center gap-1.5 truncate">
                <span class="truncate text-sm font-semibold text-slate-900">{{ $name }}</span>
                @if($user)
                    <span class="inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $user->isAdmin() ? 'Admin' : 'User' }}
                    </span>
                @endif
            </div>
            <p class="truncate text-xs text-slate-600">{{ $email }}</p>
        </div>
        {{-- Admin-only navigation entries — regular users strictly see only
             the Home (Dashboard) page, so no other links are offered. --}}
        @if($user && $user->isAdmin())
            <x-menu-item icon="users" href="{{ route('users.index') }}">{{ __('stoman.nav.users') }}</x-menu-item>
            <x-menu-item icon="user" href="{{ route('settings.index') }}">{{ __('stoman.account.profile') }}</x-menu-item>
            <x-menu-item icon="gear" href="{{ route('settings.index') }}">{{ __('stoman.account.settings') }}</x-menu-item>
            <div class="my-1 h-px bg-slate-200"></div>
        @endif
        <x-menu-item icon="enter" @click="window.stomanApi.logout()">{{ __('stoman.account.sign_out') }}</x-menu-item>
    </x-slot:panel>
</x-dropdown>
