@props([
    'title' => '',
    'breadcrumbs' => [],   // [['label' => ..., 'url' => ...|null], ...]
    'notifications' => false,
])

{{-- PRD 5.2: 64px white topbar - hamburger (mobile) | breadcrumbs | search | icons/avatar. --}}
<header
    class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 md:px-6">
    <button type="button"
        class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-100 focus-ring lg:hidden"
        aria-label="{{ __('stoman.common.open_menu') }}" @click="$store.ui.toggleDrawer()">
        <x-icon name="menu" :size="20" />
    </button>

    <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-1.5">
        @foreach ($breadcrumbs as $i => $crumb)
            @if($i === count($breadcrumbs) - 1)
                <span class="truncate text-sm font-medium text-slate-900">{{ $crumb['label'] }}</span>
            @else
                @if($crumb['url'] ?? null)
                    <a href="{{ $crumb['url'] }}"
                        class="truncate text-sm text-slate-600 transition-colors hover:text-slate-900 focus-ring rounded">{{ $crumb['label'] }}</a>
                @else
                    <span class="truncate text-sm text-slate-600">{{ $crumb['label'] }}</span>
                @endif
                <x-icon name="chevron-right" :size="14" class="shrink-0 text-slate-400" />
            @endif
        @endforeach
        @if(count($breadcrumbs) === 0 && $title !== '')
            <span class="truncate text-sm font-medium text-slate-900">{{ $title }}</span>
        @endif
    </nav>

    {{-- Center: search (max 480px) + platform shortcut hint --}}
    <div class="hidden flex-1 justify-center md:flex">
        <div class="relative w-full max-w-[480px]">
            <x-icon name="search" :size="18"
                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input id="global-search" type="search"
                placeholder="{{ __('stoman.common.search_placeholder') }}"
                aria-label="{{ __('stoman.common.search_placeholder') }}"
                class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-16 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:border-primary-600 focus:bg-white focus:ring-[3px] focus:ring-primary-100 focus:outline-none" />
            <kbd x-data="{
                    get hint() {
                        return navigator.platform.toLowerCase().includes('mac') ? '⌘ K' : 'Ctrl K';
                    },
                }"
                class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-xs text-slate-400"
                x-text="hint"></kbd>
        </div>
    </div>

    {{-- Right: avatar only (notifications and help removed) --}}
    <div class="ml-auto flex items-center gap-1 md:ml-0">
        <div class="ml-1">
            <x-account-menu variant="avatar" />
        </div>
    </div>
</header>
