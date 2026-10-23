@extends('layouts.guest')

@section('title', __('stoman.auth.sign_up'))

@section('content')
    {{-- Submit via a normal browser POST to the web /register route so the
         session is actually established (the JSON API is stateless). --}}
    <div x-data="{
        loading: false,
    }">
        <h1 class="text-[28px] font-bold leading-9 text-slate-900">{{ __('stoman.auth.sign_up') }}</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-danger-50 p-3 text-sm font-medium text-danger-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" @submit="loading = true">
            @csrf

            <x-input label="{{ __('stoman.auth.name') }}" name="name" id="register-name" required
                autocomplete="name" autofocus x-model="name" />
            <x-input label="{{ __('stoman.auth.email') }}" name="email" id="register-email" type="email" required
                autocomplete="email" x-model="email" />
            <x-input label="{{ __('stoman.auth.password') }}" name="password" id="register-password" type="password"
                required autocomplete="new-password" x-model="password" />
            <x-input label="{{ __('stoman.auth.confirm_password') }}" name="password_confirmation"
                id="register-password-confirmation" type="password" required autocomplete="new-password" x-model="password_confirmation" />

            <x-button type="submit" class="w-full">
                <span x-show="!loading">{{ __('stoman.auth.sign_up') }}</span>
                <span x-show="loading" x-cloak class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Creating account…
                </span>
            </x-button>

            <p class="text-center text-sm text-slate-600">
                {{ __('stoman.auth.have_account_prefix') }}
                <a href="{{ route('login') }}"
                    class="font-medium text-primary-600 transition-colors hover:text-primary-700">{{ __('stoman.auth.sign_in') }}</a>
            </p>
        </form>
    </div>
@endsection
