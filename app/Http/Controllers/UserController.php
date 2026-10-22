<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     * Restricted to Admin role (id: 1).
     */
    public function index(Request $request): View|JsonResponse
    {
        $currentUser = auth('web')->user() ?? auth('api')->user();

        if (! $currentUser || ! $currentUser->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden. Admin privileges required.'], 403);
            }
            abort(403, 'Forbidden. Admin privileges required.');
        }

        $search = trim((string) $request->query('search', ''));
        $roleFilter = $request->query('role');

        $query = User::with('role');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (is_numeric($roleFilter)) {
            $query->where('role_id', (int) $roleFilter);
        }

        $users = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();
        $roles = Role::orderBy('id', 'asc')->get();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role_id', Role::ADMIN)->count(),
            'standard' => User::where('role_id', Role::USER)->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'users' => $users,
                'stats' => $stats,
                'roles' => $roles,
            ]);
        }

        return view('pages.users', compact('users', 'roles', 'stats', 'search', 'roleFilter'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $currentUser = auth('web')->user() ?? auth('api')->user();
        if (! $currentUser || ! $currentUser->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            abort(403, 'Forbidden.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role_id' => (int) $validated['role_id'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => $user->load('role'),
            ], 201);
        }

        return redirect()->route('users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $currentUser = auth('web')->user() ?? auth('api')->user();
        if (! $currentUser || ! $currentUser->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            abort(403, 'Forbidden.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        // Prevent demoting the only remaining Admin
        $targetRoleId = (int) $validated['role_id'];
        if ($user->isAdmin() && $targetRoleId !== Role::ADMIN) {
            $adminCount = User::where('role_id', Role::ADMIN)->count();
            if ($adminCount <= 1) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot demote the only remaining Administrator.',
                    ], 422);
                }

                return back()->withErrors(['role_id' => 'Cannot demote the only remaining Administrator.']);
            }
        }

        $user->name = trim($validated['name']);
        $user->email = strtolower(trim($validated['email']));
        $user->role_id = $targetRoleId;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user' => $user->load('role'),
            ]);
        }

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $currentUser = auth('web')->user() ?? auth('api')->user();
        if (! $currentUser || ! $currentUser->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            abort(403, 'Forbidden.');
        }

        // Prevent self deletion
        if ($currentUser && $user->id === $currentUser->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.',
                ], 422);
            }

            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Prevent deleting the only remaining Admin
        if ($user->isAdmin()) {
            $adminCount = User::where('role_id', Role::ADMIN)->count();
            if ($adminCount <= 1) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete the only remaining Administrator.',
                    ], 422);
                }

                return back()->withErrors(['error' => 'Cannot delete the only remaining Administrator.']);
            }
        }

        $userName = $user->name;
        $user->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User '{$userName}' deleted successfully.",
            ]);
        }

        return redirect()->route('users.index')->with('success', "User '{$userName}' deleted successfully.");
    }
}
