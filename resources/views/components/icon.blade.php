@props(['name', 'size' => 20, 'solid' => false])

{{--
    STOMAN icon set - hand-drawn 24x24 stroke SVGs, colored via currentColor.
    Usage: <x-icon name="folder" /> or <x-icon name="star" solid class="text-accent-500" />
--}}
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
    fill="{{ $solid ? 'currentColor' : 'none' }}" stroke="{{ $solid ? 'none' : 'currentColor' }}"
    stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('home')
            <path d="M3 10.8 12 3.5l9 7.3" />
            <path d="M5.5 9.6V20a.5.5 0 0 0 .5.5h3.5v-5h4v5H18a.5.5 0 0 0 .5-.5V9.6" />
        @break

        @case('folder')
            <path d="M3.5 7.5A1.5 1.5 0 0 1 5 6h4l2 2.5h8a1.5 1.5 0 0 1 1.5 1.5v7.5A1.5 1.5 0 0 1 19 19H5a1.5 1.5 0 0 1-1.5-1.5v-10Z" />
        @break

        @case('folder-plus')
            <path d="M3.5 7.5A1.5 1.5 0 0 1 5 6h4l2 2.5h8a1.5 1.5 0 0 1 1.5 1.5v7.5A1.5 1.5 0 0 1 19 19H5a1.5 1.5 0 0 1-1.5-1.5v-10Z" />
            <path d="M12 11.5v5M9.5 14h5" />
        @break

        @case('users')
            <circle cx="9" cy="8.5" r="3" />
            <path d="M3.5 19.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5" />
            <circle cx="17" cy="9.5" r="2.25" />
            <path d="M16 14.9c2.6.2 4.5 2.1 4.5 4.6" />
        @break

        @case('clock')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5V12l3 2" />
        @break

        @case('star')
            <path d="m12 3.8 2.5 5.1 5.6.8-4 4 .9 5.6-5-2.6-5 2.6.9-5.6-4-4 5.6-.8L12 3.8Z" />
        @break

        @case('archive')
            <rect x="3.5" y="4.5" width="17" height="4" rx="1" />
            <path d="M5 8.5V19a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5V8.5" />
            <path d="M10 12.5h4" />
        @break

        @case('trash')
            <path d="M4.5 6.5h15" />
            <path d="M9.5 6.5V5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v1.5" />
            <path d="M6.5 6.5 7.4 19a1.5 1.5 0 0 0 1.5 1.4h6.2a1.5 1.5 0 0 0 1.5-1.4l.9-12.5" />
            <path d="M10.5 10v6.5M13.5 10v6.5" />
        @break

        @case('gear')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 2.5v3M12 18.5v3M21.5 12h-3M5.5 12h-3M18.7 5.3l-2.1 2.1M7.4 16.6l-2.1 2.1M18.7 18.7l-2.1-2.1M7.4 7.4 5.3 5.3" />
        @break

        @case('help')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M9.6 9.4a2.5 2.5 0 0 1 4.9.6c0 1.7-2.5 2.5-2.5 2.5" />
            <path d="M12 16.2h.01" />
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
        @break

        @case('chevron-down')
            <path d="m6 9.5 6 6 6-6" />
        @break

        @case('chevron-right')
            <path d="m9.5 6 6 6-6 6" />
        @break

        @case('chevron-left')
            <path d="m14.5 6-6 6 6 6" />
        @break

        @case('chevron-up-down')
            <path d="m8 10 4-4 4 4M8 14l4 4 4-4" />
        @break

        @case('search')
            <circle cx="11" cy="11" r="6.5" />
            <path d="m20 20-4.2-4.2" />
        @break

        @case('bell')
            <path d="M18 9a6 6 0 1 0-12 0c0 5-2 6.5-2 6.5h16S18 14 18 9Z" />
            <path d="M13.7 19a2 2 0 0 1-3.4 0" />
        @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
        @break

        @case('panel')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
            <path d="M9.5 4.5v15" />
            <path d="m15 10-2 2 2 2" />
        @break

        @case('upload')
            <path d="M12 15.5V4m0 0L7.5 8.5M12 4l4.5 4.5" />
            <path d="M4.5 15v3.5a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5V15" />
        @break

        @case('download')
            <path d="M12 4v11.5m0 0L7.5 11M12 15.5 16.5 11" />
            <path d="M4.5 15v3.5a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5V15" />
        @break

        @case('cloud-upload')
            <path d="M7 17.5a4.5 4.5 0 0 1-.4-9A5.5 5.5 0 0 1 17.3 9.7 3.9 3.9 0 0 1 17 17.5H7Z" />
            <path d="M12 20v-6.5m0 0L9.5 16M12 13.5 14.5 16" />
        @break

        @case('file')
            <path d="M13.5 3.5H7A1.5 1.5 0 0 0 5.5 5v14A1.5 1.5 0 0 0 7 20.5h10a1.5 1.5 0 0 0 1.5-1.5V8.5l-5-5Z" />
            <path d="M13.5 3.5v5h5" />
        @break

        @case('table')
            <rect x="4" y="5" width="16" height="14" rx="1.5" />
            <path d="M4 10h16M10 10v9" />
        @break

        @case('presentation')
            <path d="M4 4.5h16" />
            <rect x="5" y="7" width="14" height="9" rx="1.5" />
            <path d="M12 16v3m-3 0h6" />
        @break

        @case('clipboard')
            <rect x="5" y="5" width="14" height="16" rx="1.5" />
            <path d="M9 5V3.8h6V5" />
            <path d="M9 10.5h6M9 14.5h6" />
        @break

        @case('dots')
            <circle cx="12" cy="5.5" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="12" cy="18.5" r="1.5" fill="currentColor" stroke="none" />
        @break

        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
        @break

        @case('x')
            <path d="M6 6l12 12M18 6 6 18" />
        @break

        @case('grid')
            <rect x="4" y="4" width="7" height="7" rx="1.5" />
            <rect x="13" y="4" width="7" height="7" rx="1.5" />
            <rect x="4" y="13" width="7" height="7" rx="1.5" />
            <rect x="13" y="13" width="7" height="7" rx="1.5" />
        @break

        @case('list')
            <path d="M9 6h11M9 12h11M9 18h11" />
            <circle cx="4.5" cy="6" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="4.5" cy="12" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="4.5" cy="18" r="1.2" fill="currentColor" stroke="none" />
        @break

        @case('sort')
            <path d="M7 4.5v15m0 0-3-3m3 3 3-3" />
            <path d="M17 19.5v-15m0 0-3 3m3-3 3 3" />
        @break

        @case('share')
            <circle cx="18" cy="5.5" r="2.5" />
            <circle cx="6" cy="12" r="2.5" />
            <circle cx="18" cy="18.5" r="2.5" />
            <path d="m8.2 10.8 7.6-4M8.2 13.2l7.6 4" />
        @break

        @case('move')
            <path d="M12 3.5v17M3.5 12h17" />
            <path d="m9 6.5 3-3 3 3M9 17.5l3 3 3-3M6.5 9l-3 3 3 3M17.5 9l3 3-3 3" />
        @break

        @case('eye')
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
            <circle cx="12" cy="12" r="3" />
        @break

        @case('pencil')
            <path d="M4.5 19.5h4l10-10a2.1 2.1 0 0 0-3-3l-10 10v3Z" />
            <path d="m14.5 6.5 3 3" />
        @break

        @case('enter')
            <path d="M14 4.5h4A1.5 1.5 0 0 1 19.5 6v12a1.5 1.5 0 0 1-1.5 1.5h-4" />
            <path d="M4.5 12h10m0 0-3.5-3.5M14.5 12 11 15.5" />
        @break

        @case('mail')
            <rect x="3.5" y="5.5" width="17" height="13" rx="1.5" />
            <path d="m4.5 7 7.5 5.5L19.5 7" />
        @break

        @case('shield')
            <path d="M12 3.5 5 6v5.5c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6l-7-2.5Z" />
            <path d="m9.5 12 1.8 1.8 3.4-3.6" />
        @break

        @case('user')
            <circle cx="12" cy="8.5" r="3.5" />
            <path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6" />
        @break

        @case('alert')
            <path d="M12 4.5 2.9 19.5h18.2L12 4.5Z" />
            <path d="M12 10v4M12 16.6h.01" />
        @break

        @case('info')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 11v5M12 8h.01" />
        @break

        @case('exclamation-circle')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 8v4.5M12 15.6h.01" />
        @break

        @case('server')
            <rect x="4" y="5" width="16" height="6" rx="1.5" />
            <rect x="4" y="13" width="16" height="6" rx="1.5" />
            <path d="M7.5 8h.01M7.5 16h.01" />
        @break

        @case('restore')
            <path d="M4 9.5h9.5a5 5 0 0 1 0 10H8" />
            <path d="m7.5 6-3.5 3.5L7.5 13" />
        @break

        @case('link')
            <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 1 0-5.7-5.7l-1.3 1.3" />
            <path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 1 0 5.7 5.7l1.3-1.3" />
        @break

        @case('image')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
            <circle cx="8.5" cy="9.5" r="1.5" />
            <path d="m20.5 15.5-5-5-6 6-2-2-4 4" />
        @break

        @case('video')
            <rect x="3" y="5.5" width="13" height="13" rx="2" />
            <polygon points="16 9.5 21 6.5 21 17.5 16 14.5" />
        @break

        @case('music')
            <path d="M9 18V5l10-2v13" />
            <circle cx="6" cy="18" r="3" />
            <circle cx="16" cy="16" r="3" />
        @break

        @case('code')
            <polyline points="16 18 22 12 16 6" />
            <polyline points="8 6 2 12 8 18" />
        @break

        @default
            <circle cx="12" cy="12" r="8.5" />
    @endswitch
</svg>
