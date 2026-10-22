@php
    $currentUser = auth('web')->user() ?? auth('api')->user() ?? \App\Models\User::first();

    $nav = [
        ['label' => __('stoman.nav.home'), 'route' => 'home', 'icon' => 'home'],
        ['label' => __('stoman.nav.storage'), 'route' => 'storage.index', 'icon' => 'folder'],
        ['label' => __('stoman.nav.shared'), 'route' => 'shared.index', 'icon' => 'users'],
        ['label' => __('stoman.nav.recent'), 'route' => 'recent.index', 'icon' => 'clock'],
        ['label' => __('stoman.nav.starred'), 'route' => 'starred.index', 'icon' => 'star'],
        ['label' => __('stoman.nav.archive'), 'route' => 'archive.index', 'icon' => 'archive'],
        ['label' => __('stoman.nav.trash'), 'route' => 'trash.index', 'icon' => 'trash'],
    ];

    if ($currentUser && $currentUser->isAdmin()) {
        $nav[] = ['label' => __('stoman.nav.users'), 'route' => 'users.index', 'icon' => 'users'];
    }

    $usedBytes = $currentUser ? \App\Models\FileItem::where('user_id', $currentUser->id)->sum('size') : 0;
    $maxQuotaBytes = 10 * 1024 * 1024 * 1024; // 10 GB
    $percent = min(100, (int) round(($usedBytes / $maxQuotaBytes) * 100));
    $usedFormatted = $usedBytes < 1024 * 1024 ? round($usedBytes / 1024, 1) . ' KB' : round($usedBytes / (1024 * 1024), 1) . ' MB';
    $usedLabel = $usedFormatted . ' of 10 GB';
    $starredFoldersList = $currentUser ? \App\Models\Folder::where('user_id', $currentUser->id)->where('is_starred', true)->where('is_trashed', false)->limit(5)->get() : collect();
    $archivedFoldersList = $currentUser ? \App\Models\Folder::where('user_id', $currentUser->id)->where('is_archived', true)->where('is_trashed', false)->limit(5)->get() : collect();
@endphp

{{-- Mobile drawer backdrop (768-1023px: sidebar becomes a drawer, PRD section 9). --}}
<div x-cloak x-show="$store.ui.drawerOpen"
    class="fixed inset-0 z-30 bg-slate-900/50 transition-opacity lg:hidden"
    @click="$store.ui.drawerOpen = false"></div>

<aside x-cloak
    x-data="{
        get labels() {
            return !this.$store.ui.sidebarCollapsed || this.$store.ui.drawerOpen;
        },
    }"
    aria-label="{{ __('stoman.app.name') }}"
    class="fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col border-r border-slate-200 bg-white transition-all duration-200 lg:static lg:z-auto"
    :class="[
        $store.ui.drawerOpen ? 'flex' : 'hidden lg:flex',
        $store.ui.sidebarCollapsed && !$store.ui.drawerOpen ? 'lg:w-[72px]' : '',
    ]">

    {{-- 1. Header (64px): logo + collapse --}}
    <div class="flex h-16 shrink-0 items-center justify-between px-4">
        <a href="{{ route('home') }}" class="focus-ring rounded-lg" aria-label="{{ __('stoman.app.name') }}">
            <x-logo :show-text="true" x-show="labels" />
            <span x-show="!labels" x-cloak><x-logo :show-text="false" /></span>
        </a>
        <button type="button" class="hidden h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus-ring lg:flex"
            aria-label="{{ __('stoman.nav.collapse') }}" @click="$store.ui.toggleSidebar()">
            <x-icon name="panel" :size="18" />
        </button>
    </div>

    {{-- 2. Account card --}}
    <div class="mx-4 mt-4">
        <x-account-menu variant="card" />
    </div>

    {{-- 3. "+ New" button for Admin / Read-only badge for User --}}
    <div class="mx-4 mt-4">
        @if($currentUser && $currentUser->isAdmin())
            <x-new-menu size="m" block :trigger-id="'btn-new'" />
        @else
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-medium text-slate-500 shadow-sm"
                :class="!labels ? 'justify-center px-0' : ''"
                title="Read-only permissions: viewing files only">
                <x-icon name="eye" :size="18" class="text-slate-400 shrink-0" />
                <span x-show="labels" class="truncate font-semibold text-slate-700">Read-Only Mode</span>
            </div>
        @endif
    </div>

    {{-- 4. Main navigation --}}
    <nav class="mt-6" aria-label="{{ __('stoman.nav.home') }}">
        <ul class="flex flex-col gap-1 px-3">
        @foreach ($nav as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <li class="group relative list-none">
                <a href="{{ route($item['route']) }}"
                    @if($active) aria-current="page" @endif
                    class="relative flex h-10 items-center rounded-lg transition-colors focus-ring {{ $active ? 'bg-primary-100 text-primary-700' : 'text-slate-600 hover:bg-slate-100' }}"
                    :class="labels ? 'gap-3 px-3' : 'justify-center px-0'">
                    @if($active)
                        <span class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r bg-primary-600"></span>
                    @endif
                    <x-icon :name="$item['icon']" :size="20"
                        class="{{ $active ? 'text-primary-600' : 'text-slate-400 transition-colors group-hover:text-slate-600' }}" />
                    <span x-show="labels" class="truncate text-sm font-medium">{{ $item['label'] }}</span>
                    {{-- Collapsed: tooltip on the right --}}
                    <template x-if="!labels">
                        <span
                            class="pointer-events-none absolute left-full z-50 ml-2 hidden whitespace-nowrap rounded-lg bg-slate-900 px-2 py-1 text-xs font-medium text-white group-hover:block">
                            {{ $item['label'] }}
                        </span>
                    </template>
                </a>
            </li>
        @endforeach
        </ul>
    </nav>

    {{-- 5/6. Collapsible Starred + Archive sections --}}
    <div class="mt-5 flex flex-col gap-4 px-4" x-show="labels">
        <div x-data="{ open: true }">
            <div class="flex items-center justify-between">
                <button type="button" class="rounded px-1 py-1 text-xs font-semibold uppercase tracking-wide text-slate-400 transition-colors hover:text-slate-600 focus-ring"
                    :aria-expanded="open" @click="open = !open">
                    {{ __('stoman.nav.starred_section') }}
                </button>
            </div>
            <div x-show="open" x-cloak x-transition class="mt-1 flex flex-col gap-1">
                @forelse($starredFoldersList as $sf)
                    <a href="{{ route('storage.folder', $sf->id) }}" class="flex items-center gap-2 rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 truncate">
                        <x-icon name="folder" :size="14" class="text-amber-500 shrink-0" />
                        <span class="truncate">{{ $sf->name }}</span>
                    </a>
                @empty
                    <p class="px-1 text-xs text-slate-400">{{ __('stoman.nav.no_starred_folders') }}</p>
                @endforelse
            </div>
        </div>

        <div x-data="{ open: false }">
            <div class="flex items-center justify-between">
                <button type="button" class="rounded px-1 py-1 text-xs font-semibold uppercase tracking-wide text-slate-400 transition-colors hover:text-slate-600 focus-ring"
                    :aria-expanded="open" @click="open = !open">
                    {{ __('stoman.nav.archive_section') }}
                </button>
            </div>
            <div x-show="open" x-cloak x-transition class="mt-1 flex flex-col gap-1">
                @forelse($archivedFoldersList as $af)
                    <a href="{{ route('storage.folder', $af->id) }}" class="flex items-center gap-2 rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 truncate">
                        <x-icon name="archive" :size="14" class="text-slate-400 shrink-0" />
                        <span class="truncate">{{ $af->name }}</span>
                    </a>
                @empty
                    <p class="px-1 text-xs text-slate-400">{{ __('stoman.nav.no_archived_folders') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 7. Storage quota (pinned bottom) + 8. Footer --}}
    <div class="mt-auto px-4 pb-4 pt-5">
        <x-storage-meter class="mb-3" :percent="$percent" :used="$usedLabel" />
        <ul class="flex flex-col gap-1" aria-label="{{ __('stoman.nav.settings') }}">
            <li class="group relative list-none">
                <button type="button"
                    class="flex h-10 w-full items-center rounded-lg px-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 focus-ring"
                    :class="labels ? 'gap-3' : 'justify-center px-0'">
                    <x-icon name="help" :size="20" class="text-slate-400 transition-colors group-hover:text-slate-600" />
                    <span x-show="labels">{{ __('stoman.nav.help') }}</span>
                    <template x-if="!labels">
                        <span class="pointer-events-none absolute left-full z-50 ml-2 hidden whitespace-nowrap rounded-lg bg-slate-900 px-2 py-1 text-xs font-medium text-white group-hover:block">
                            {{ __('stoman.nav.help') }}
                        </span>
                    </template>
                </button>
            </li>
            <li class="group relative list-none">
                <a href="{{ route('settings.index') }}"
                    class="flex h-10 w-full items-center rounded-lg px-3 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 focus-ring"
                    :class="labels ? 'gap-3' : 'justify-center px-0'">
                    <x-icon name="gear" :size="20" class="text-slate-400 transition-colors group-hover:text-slate-600" />
                    <span x-show="labels">{{ __('stoman.nav.settings') }}</span>
                    <template x-if="!labels">
                        <span class="pointer-events-none absolute left-full z-50 ml-2 hidden whitespace-nowrap rounded-lg bg-slate-900 px-2 py-1 text-xs font-medium text-white group-hover:block">
                            {{ __('stoman.nav.settings') }}
                        </span>
                    </template>
                </a>
            </li>
        </ul>
    </div>
</aside>
