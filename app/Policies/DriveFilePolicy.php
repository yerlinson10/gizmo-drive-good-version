<?php

namespace App\Policies;

use App\Models\DriveFile;
use App\Models\User;
use App\Services\Drive\DriveAccess;

class DriveFilePolicy
{
    public function __construct(private DriveAccess $access) {}

    public function view(User $user, DriveFile $file): bool
    {
        return $this->access->canView($user, $file);
    }

    public function update(User $user, DriveFile $file): bool
    {
        return $this->access->canEdit($user, $file);
    }

    public function delete(User $user, DriveFile $file): bool
    {
        return $this->access->isOwner($user, $file);
    }

    public function share(User $user, DriveFile $file): bool
    {
        return $this->access->isOwner($user, $file);
    }
}
