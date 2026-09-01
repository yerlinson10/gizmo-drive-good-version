<?php

namespace App\Enums;

enum SharePermission: string
{
    case View = 'view';
    case Edit = 'edit';

    public function allowsEdit(): bool
    {
        return $this === self::Edit;
    }
}
