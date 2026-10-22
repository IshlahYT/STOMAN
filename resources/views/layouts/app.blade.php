<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('stoman.app.name'))</title>

    {{-- Translated strings used from JS (app.js Alpine.data handlers). --}}
    <script>
        window.STOMAN_I18N = @js([
            'uploadComplete' => __('stoman.upload.done_toast'),
            'uploadFailed' => __('stoman.upload.failed_toast'),
            'folderCreated' => __('stoman.folder.created_toast'),
            'linkCopied' => __('stoman.share.copied_toast'),
            'linkCopyFailed' => __('stoman.share.copy_failed'),
            'saved' => __('stoman.common.saved_toast'),
        ]);

        {{-- Persist JWT to localStorage after web login/register. --}}
        @if(session('jwt_token'))
            localStorage.setItem('stoman_jwt_token', @js(session('jwt_token')));
        @endif
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-primary-700 focus:shadow-overlay">
        {{ __('stoman.common.skip_to_content') }}
    </a>

    <div class="flex h-full">
        <x-sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Offline banner (PRD section 8): yellow, above the topbar --}}
            <div x-data="{ offline: !navigator.onLine }" @offline.window="offline = true"
                @online.window="offline = false" x-show="offline" x-cloak x-transition role="status"
                class="flex h-9 shrink-0 items-center justify-center bg-warning-500 px-4 text-sm font-medium text-slate-900">
                {{ __('stoman.common.offline') }}
            </div>

            <x-topbar :title="$topbarTitle ?? ''" :breadcrumbs="$breadcrumbs ?? []" />

            <div class="flex min-h-0 flex-1">
                <main id="main-content" class="min-w-0 flex-1 overflow-y-auto">
                    <div class="mx-auto w-full max-w-[1280px] p-4 md:p-6 lg:p-8">
                        @yield('content')
                    </div>
                </main>

                <x-detail-panel />
            </div>
        </div>
    </div>

    {{-- Global overlays --}}
    <x-upload-modal />
    <x-folder-modal />
    <x-share-modal />
    <x-preview-modal />
    <x-toast />

    @stack('scripts')
</body>

</html>
