<?php

namespace App\Http\Controllers;

use App\Models\FileItem;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    protected function getUser(): ?User
    {
        return auth('web')->user() ?? auth('api')->user();
    }

    /**
     * Which owners' content may appear on the dashboard.
     *
     * Admin sees their own folders/files; a regular (read-only) user only
     * sees folders and files that were created by an Admin account.
     *
     * @return array<int, int>
     */
    protected function visibleOwnerIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isAdmin()) {
            return [$user->id];
        }

        return User::where('role_id', Role::ADMIN)
            ->where('id', '!=', $user->id)
            ->pluck('id')
            ->all();
    }

    public function index(): View
    {
        $user = $this->getUser();
        $ownerIds = $this->visibleOwnerIds($user);

        $folders = $ownerIds
            ? Folder::whereIn('user_id', $ownerIds)
                ->whereNull('parent_id')
                ->where('is_trashed', false)
                ->where('is_archived', false)
                ->get()
            : collect();

        $starredFolders = $ownerIds
            ? Folder::whereIn('user_id', $ownerIds)
                ->where('is_starred', true)
                ->where('is_trashed', false)
                ->get()
            : collect();

        $collaborativeFolders = $ownerIds
            ? Folder::whereIn('user_id', $ownerIds)
                ->whereHas('shares')
                ->where('is_trashed', false)
                ->get()
            : collect();

        $files = $ownerIds
            ? FileItem::whereIn('user_id', $ownerIds)
                ->where('is_trashed', false)
                ->where('is_archived', false)
                ->latest()
                ->limit(12)
                ->get()
            : collect();

        return view('pages.home', [
            'topbarTitle' => $user?->isAdmin()
                ? __('stoman.nav.home')
                : __('stoman.nav.my_folder'),
            'breadcrumbs' => [],
            'folders' => $folders,
            'starredFolders' => $starredFolders,
            'collaborativeFolders' => $collaborativeFolders,
            'files' => $files,
        ]);
    }
}
