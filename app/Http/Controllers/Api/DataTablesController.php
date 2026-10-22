<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileItem;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DataTablesController extends Controller
{
    protected function getUser(): ?User
    {
        return auth('api')->user() ?? auth('web')->user() ?? User::first();
    }

    /**
     * Provide DataTables JSON response for files.
     */
    public function files(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $type = $request->query('type', 'all'); // all, starred, archived, archive, trash, recent, shared
        $folderId = $request->query('folder_id');

        if ($type === 'shared') {
            $email = $user?->email;
            $sharedFileIds = $email
                ? Share::where('shared_with_email', $email)->whereNotNull('file_id')->pluck('file_id')
                : collect();

            $query = FileItem::with('user')->whereIn('id', $sharedFileIds)->where('is_trashed', false);
        } else {
            $query = FileItem::with('user');

            if ($user) {
                $query->where('user_id', $user->id);
            }

            if ($type === 'trash') {
                $query->where('is_trashed', true);
            } else {
                $query->where('is_trashed', false);

                if ($type === 'starred') {
                    $query->where('is_starred', true);
                } elseif ($type === 'archived' || $type === 'archive') {
                    $query->where('is_archived', true);
                } elseif ($type === 'recent') {
                    $query->where('is_archived', false)->latest('updated_at');
                } else {
                    $query->where('is_archived', false);
                    if (is_numeric($folderId)) {
                        $query->where('folder_id', (int) $folderId);
                    }
                }
            }
        }

        $isAdmin = $user && $user->isAdmin();

        return DataTables::of($query)
            ->addColumn('checkbox', function ($row) use ($isAdmin) {
                if (! $isAdmin) {
                    return '';
                }

                return '<input type="checkbox" value="'.$row->id.'" class="dt-checkbox h-4 w-4 rounded border-slate-300 accent-primary-600 focus-ring" />';
            })
            ->editColumn('name', function ($row) {
                return '<div class="flex items-center gap-2.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <span class="font-medium text-slate-900 truncate">'.e($row->name).'</span>
                </div>';
            })
            ->addColumn('owner', function ($row) {
                return e($row->user?->name ?? 'Me');
            })
            ->addColumn('modified', function ($row) {
                return $row->updated_at ? $row->updated_at->diffForHumans() : '-';
            })
            ->addColumn('formatted_size', function ($row) {
                return $row->size_label;
            })
            ->addColumn('actions', function ($row) use ($type, $isAdmin) {
                $downloadUrl = route('api.files.download', $row->id);
                $previewUrl = route('api.files.preview', $row->id);

                if (! $isAdmin) {
                    return '<div class="flex items-center justify-end gap-1">
                        <a href="'.$previewUrl.'" target="_blank" class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="Preview">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        <a href="'.$downloadUrl.'" class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="Download">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>';
                }

                if ($type === 'trash') {
                    return '<div class="flex items-center justify-end gap-1">
                        <button type="button" class="btn-dt-restore rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700" data-id="'.$row->id.'" title="Restore">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        </button>
                        <button type="button" class="btn-dt-delete-permanently rounded p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700" data-id="'.$row->id.'" title="Delete Permanently">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>';
                }

                $starClass = $row->is_starred ? 'text-amber-500 fill-amber-400' : 'text-slate-400 hover:text-amber-500';

                return '<div class="flex items-center justify-end gap-1">
                    <button type="button" class="btn-dt-star rounded p-1.5 '.$starClass.'" data-id="'.$row->id.'" data-starred="'.($row->is_starred ? '1' : '0').'" title="Star">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </button>
                    <a href="'.$downloadUrl.'" class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700" title="Download">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                    <button type="button" class="btn-dt-trash rounded p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" data-id="'.$row->id.'" title="Move to Trash">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>';
            })
            ->rawColumns(['checkbox', 'name', 'actions'])
            ->make(true);
    }
}
