<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileItem;
use App\Models\Folder;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    protected function getUser(): ?User
    {
        return auth('api')->user() ?? auth('web')->user() ?? User::first();
    }

    /**
     * List items (folders and files).
     */
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['folders' => [], 'files' => []]);
        }

        $folderId = $request->query('folder');
        $search = $request->query('search');
        $starred = $request->boolean('starred');
        $archived = $request->boolean('archived');
        $trash = $request->boolean('trash');
        $recent = $request->boolean('recent');

        $folderQuery = Folder::where('user_id', $user->id);
        $fileQuery = FileItem::where('user_id', $user->id);

        if ($trash) {
            $folderQuery->where('is_trashed', true);
            $fileQuery->where('is_trashed', true);
        } else {
            $folderQuery->where('is_trashed', false);
            $fileQuery->where('is_trashed', false);

            if ($starred) {
                $folderQuery->where('is_starred', true);
                $fileQuery->where('is_starred', true);
            } elseif ($archived) {
                $folderQuery->where('is_archived', true);
                $fileQuery->where('is_archived', true);
            } else {
                $folderQuery->where('is_archived', false);
                $fileQuery->where('is_archived', false);

                if (is_numeric($folderId)) {
                    $folderQuery->where('parent_id', (int) $folderId);
                    $fileQuery->where('folder_id', (int) $folderId);
                } else {
                    $folderQuery->whereNull('parent_id');
                    $fileQuery->whereNull('folder_id');
                }
            }
        }

        if ($search) {
            $folderQuery->where('name', 'like', "%{$search}%");
            $fileQuery->where('name', 'like', "%{$search}%");
        }

        if ($recent) {
            $fileQuery->orderByDesc('updated_at')->limit(20);

            return response()->json([
                'folders' => [],
                'files' => $fileQuery->get(),
            ]);
        }

        $folders = $folderQuery->orderBy('name')->get();
        $files = $fileQuery->orderBy('name')->get();

        return response()->json([
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    /**
     * Ensure user has Admin privileges for mutating operations.
     */
    protected function ensureAdmin(?User $user): ?JsonResponse
    {
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can manage folders and files.',
            ], 403);
        }

        return null;
    }

    /**
     * Rename an item.
     */
    public function rename(Request $request, int|string $id): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($validated['name']);

        // Check if file or folder
        $file = FileItem::find($id);
        if ($file) {
            $file->update(['name' => $name]);

            return response()->json(['success' => true, 'id' => $id, 'name' => $name, 'item' => $file]);
        }

        $folder = Folder::find($id);
        if ($folder) {
            $folder->update(['name' => $name]);

            return response()->json(['success' => true, 'id' => $id, 'name' => $name, 'item' => $folder]);
        }

        return response()->json(['message' => 'Item not found.'], 404);
    }

    /**
     * Star or unstar an item.
     */
    public function star(Request $request, int|string $id): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $starred = $request->boolean('starred', true);

        $file = FileItem::find($id);
        if ($file) {
            $file->update(['is_starred' => $starred]);

            return response()->json(['success' => true, 'item' => $file]);
        }

        $folder = Folder::find($id);
        if ($folder) {
            $folder->update(['is_starred' => $starred]);

            return response()->json(['success' => true, 'item' => $folder]);
        }

        return response()->json(['message' => 'Item not found.'], 404);
    }

    /**
     * Move items to target folder.
     */
    public function move(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $request->validate([
            'ids' => ['required', 'array'],
            'target' => ['nullable'],
        ]);

        $ids = $request->input('ids', []);
        $targetId = $request->input('target');
        if (! is_numeric($targetId)) {
            $targetId = null;
        }

        FileItem::whereIn('id', $ids)->update(['folder_id' => $targetId]);
        Folder::whereIn('id', $ids)->where('id', '!=', $targetId)->update(['parent_id' => $targetId]);

        return response()->json(['success' => true]);
    }

    /**
     * Share an item.
     */
    public function share(Request $request, int|string $id): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $user = $this->getUser();
        $members = $request->input('members', []);
        $role = $request->input('role', 'viewer');
        $link = $request->boolean('link', false);

        $folder = is_numeric($id) ? Folder::find($id) : null;
        $file = is_numeric($id) ? FileItem::find($id) : null;

        foreach ($members as $email) {
            Share::create([
                'user_id' => $user?->id ?? 1,
                'folder_id' => $folder?->id,
                'file_id' => $file?->id,
                'shared_with_email' => $email,
                'role' => $role,
                'link_share' => $link,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Archive items.
     */
    public function archive(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $ids = $request->input('ids', []);

        FileItem::whereIn('id', $ids)->update(['is_archived' => true]);
        Folder::whereIn('id', $ids)->update(['is_archived' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Move items to trash or permanently destroy if already trashed.
     */
    public function destroy(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $ids = $request->input('ids', []);

        $files = FileItem::whereIn('id', $ids)->get();
        $folders = Folder::whereIn('id', $ids)->get();

        foreach ($files as $file) {
            if ($file->is_trashed) {
                // Permanently delete file from disk & DB
                if (Storage::disk('local')->exists($file->path)) {
                    Storage::disk('local')->delete($file->path);
                }
                $file->delete();
            } else {
                $file->update(['is_trashed' => true, 'trashed_at' => now()]);
            }
        }

        foreach ($folders as $folder) {
            if ($folder->is_trashed) {
                $folder->delete();
            } else {
                $folder->update(['is_trashed' => true, 'trashed_at' => now()]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Restore items from trash.
     */
    public function restore(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAdmin($this->getUser())) {
            return $denied;
        }

        $ids = $request->input('ids', []);

        FileItem::whereIn('id', $ids)->update(['is_trashed' => false, 'trashed_at' => null]);
        Folder::whereIn('id', $ids)->update(['is_trashed' => false, 'trashed_at' => null]);

        return response()->json(['success' => true]);
    }

    /**
     * Empty entire trash for user.
     */
    public function emptyTrash(): JsonResponse
    {
        $user = $this->getUser();
        if ($denied = $this->ensureAdmin($user)) {
            return $denied;
        }

        $files = FileItem::where('user_id', $user->id)->where('is_trashed', true)->get();
        foreach ($files as $file) {
            if (Storage::disk('local')->exists($file->path)) {
                Storage::disk('local')->delete($file->path);
            }
            $file->delete();
        }

        Folder::where('user_id', $user->id)->where('is_trashed', true)->delete();

        return response()->json(['success' => true]);
    }
}
