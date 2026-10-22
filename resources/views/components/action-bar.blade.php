{{-- PRD 7.3 multi-selection action bar: appears when >= 1 item is selected,
     replaces the toolbar. Requires page x-data with `selected` array. --}}
<div x-show="selected.length > 0" x-cloak
    class="flex h-14 items-center justify-between gap-3 bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-sm mb-4">
    <div class="flex min-w-0 items-center gap-4">
        <span x-ref="nl" data-template="{{ __('stoman.common.n_selected') }}" class="hidden"></span>
        <span class="shrink-0 text-sm font-medium text-slate-900"
            x-text="$refs.nl.dataset.template.replace(':n', selected.length)"></span>

        @if(auth()->user()?->isAdmin() ?? false)
            <div class="flex items-center gap-1">
                <x-button variant="ghost" size="s" icon="share" @click="$store.modals.share = true">
                    {{ __('stoman.common.share') }}
                </x-button>
                <x-button variant="ghost" size="s" icon="archive"
                    @click="window.stomanApi.archive(selected).then(() => { $store.ui.toast('success', 'Archived'); setTimeout(() => window.location.reload(), 300); })">
                    {{ __('stoman.common.archive') }}
                </x-button>
                <x-button variant="danger" size="s" icon="trash" data-delete-selected
                    @click="window.stomanApi.destroy(selected).then(() => { $store.ui.toast('success', 'Moved to trash'); setTimeout(() => window.location.reload(), 300); })">
                    {{ __('stoman.common.trash') }}
                </x-button>
            </div>
        @endif
    </div>

    <x-button variant="ghost" size="s" @click="selected = []">
        {{ __('stoman.common.clear_selection') }}
    </x-button>
</div>
