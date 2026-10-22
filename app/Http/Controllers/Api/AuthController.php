<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login view.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Show register view.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle web login form.
     */
    public function webLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            // Generate JWT token as well
            $token = auth('api')->login(Auth::user());

            return redirect()->intended(route('storage.index'))->with('jwt_token', $token);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle web register form.
     */
    public function webRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'password'=> Hash::make($validated['password']),
            'role_id' => Role::USER, // Self-registered users get read-only role
        ]);

        Auth::login($user);
        $token = auth('api')->login($user);

        return redirect()->route('storage.index')->with('jwt_token', $token);
    }

    /**
     * Handle web logout.
     */
    public function webLogout(Request $request)
    {
        try {
            if (auth('api')->check()) {
                auth('api')->logout();
            }
        } catch (\Exception $e) {
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Authenticate user and return JWT token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user = auth('api')->user();

        // Also log into web session so page navigation works smoothly
        Auth::guard('web')->login($user, $request->boolean('remember'));

        return $this->respondWithToken($token, $user);
    }

    /**
     * Register a new user and return JWT token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name'    => $validated['name'],
            'email'   => $validated['email'],
            'password'=> Hash::make($validated['password']),
            'role_id' => Role::USER, // Self-registered users get read-only role
        ]);

        $token = auth('api')->login($user);
        Auth::guard('web')->login($user);

        return $this->respondWithToken($token, $user, 201);
    }

    /**
     * Get the authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user() ?? Auth::guard('web')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $user,  
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        try {
            if (auth('api')->check()) {
                auth('api')->logout();
            }
        } catch (\Exception $e) {
            // Token might already be expired or missing
        }

        Auth::guard('web')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = auth('api')->refresh();

            return $this->respondWithToken($newToken, auth('api')->user());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed.',
            ], 401);
        }
    }

    /**
     * Format token response JSON.
     */
    protected function respondWithToken(string $token, ?User $user = null, int $status = 200): JsonResponse
    {
        $ttl = config('jwt.ttl', 60);

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttl * 60,
            'user' => $user,
        ], $status);
    }
}
