<?php

namespace App\Services\Drive;

use App\Enums\SharePermission;
use App\Models\DriveFile;
use App\Models\Folder;
use App\Models\Share;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveService
{
    public function __construct(private DriveAccess $access) {}

    /**
     * Build the browse page payload for a user's current folder context.
     *
     * @return array{
     *     currentFolder: ?array<string, mixed>,
     *     breadcrumbs: list<array{id: int, name: string}>,
     *     folders: list<array<string, mixed>>,
     *     files: list<array<string, mixed>>,
     *     sharedWithMe: ?array{folders: list<array<string, mixed>>, files: list<array<string, mixed>>},
     *     canCreate: bool
     * }
     */
    public function browse(User $user, ?int $folderId = null): array
    {
        $currentFolder = null;
        $breadcrumbs = [];

        if ($folderId) {
            $currentFolder = Folder::query()->with('parent')->findOrFail($folderId);

            if (! $this->access->canView($user, $currentFolder)) {
                throw new AuthorizationException;
            }

            $breadcrumbs = collect($currentFolder->ancestors())
                ->push($currentFolder)
                ->map(fn (Folder $folder): array => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                ])
                ->values()
                ->all();
        }

        $folders = $this->foldersIn($user, $currentFolder, $folderId)
            ->filter(fn (Folder $folder): bool => $this->access->canView($user, $folder))
            ->map(fn (Folder $folder): array => $this->presentFolder($folder, $user))
            ->values()
            ->all();

        $files = $this->filesIn($user, $currentFolder, $folderId)
            ->filter(fn (DriveFile $file): bool => $this->access->canView($user, $file))
            ->map(fn (DriveFile $file): array => $this->presentFile($file, $user))
            ->values()
            ->all();

        return [
            'currentFolder' => $currentFolder
                ? $this->presentFolder($currentFolder, $user)
                : null,
            'breadcrumbs' => $breadcrumbs,
            'folders' => $folders,
            'files' => $files,
            'sharedWithMe' => $folderId ? null : $this->sharedWithMe($user),
            'canCreate' => $currentFolder
                ? $this->access->canEdit($user, $currentFolder)
                : true,
        ];
    }

    public function createFolder(User $user, string $name, ?int $parentId = null): Folder
    {
        return Folder::query()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => $name,
        ]);
    }

    public function uploadFile(User $user, UploadedFile $uploaded, ?int $folderId = null): DriveFile
    {
        $path = $uploaded->store('drive/'.$user->id, 'local');

        return DriveFile::query()->create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'name' => $uploaded->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize() ?: 0,
        ]);
    }

    public function download(DriveFile $file): StreamedResponse
    {
        return Storage::disk($file->disk)->download($file->path, $file->name);
    }

    public function deleteFolder(Folder $folder): ?int
    {
        $parentId = $folder->parent_id;

        DB::transaction(function () use ($folder): void {
            $this->deleteFolderRecursive($folder);
        });

        return $parentId;
    }

    public function deleteFile(DriveFile $file): ?int
    {
        $folderId = $file->folder_id;

        $file->deleteFromStorage();
        $file->delete();

        return $folderId;
    }

    public function share(
        User $owner,
        Folder|DriveFile $item,
        string $email,
        SharePermission|string $permission,
    ): Share {
        $recipient = User::query()->where('email', $email)->firstOrFail();

        if ($recipient->is($owner)) {
            throw ValidationException::withMessages([
                'email' => 'You cannot share with yourself.',
            ]);
        }

        $permission = $permission instanceof SharePermission
            ? $permission
            : SharePermission::from($permission);

        return Share::query()->updateOrCreate(
            [
                'shareable_type' => $item->getMorphClass(),
                'shareable_id' => $item->getKey(),
                'shared_with' => $recipient->id,
            ],
            [
                'shared_by' => $owner->id,
                'permission' => $permission,
            ],
        );
    }

    public function revokeShare(User $actor, Share $share): void
    {
        if ((int) $share->shared_by !== (int) $actor->id) {
            throw new AuthorizationException;
        }

        $share->delete();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Folder>
     */
    protected function foldersIn(User $user, ?Folder $currentFolder, ?int $folderId)
    {
        $query = Folder::query()
            ->where('parent_id', $folderId)
            ->orderBy('name');

        if ($currentFolder && ! $this->access->isOwner($user, $currentFolder)) {
            $query->where(function ($builder) use ($user, $currentFolder): void {
                $builder->where('user_id', $currentFolder->user_id)
                    ->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, DriveFile>
     */
    protected function filesIn(User $user, ?Folder $currentFolder, ?int $folderId)
    {
        $query = DriveFile::query()
            ->where('folder_id', $folderId)
            ->orderBy('name');

        if ($currentFolder && ! $this->access->isOwner($user, $currentFolder)) {
            $query->where(function ($builder) use ($user, $currentFolder): void {
                $builder->where('user_id', $currentFolder->user_id)
                    ->orWhere('user_id', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    /**
     * @return array{folders: list<array<string, mixed>>, files: list<array<string, mixed>>}
     */
    protected function sharedWithMe(User $user): array
    {
        return [
            'folders' => Folder::query()
                ->whereHas('shares', fn ($query) => $query->where('shared_with', $user->id))
                ->with('owner:id,name,email')
                ->orderBy('name')
                ->get()
                ->map(fn (Folder $folder): array => $this->presentFolder($folder, $user))
                ->values()
                ->all(),
            'files' => DriveFile::query()
                ->whereHas('shares', fn ($query) => $query->where('shared_with', $user->id))
                ->with('owner:id,name,email')
                ->orderBy('name')
                ->get()
                ->map(fn (DriveFile $file): array => $this->presentFile($file, $user))
                ->values()
                ->all(),
        ];
    }

    protected function deleteFolderRecursive(Folder $folder): void
    {
        $folder->load(['children', 'files']);

        foreach ($folder->children as $child) {
            $this->deleteFolderRecursive($child);
        }

        foreach ($folder->files as $file) {
            $file->deleteFromStorage();
            $file->delete();
        }

        $folder->shares()->delete();
        $folder->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentFolder(Folder $folder, User $user): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'parent_id' => $folder->parent_id,
            'is_owner' => $this->access->isOwner($user, $folder),
            'can_edit' => $this->access->canEdit($user, $folder),
            'can_share' => $this->access->isOwner($user, $folder),
            'owner' => $folder->relationLoaded('owner') && $folder->owner
                ? [
                    'id' => $folder->owner->id,
                    'name' => $folder->owner->name,
                    'email' => $folder->owner->email,
                ]
                : null,
            'updated_at' => $folder->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentFile(DriveFile $file, User $user): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'folder_id' => $file->folder_id,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'is_owner' => $this->access->isOwner($user, $file),
            'can_edit' => $this->access->canEdit($user, $file),
            'can_share' => $this->access->isOwner($user, $file),
            'owner' => $file->relationLoaded('owner') && $file->owner
                ? [
                    'id' => $file->owner->id,
                    'name' => $file->owner->name,
                    'email' => $file->owner->email,
                ]
                : null,
            'updated_at' => $file->updated_at?->toIso8601String(),
        ];
    }
}
