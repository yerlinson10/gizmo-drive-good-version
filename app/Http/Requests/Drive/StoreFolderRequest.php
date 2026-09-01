<?php

namespace App\Http\Requests\Drive;

use App\Models\Folder;
use App\Services\Drive\DriveAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('drive.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->integer('parent_id') ?: null;

            if (! $parentId) {
                return;
            }

            $parent = Folder::query()->find($parentId);
            $user = $this->user();

            if (! $parent || ! $user || ! app(DriveAccess::class)->canEdit($user, $parent)) {
                $validator->errors()->add('parent_id', 'You cannot create a folder here.');
            }
        });
    }
}
