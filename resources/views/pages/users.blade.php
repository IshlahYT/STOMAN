@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div x-data="{
    addModalOpen: false,
    editModalOpen: false,
    deleteModalOpen: false,
    currentUser: { id: null, name: '', email: '', role_id: 2 },
    openEdit(user) {
        this.currentUser = { ...user };
        this.editModalOpen = true;
    },
    openDelete(user) {
        this.currentUser = { ...user };
        this.deleteModalOpen = true;
    }
}" class="space-y-6">

    {{-- 1. Page Header & Actions --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-[28px] font-bold leading-9 text-slate-900">User Management</h1>
            <p class="mt-1 text-sm text-slate-600">Manage user accounts, assign roles, and control access permissions.</p>
        </div>
        <div>
            <x-button size="m" icon="plus" @click="addModalOpen = true" id="btn-add-user">
                Add New User
            </x-button>
        </div>
    </div>

    {{-- Flash notifications --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            <x-icon name="check" :size="20" class="text-emerald-600 shrink-0" />
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
            <div class="flex items-center gap-2 font-semibold">
                <x-icon name="alert" :size="18" class="text-rose-600 shrink-0" />
                <span>Please correct the following errors:</span>
            </div>
            <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2. Stats Overview Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Total Users --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Users</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <x-icon name="users" :size="20" />
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ $stats['total'] }}</span>
                <span class="text-xs text-slate-500">registered accounts</span>
            </div>
        </div>

        {{-- Administrators --}}
        <div class="rounded-xl border border-purple-200 bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-purple-700">Administrators</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                    <x-icon name="shield" :size="20" />
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ $stats['admins'] }}</span>
                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">
                    Full CRUD
                </span>
            </div>
        </div>

        {{-- Standard Users (Read-only) --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Regular Users</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <x-icon name="user" :size="20" />
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-slate-900">{{ $stats['standard'] }}</span>
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/20">
                    Read-Only
                </span>
            </div>
        </div>
    </div>

    {{-- 3. Filter & Search Bar --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-card">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                {{-- Search Box --}}
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="search" :size="18" />
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Search users by name or email…"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                </div>

                {{-- Role Filter --}}
                <div class="w-full sm:w-48">
                    <select name="role" onchange="this.form.submit()"
                        class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string)$roleFilter === (string)$role->id)>
                                {{ $role->name }} ({{ $role->id === 1 ? 'Admin' : 'User' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="secondary" size="s">
                    Filter
                </x-button>
                @if($search !== '' || $roleFilter !== null)
                    <a href="{{ route('users.index') }}" class="inline-flex h-8 items-center justify-center rounded-lg px-3 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- 4. Users Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">User</th>
                        <th scope="col" class="px-3 py-3.5">Role</th>
                        <th scope="col" class="px-3 py-3.5">Permissions</th>
                        <th scope="col" class="px-3 py-3.5">Joined Date</th>
                        <th scope="col" class="py-3.5 pl-3 pr-4 text-right sm:pr-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($users as $user)
                        @php
                            $initial = strtoupper(mb_substr($user->name, 0, 1));
                            $isAdmin = $user->isAdmin();
                            $isSelf = auth()->id() === $user->id;
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/80">
                            {{-- User Info --}}
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $isAdmin ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $initial }}
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-900">{{ $user->name }}</span>
                                            @if($isSelf)
                                                <span class="inline-flex items-center rounded bg-primary-50 px-1.5 py-0.5 text-[10px] font-semibold text-primary-700">
                                                    You
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-slate-500">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Role Badge --}}
                            <td class="whitespace-nowrap px-3 py-4">
                                @if($isAdmin)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700">
                                        <x-icon name="shield" :size="14" class="text-purple-600" />
                                        Admin (id: 1)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        <x-icon name="user" :size="14" class="text-slate-500" />
                                        User (id: 2)
                                    </span>
                                @endif
                            </td>

                            {{-- Permissions Column --}}
                            <td class="whitespace-nowrap px-3 py-4 text-xs">
                                @if($isAdmin)
                                    <span class="font-medium text-emerald-700 flex items-center gap-1">
                                        <x-icon name="check" :size="14" class="text-emerald-600" />
                                        Full CRUD (Folders & Files)
                                    </span>
                                @else
                                    <span class="font-medium text-slate-500 flex items-center gap-1">
                                        <x-icon name="eye" :size="14" class="text-slate-400" />
                                        Read-Only (Files Viewing)
                                    </span>
                                @endif
                            </td>

                            {{-- Joined Date --}}
                            <td class="whitespace-nowrap px-3 py-4 text-xs text-slate-500">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : '-' }}
                            </td>

                            {{-- Actions --}}
                            <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Edit Button --}}
                                    <button type="button"
                                        @click="openEdit({
                                            id: {{ $user->id }},
                                            name: '{{ addslashes($user->name) }}',
                                            email: '{{ addslashes($user->email) }}',
                                            role_id: {{ $user->role_id }}
                                        })"
                                        class="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-ring"
                                        title="Edit user">
                                        <x-icon name="pencil" :size="16" />
                                    </button>

                                    {{-- Delete Button --}}
                                    @if($isSelf)
                                        <button type="button" disabled
                                            class="cursor-not-allowed rounded-lg p-1.5 text-slate-300"
                                            title="You cannot delete your own account">
                                            <x-icon name="trash" :size="16" />
                                        </button>
                                    @else
                                        <button type="button"
                                            @click="openDelete({
                                                id: {{ $user->id }},
                                                name: '{{ addslashes($user->name) }}',
                                                email: '{{ addslashes($user->email) }}'
                                            })"
                                            class="rounded-lg p-1.5 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-700 focus-ring"
                                            title="Delete user">
                                            <x-icon name="trash" :size="16" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <x-icon name="users" :size="24" />
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-900">No users found</p>
                                <p class="mt-1 text-xs text-slate-500">Try adjusting your search criteria or add a new user.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL 1: ADD NEW USER                                    --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="addModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-add-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 transition-opacity" @click="addModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all"
                @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                            <x-icon name="users" :size="20" />
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900" id="modal-add-title">Add New User</h3>
                            <p class="text-xs text-slate-500">Create a new system user with assigned permissions</p>
                        </div>
                    </div>
                    <button type="button" @click="addModalOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <form method="POST" action="{{ route('users.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="add-name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Full Name</label>
                        <input type="text" name="name" id="add-name" required
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            placeholder="e.g. Jane Doe" />
                    </div>

                    <div>
                        <label for="add-email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Email Address</label>
                        <input type="email" name="email" id="add-email" required
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            placeholder="jane@example.com" />
                    </div>

                    <div>
                        <label for="add-password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Password</label>
                        <input type="password" name="password" id="add-password" required minlength="6"
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            placeholder="Minimum 6 characters" />
                    </div>

                    <div>
                        <label for="add-role" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Assigned Role</label>
                        <select name="role_id" id="add-role" required
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="2">User (id: 2) — Read-Only for Viewing Files</option>
                            <option value="1">Admin (id: 1) — Full CRUD for Folders, Files & Users</option>
                        </select>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <x-button type="button" variant="secondary" size="m" @click="addModalOpen = false">
                            Cancel
                        </x-button>
                        <x-button type="submit" size="m">
                            Create User
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL 2: EDIT USER                                       --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="editModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-edit-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 transition-opacity" @click="editModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all"
                @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                            <x-icon name="pencil" :size="20" />
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900" id="modal-edit-title">Edit User</h3>
                            <p class="text-xs text-slate-500">Update account credentials or assigned role</p>
                        </div>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring">
                        <x-icon name="x" :size="18" />
                    </button>
                </div>

                <form method="POST" :action="'/users/' + currentUser.id" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="edit-name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Full Name</label>
                        <input type="text" name="name" id="edit-name" x-model="currentUser.name" required
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>

                    <div>
                        <label for="edit-email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Email Address</label>
                        <input type="email" name="email" id="edit-email" x-model="currentUser.email" required
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>

                    <div>
                        <label for="edit-password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">New Password (optional)</label>
                        <input type="password" name="password" id="edit-password" minlength="6"
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            placeholder="Leave blank to keep unchanged" />
                    </div>

                    <div>
                        <label for="edit-role" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">Assigned Role</label>
                        <select name="role_id" id="edit-role" x-model="currentUser.role_id" required
                            class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                            <option value="2">User (id: 2) — Read-Only for Viewing Files</option>
                            <option value="1">Admin (id: 1) — Full CRUD for Folders, Files & Users</option>
                        </select>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <x-button type="button" variant="secondary" size="m" @click="editModalOpen = false">
                            Cancel
                        </x-button>
                        <x-button type="submit" size="m">
                            Save Changes
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- MODAL 3: DELETE CONFIRMATION                             --}}
    {{-- ======================================================== --}}
    <div x-cloak x-show="deleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-delete-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 transition-opacity" @click="deleteModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all"
                @click.stop>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <x-icon name="trash" :size="22" />
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900" id="modal-delete-title">Delete User Account</h3>
                        <p class="text-xs text-slate-500">This action cannot be undone.</p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-600">
                    Are you sure you want to delete user <span class="font-semibold text-slate-900" x-text="currentUser.name"></span> (<span x-text="currentUser.email"></span>)? All associated data will be removed.
                </div>

                <form method="POST" :action="'/users/' + currentUser.id" class="mt-5 flex items-center justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-button type="button" variant="secondary" size="m" @click="deleteModalOpen = false">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="danger" size="m">
                        Confirm Delete
                    </x-button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
