@extends('layouts.app')

@section('title', $folder ?? __('stoman.storage.title'))

{{-- PRD 7.3: file manager - toolbar, folder + file sections, empty state,
     whole area drop zone, multi-selection action bar, DataTables integration. --}}
@section('content')
    @php
        $isEmpty = $folders->isEmpty() && $files->isEmpty();
    @endphp

    <div data-current-folder-id="{{ $currentFolderId ?? '' }}"
        x-data="{
            view: localStorage.getItem('stoman.view') || 'grid',
            selected: [],
            dragging: false,
            hasItems: {{ $isEmpty ? 'false' : 'true' }},
        }"
        x-init="
            if (view === 'list') {
                $nextTick(() => window.initStomanDataTable('#storage-datatable', '{{ route('api.datatables.files') }}', { folder_id: '{{ $currentFolderId ?? '' }}' }));
            }
            $watch('view', (v) => {
                localStorage.setItem('stoman.view', v);
                if (v === 'list') {
                    $nextTick(() => window.initStomanDataTable('#storage-datatable', '{{ route('api.datatables.files') }}', { folder_id: '{{ $currentFolderId ?? '' }}' }));
                }
            })">

        {{-- Toolbar (56px) --}}
        <div x-show="selected.length === 0" class="mb-4 flex h-14 items-center justify-between gap-3">
            <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-1.5">
                @foreach ($breadcrumbs as $i => $crumb)
                    @if($i === count($breadcrumbs) - 1)
                        <span class="truncate text-sm font-medium text-slate-900">{{ $crumb['label'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}"
                            class="truncate text-sm text-slate-600 transition-colors hover:text-slate-900 focus-ring rounded">{{ $crumb['label'] }}</a>
                        <x-icon name="chevron-right" :size="14" class="shrink-0 text-slate-400" />
                    @endif
                @endforeach
                @if(count($breadcrumbs) === 0)
                    <span class="truncate text-sm font-medium text-slate-900">{{ __('stoman.storage.breadcrumb_home') }}</span>
                @endif
            </nav>

            @if(auth()->user()?->isAdmin())
            <div class="flex shrink-0 items-center gap-2">
                <x-sort-menu :disabled="$isEmpty" />
                <x-view-toggle :disabled="$isEmpty" />
                <x-new-menu size="s" />
            </div>
            @endif
        </div>

        <x-action-bar />

        {{-- Content area = drop zone --}}
        <div class="relative flex min-h-[55vh] flex-col rounded-xl"
            @dragover.prevent.stop="dragging = true" @dragleave.stop="dragging = false"
            @drop.prevent.stop="dragging = false; $store.modals.upload = true; $dispatch('stoman-files', { files: $event.dataTransfer.files })">
            <div x-show="dragging" x-cloak
                class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-xl border-2 border-dashed border-primary-600 bg-primary-50">
                <p class="text-base font-semibold text-primary-700">{{ __('stoman.storage.drop_here') }}</p>
            </div>

            @if($isEmpty)
                {{-- Default state: centered empty state with CTAs (admin only) --}}
                <div class="flex flex-1 items-center justify-center">
                    <x-empty-state :title="__('stoman.storage.empty_title')" :description="__('stoman.storage.empty_desc')"
                        icon="folder">
                        @if(auth()->user()?->isAdmin())
                            <x-button icon="upload" data-upload-trigger @click="$store.modals.upload = true">
                                {{ __('stoman.common.upload_file') }}
                            </x-button>
                            <x-button variant="secondary" icon="folder-plus" @click="$store.modals.folder = true">
                                {{ __('stoman.common.create_folder') }}
                            </x-button>
                        @endif
                    </x-empty-state>
                </div>
            @else
                {{-- Folders section --}}
                <section class="mb-8">
                    <h2 class="mb-4 text-xl font-semibold leading-7 text-slate-900">
                        {{ __('stoman.storage.folders_section') }}
                    </h2>
                    @if($folders->isEmpty())
                        <x-empty-state variant="small" icon="folder" :title="__('stoman.storage.no_folders')" />
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($folders as $item)
                                <x-folder-card
                                    :id="data_get($item, 'id')"
                                    :name="data_get($item, 'name', '')"
                                    :meta="data_get($item, 'meta_label', '')"
                                    :url="data_get($item, 'url', '#')"
                                    :starred="(bool) data_get($item, 'is_starred', false)" />
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Files section --}}
                <section>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold leading-7 text-slate-900">
                            {{ __('stoman.storage.files_section') }}
                        </h2>
                        <div class="flex items-center gap-2">
                            <x-view-toggle :disabled="$files->isEmpty()" />
                        </div>
                    </div>

                    @if($files->isEmpty())
                        <x-empty-state variant="small" icon="file" :title="__('stoman.storage.no_files')" />
                    @else
                        {{-- Grid view --}}
                        <div x-show="view === 'grid'"
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($files as $item)
                                <x-file-card :id="data_get($item, 'id')" :name="data_get($item, 'name', '')"
                                    :meta="data_get($item, 'modified_label', '')"
                                    :mime-type="data_get($item, 'mime_type', '')"
                                    :size="data_get($item, 'size_label', '')"
                                    :url="data_get($item, 'url', '#')"
                                    :download-url="data_get($item, 'download_url', '#')" />
                            @endforeach
                        </div>

                        {{-- DataTables List view --}}
                        <div x-show="view === 'list'" x-cloak class="mt-2 bg-white rounded-xl border border-slate-200 p-4 shadow-sm overflow-x-auto">
                            <table id="storage-datatable" class="w-full">
                                <thead>
                                    <tr>
                                        <th class="w-8"></th>
                                        <th>{{ __('stoman.common.sort_name') }}</th>
                                        <th>Owner</th>
                                        <th>{{ __('stoman.common.sort_date') }}</th>
                                        <th>{{ __('stoman.common.sort_size') }}</th>
                                        <th class="w-28 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
