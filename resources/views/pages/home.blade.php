@extends('layouts.app')

{{-- Role-aware page title: Admin sees "Home", regular users see "My Folder". --}}
@section('title', auth()->user()?->isAdmin() ? __('stoman.nav.home') : __('stoman.nav.my_folder'))

@section('content')
    @php
        $displayName = auth()->user()?->name ?? __('stoman.account.guest');
        $isAdmin = auth()->user()?->isAdmin() ?? false;
    @endphp

    <div class="space-y-8" x-data="{ view: 'grid' }">
        {{-- Header + quota widget --}}
        <header class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-[28px] font-bold leading-9 text-slate-900">
                    {{ __('stoman.home.welcome', ['name' => $displayName]) }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('stoman.home.subtitle') }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <svg width="24" height="24" viewBox="0 0 24 24" class="-rotate-90" role="progressbar"
                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('stoman.quota.aria') }}">
                    <circle cx="12" cy="12" r="10" fill="none" stroke-width="3" class="stroke-slate-200" />
                    <circle cx="12" cy="12" r="10" fill="none" stroke-width="3" stroke-linecap="round"
                        class="stroke-primary-600" stroke-dasharray="62.83" stroke-dashoffset="62.83" />
                </svg>
                <span class="text-xs text-slate-600">{{ __('stoman.quota.used_percent') }}</span>
            </div>
        </header>

        {{-- Quick action cards — only for Admin --}}
        @if($isAdmin)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <button type="button" data-upload-trigger @click="$store.modals.upload = true"
                class="flex h-[120px] items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 text-left shadow-card transition-all hover:shadow-card-hover focus-ring">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                    <x-icon name="upload" :size="22" />
                </span>
                <span class="min-w-0">
                    <span class="block text-base font-semibold text-slate-900">{{ __('stoman.common.upload_file') }}</span>
                    <span class="mt-0.5 block truncate text-sm text-slate-600">{{ __('stoman.home.upload_file_desc') }}</span>
                </span>
            </button>

            <button type="button" @click="$store.modals.folder = true"
                class="flex h-[120px] items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 text-left shadow-card transition-all hover:shadow-card-hover focus-ring">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                    <x-icon name="folder-plus" :size="22" />
                </span>
                <span class="min-w-0">
                    <span class="block text-base font-semibold text-slate-900">{{ __('stoman.common.create_folder') }}</span>
                    <span class="mt-0.5 block truncate text-sm text-slate-600">{{ __('stoman.home.create_folder_desc') }}</span>
                </span>
            </button>
        </div>
        @endif

        {{-- Recently Opened Files --}}
        <section>
            <div class="mb-2 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-7 text-slate-900">{{ __('stoman.home.recent_title') }}</h2>
            </div>
            @if($files->isEmpty())
                <x-empty-state variant="small" icon="file" :title="__('stoman.home.no_files')">
                    @if($isAdmin)
                        <x-button icon="upload" data-upload-trigger @click="$store.modals.upload = true">
                            {{ __('stoman.common.upload_file') }}
                        </x-button>
                    @endif
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($files as $item)
                        <x-file-card
                            :id="data_get($item, 'id')"
                            :name="data_get($item, 'name', '')"
                            :meta="data_get($item, 'modified_label', '')"
                            :mime-type="data_get($item, 'mime_type', '')"
                            :size="data_get($item, 'size_label', '')"
                            :url="data_get($item, 'url', '#')"
                            :download-url="data_get($item, 'download_url', '#')" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Folders --}}
        @if(!$folders->isEmpty())
        <section>
            <h2 class="mb-2 text-xl font-semibold leading-7 text-slate-900">{{ __('stoman.storage.folders_section') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($folders as $item)
                    <x-folder-card
                        :id="data_get($item, 'id')"
                        :name="data_get($item, 'name', '')"
                        :meta="data_get($item, 'meta_label', '')"
                        :url="data_get($item, 'url', '#')" />
                @endforeach
            </div>
        </section>
        @endif
    </div>
@endsection
