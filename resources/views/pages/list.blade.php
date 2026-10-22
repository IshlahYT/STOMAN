@extends('layouts.app')

@section('title', $title)

{{-- Shared shell for Shared / Recent / Starred / Archive / Trash (PRD 7.4),
     built on the My Storage framework (PRD 7.3). --}}
@section('content')
    <div x-data="{
        view: localStorage.getItem('stoman.view') || 'grid',
        selected: [],
        dragging: false
    }"
    x-init="
        if (view === 'list') {
            $nextTick(() => window.initStomanDataTable('#list-datatable', '{{ route('api.datatables.files') }}', { type: '{{ $page }}' }));
        }
        $watch('view', (v) => {
            localStorage.setItem('stoman.view', v);
            if (v === 'list') {
                $nextTick(() => window.initStomanDataTable('#list-datatable', '{{ route('api.datatables.files') }}', { type: '{{ $page }}' }));
            }
        })">
        <header class="mb-6 flex items-start justify-between gap-4">
            <h1 class="text-[28px] font-bold leading-9 text-slate-900">{{ $title }}</h1>
        </header>

        {{-- Toolbar (56px): sort + grid/list toggle; trash gets Empty Trash (danger, S) --}}
        <div x-show="selected.length === 0" class="mb-6 flex h-14 items-center justify-between gap-3">
            <div></div>
            <div class="flex items-center gap-2">
                <x-sort-menu :disabled="$items->isEmpty()" />
                <x-view-toggle :disabled="$items->isEmpty()" />
                @if($showEmptyTrash)
                    <x-button variant="danger" size="s" icon="trash" :disabled="$items->isEmpty()"
                        @click="if(confirm('Are you sure you want to empty the trash? All items will be permanently deleted.')) window.stomanApi.emptyTrash().then(() => { $store.ui.toast('success', 'Trash emptied'); setTimeout(() => window.location.reload(), 300); })">
                        {{ __('stoman.common.empty_trash') }}
                    </x-button>
                @endif
            </div>
        </div>

        <x-action-bar />

        {{-- Content area: entire area is a drop zone (PRD 7.3) --}}
        <div class="relative flex min-h-[45vh] flex-col justify-center rounded-xl"
            @dragover.prevent.stop="dragging = true" @dragleave.stop="dragging = false"
            @drop.prevent.stop="dragging = false; $store.modals.upload = true; $dispatch('stoman-files', { files: $event.dataTransfer.files })">
            <div x-show="dragging" x-cloak
                class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-xl border-2 border-dashed border-primary-600 bg-primary-50">
                <p class="text-base font-semibold text-primary-700">{{ __('stoman.storage.drop_here') }}</p>
            </div>

            @if ($items->isEmpty())
                <x-empty-state :title="$emptyTitle" :hint="$emptyHint"
                    :icon="$page === 'starred' ? 'star' : ($page === 'archive' ? 'archive' : ($page === 'trash' ? 'trash' : ($page === 'recent' ? 'clock' : 'users')))">
                    @if($emptyAction === 'upload')
                        <x-button icon="upload" data-upload-trigger @click="$store.modals.upload = true">
                            {{ __('stoman.common.upload_file') }}
                        </x-button>
                    @endif
                </x-empty-state>
            @else
                {{-- Grid view --}}
                <div x-show="view === 'grid'"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @forelse ($items as $item)
                        @if($item instanceof \App\Models\Folder)
                            <x-folder-card
                                :id="data_get($item, 'id')"
                                :name="data_get($item, 'name', '')"
                                :meta="data_get($item, 'meta_label', '')"
                                :url="data_get($item, 'url', '#')"
                                :starred="(bool) data_get($item, 'is_starred', false)" />
                        @else
                            <x-file-card
                                :id="data_get($item, 'id')"
                                :name="data_get($item, 'name', '')"
                                :meta="data_get($item, 'modified_label', '')"
                                :mime-type="data_get($item, 'mime_type', '')"
                                :size="data_get($item, 'size_label', '')"
                                :url="data_get($item, 'url', '#')"
                                :download-url="data_get($item, 'download_url', '#')" />
                        @endif
                    @empty
                    @endforelse
                </div>

                {{-- DataTables list view --}}
                <div x-show="view === 'list'" x-cloak class="mt-2 bg-white rounded-xl border border-slate-200 p-4 shadow-sm overflow-x-auto">
                    <table id="list-datatable" class="w-full">
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
        </div>
    </div>
@endsection
