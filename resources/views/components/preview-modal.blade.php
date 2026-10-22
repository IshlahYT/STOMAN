<div x-data="previewModal"
    id="preview-modal-container"
    x-on:open-preview.window="open($event.detail)"
    x-on:keydown.escape.window="if ($store.modals.preview) close()">

    <div x-show="$store.modals.preview" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 md:p-6"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        {{-- Backdrop overlay --}}
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" x-on:click="close()"></div>

        {{-- Dialog --}}
        <div role="dialog" aria-modal="true" aria-labelledby="preview-modal-title"
            x-trap.noscroll.inert="$store.modals.preview"
            class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-overlay"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2">

            {{-- Top Bar / Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <template x-if="category === 'image'">
                            <x-icon name="image" :size="20" class="text-primary-600" />
                        </template>
                        <template x-if="category === 'video'">
                            <x-icon name="video" :size="20" class="text-rose-600" />
                        </template>
                        <template x-if="category === 'audio'">
                            <x-icon name="music" :size="20" class="text-violet-600" />
                        </template>
                        <template x-if="category === 'pdf'">
                            <x-icon name="file" :size="20" class="text-amber-600" />
                        </template>
                        <template x-if="category === 'text'">
                            <x-icon name="code" :size="20" class="text-sky-600" />
                        </template>
                        <template x-if="category === 'other'">
                            <x-icon name="file" :size="20" class="text-slate-500" />
                        </template>
                    </span>

                    <div class="min-w-0">
                        <h3 id="preview-modal-title" class="truncate text-sm sm:text-base font-semibold text-slate-900" x-text="file.name">
                            Preview
                        </h3>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span x-show="file.size" x-text="file.size" class="text-xs text-slate-500"></span>
                            <span x-show="extension" x-text="extension.toUpperCase()"
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-100 text-slate-600"></span>
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex shrink-0 items-center gap-2">
                    <a :href="file.downloadUrl" download
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 focus-ring cursor-pointer"
                        title="{{ __('stoman.common.download') }}">
                        <x-icon name="download" :size="15" />
                        <span class="hidden sm:inline">{{ __('stoman.common.download') }}</span>
                    </a>

                    <a :href="file.url" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-900 focus-ring cursor-pointer"
                        title="{{ __('stoman.common.open_in_new_tab') }}">
                        <x-icon name="link" :size="15" />
                        <span class="hidden md:inline">{{ __('stoman.common.open_in_new_tab') }}</span>
                    </a>

                    <button type="button" x-on:click="close()"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus-ring cursor-pointer"
                        aria-label="{{ __('stoman.common.close') }}">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>
            </div>

            {{-- Body: Dynamic Preview Content --}}
            <div class="flex min-h-[300px] flex-1 items-center justify-center overflow-auto bg-slate-50/60 p-4 sm:p-6">

                {{-- 1. Photo / Image preview --}}
                <template x-if="category === 'image'">
                    <div class="flex w-full items-center justify-center">
                        <div x-show="!imgError" class="relative max-h-[72vh] max-w-full overflow-hidden rounded-xl bg-slate-950/5 p-2 shadow-sm">
                            <img :src="file.url" :alt="file.name" x-on:error="imgError = true"
                                class="max-h-[68vh] max-w-full rounded-lg object-contain mx-auto transition-transform" />
                        </div>
                        <div x-show="imgError" x-cloak class="flex flex-col items-center justify-center p-8 text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <x-icon name="image" :size="32" />
                            </div>
                            <p class="mt-3 text-sm font-medium text-slate-700">Unable to load image</p>
                            <a :href="file.downloadUrl" download class="mt-3 text-xs font-semibold text-primary-600 hover:underline">Download file instead</a>
                        </div>
                    </div>
                </template>

                {{-- 2. Video preview --}}
                <template x-if="category === 'video'">
                    <div class="w-full flex items-center justify-center">
                        <video controls autoplay playsinline
                            class="max-h-[72vh] w-full rounded-xl bg-black shadow-md"
                            :src="file.url">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </template>

                {{-- 3. Audio preview --}}
                <template x-if="category === 'audio'">
                    <div class="flex w-full max-w-md flex-col items-center rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                        <div class="relative flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-tr from-violet-600 to-primary-500 text-white shadow-lg shadow-violet-500/25">
                            <x-icon name="music" :size="40" />
                        </div>
                        <h4 class="mt-4 truncate text-center text-base font-semibold text-slate-900" x-text="file.name"></h4>
                        <p class="mt-1 text-xs text-slate-500" x-text="file.size"></p>
                        <audio controls autoplay class="mt-6 w-full" :src="file.url">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                </template>

                {{-- 4. PDF preview --}}
                <template x-if="category === 'pdf'">
                    <div class="h-[72vh] w-full rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm">
                        <iframe :src="file.url" class="h-full w-full" title="PDF preview"></iframe>
                    </div>
                </template>

                {{-- 5. Text / Code preview --}}
                <template x-if="category === 'text'">
                    <div class="h-[72vh] w-full flex flex-col rounded-xl overflow-hidden border border-slate-200 bg-slate-900 shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-800 bg-slate-950 px-4 py-2">
                            <span class="text-xs font-mono text-slate-400" x-text="file.name"></span>
                            <button type="button"
                                x-on:click="if (textContent) { navigator.clipboard.writeText(textContent); $store.ui.toast('success', 'Copied to clipboard'); }"
                                class="rounded px-2 py-1 text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors focus-ring cursor-pointer">
                                Copy content
                            </button>
                        </div>
                        <div class="flex-1 overflow-auto p-4 font-mono text-xs text-slate-200 leading-relaxed">
                            <div x-show="loading" class="flex h-full items-center justify-center text-slate-400">
                                <x-icon name="spinner" :size="24" class="animate-spin text-primary-500" />
                                <span class="ml-2">Loading content...</span>
                            </div>
                            <pre x-show="!loading" x-text="textContent" class="whitespace-pre-wrap font-mono"></pre>
                        </div>
                    </div>
                </template>

                {{-- 6. Other / Fallback preview --}}
                <template x-if="category === 'other'">
                    <div class="flex flex-col items-center justify-center p-8 text-center max-w-md">
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 shadow-sm">
                            <x-icon name="file" :size="40" />
                        </div>
                        <h4 class="mt-4 text-base font-semibold text-slate-900" x-text="file.name"></h4>
                        <p class="mt-1 text-xs text-slate-500" x-text="file.size"></p>
                        <p class="mt-3 text-sm text-slate-600">
                            {{ __('stoman.common.no_preview') }}
                        </p>
                        <div class="mt-6 flex items-center gap-3">
                            <a :href="file.downloadUrl" download
                                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus-ring cursor-pointer">
                                <x-icon name="download" :size="18" />
                                <span>{{ __('stoman.common.download') }}</span>
                            </a>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </div>
</div>
