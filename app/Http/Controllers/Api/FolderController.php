<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    protected function getUser(): ?User
    {
        return auth('api')->user() ?? auth('web')->user() ?? User::first();
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can manage folders.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[^\/\\:*?"<>|]+$/'],
            'color' => ['nullable', 'string', 'in:primary,sky,violet,rose,amber,slate'],
            'parent_id' => ['nullable'],
        ]);

        $parentId = $request->input('parent_id');
        if (! is_numeric($parentId)) {
            $parentId = null;
        }

        $folder = Folder::create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => trim($validated['name']),
            'color' => $validated['color'] ?? 'primary',
        ]);

        return response()->json([
            'success' => true,
            'folder' => $folder,
        ], 201);
    }

    /**
     * Display the specified folder.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->getUser();
        $folder = Folder::with(['children', 'files'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'folder' => $folder,
        ]);
    }

    /**
     * Update the specified folder.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can manage folders.',
            ], 403);
        }

        $folder = Folder::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['sometimes', 'string', 'in:primary,sky,violet,rose,amber,slate'],
        ]);

        $folder->update($validated);

        return response()->json([
            'success' => true,
            'folder' => $folder,
        ]);
    }

    /**
     * Remove the specified folder (move to trash).
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can manage folders.',
            ], 403);
        }

        $folder = Folder::findOrFail($id);
        $folder->update([
            'is_trashed' => true,
            'trashed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder moved to trash.',
        ]);
    }
}
