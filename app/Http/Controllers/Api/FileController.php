<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    protected function getUser(): ?User
    {
        return auth('api')->user() ?? auth('web')->user() ?? User::first();
    }

    /**
     * Upload a file or files.
     */
    public function upload(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only administrators can upload files.',
            ], 403);
        }

        $request->validate([
            'file' => ['required', 'file'],
            'folder_id' => ['nullable'],
        ]);

        $uploaded = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $mimeType = $uploaded->getClientMimeType() ?: $uploaded->getMimeType();
        $size = $uploaded->getSize();

        $folderId = $request->input('folder_id');
        if (! is_numeric($folderId)) {
            $folderId = null;
        }

        // Store file in local disk
        $path = $uploaded->store("uploads/{$user->id}", 'local');

        $fileItem = FileItem::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'name' => $originalName,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
        ]);

        return response()->json([
            'success' => true,
            'file' => $fileItem,
        ], 201);
    }

    /**
     * Download the specified file.
     */
    public function download(int $id): StreamedResponse|BinaryFileResponse|JsonResponse
    {
        $file = FileItem::findOrFail($id);

        if (! Storage::disk('local')->exists($file->path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk('local')->download($file->path, $file->name);
    }

    /**
     * Preview the specified file in browser.
     */
    public function preview(int $id): StreamedResponse|BinaryFileResponse|JsonResponse
    {
        $file = FileItem::findOrFail($id);

        if (! Storage::disk('local')->exists($file->path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk('local')->response($file->path, $file->name, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$file->name.'"',
        ]);
    }
}
