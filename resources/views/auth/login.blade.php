@extends('layouts.guest')

@section('title', __('stoman.auth.sign_in'))

@section('content')
    {{-- Submit via a normal browser POST to the web /login route: only the web
         middleware group starts the session and sets the session cookie, which
         the auth-protected pages depend on. The JSON API (/api/auth/login) is
         stateless and cannot establish a login for the browser. --}}
    <div x-data="{
        show: false,
        loading: false,
        email: '{{ old('email', 'test@example.com') }}',
        password: 'password',
    }">
        <h1 class="text-[28px] font-bold leading-9 text-slate-900">{{ __('stoman.auth.sign_in') }}</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-danger-50 p-3 text-sm font-medium text-danger-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" @submit="loading = true">
            @csrf

            <x-input label="{{ __('stoman.auth.email') }}" name="email" id="login-email" type="email"
                required autocomplete="email" autofocus x-model="email" />

            <div>
                <label for="login-password"
                    class="mb-1.5 block text-sm font-medium text-slate-900">{{ __('stoman.auth.password') }}</label>
                <div class="relative">
                    <input id="login-password" name="password" :type="show ? 'text' : 'password'" required
                        autocomplete="current-password" x-model="password"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:border-primary-600 focus:ring-[3px] focus:ring-primary-100 focus:outline-none" />
                    <button type="button"
                        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus-ring"
                        @click="show = !show"
                        x-bind:aria-label="show ? window.STOMAN_I18N.hidePassword : window.STOMAN_I18N.showPassword">
                        <x-icon name="eye" :size="18" />
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-900">
                    <input type="checkbox" name="remember" x-model="remember"
                        class="h-4 w-4 rounded border-slate-300 accent-primary-600 focus-ring" />
                    {{ __('stoman.auth.remember') }}
                </label>
                <button type="button"
                    class="rounded text-sm font-medium text-primary-600 transition-colors hover:text-primary-700 focus-ring">
                    {{ __('stoman.auth.forgot') }}
                </button>
            </div>

            <x-button type="submit" class="w-full">
                <span x-show="!loading">{{ __('stoman.auth.sign_in') }}</span>
                <span x-show="loading" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Signing in…
                </span>
            </x-button>

            <p class="text-center text-sm text-slate-600">
                {{ __('stoman.auth.no_account_prefix') }}
                <a href="{{ route('register') }}"
                    class="font-medium text-primary-600 transition-colors hover:text-primary-700">{{ __('stoman.auth.sign_up') }}</a>
            </p>
        </form>
    </div>
@endsection
