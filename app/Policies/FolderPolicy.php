<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use App\Services\Drive\DriveAccess;

class FolderPolicy
{
    public function __construct(private DriveAccess $access) {}

    public function view(User $user, Folder $folder): bool
    {
        return $this->access->canView($user, $folder);
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->access->canEdit($user, $folder);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->access->isOwner($user, $folder);
    }

    public function share(User $user, Folder $folder): bool
    {
        return $this->access->isOwner($user, $folder);
    }
}
