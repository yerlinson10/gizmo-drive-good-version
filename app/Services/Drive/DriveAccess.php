<?php

namespace App\Services\Drive;

use App\Enums\SharePermission;
use App\Models\DriveFile;
use App\Models\Folder;
use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DriveAccess
{
    public function canView(User $user, Folder|DriveFile $item): bool
    {
        return $this->permissionFor($user, $item) !== null;
    }

    public function canEdit(User $user, Folder|DriveFile $item): bool
    {
        return $this->isOwner($user, $item)
            || $this->permissionFor($user, $item) === SharePermission::Edit;
    }

    public function isOwner(User $user, Folder|DriveFile $item): bool
    {
        return (int) $item->user_id === (int) $user->id;
    }

    public function permissionFor(User $user, Folder|DriveFile $item): ?SharePermission
    {
        if ($this->isOwner($user, $item)) {
            return SharePermission::Edit;
        }

        $direct = $this->directShare($user, $item);

        if ($direct) {
            return $direct->permission;
        }

        if ($item instanceof DriveFile) {
            $item->loadMissing('folder');

            if ($item->folder) {
                return $this->permissionFor($user, $item->folder);
            }
        }

        if ($item instanceof Folder && $item->parent_id) {
            $item->loadMissing('parent');

            if ($item->parent) {
                return $this->permissionFor($user, $item->parent);
            }
        }

        return null;
    }

    protected function directShare(User $user, Model $item): ?Share
    {
        return Share::query()
            ->where('shareable_type', $item->getMorphClass())
            ->where('shareable_id', $item->getKey())
            ->where('shared_with', $user->id)
            ->first();
    }
}
