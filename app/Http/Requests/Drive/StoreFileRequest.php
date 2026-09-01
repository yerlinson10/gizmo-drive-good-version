<?php

namespace App\Http\Requests\Drive;

use App\Models\Folder;
use App\Services\Drive\DriveAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFileRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:51200'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $folderId = $this->integer('folder_id') ?: null;

            if (! $folderId) {
                return;
            }

            $folder = Folder::query()->find($folderId);
            $user = $this->user();

            if (! $folder || ! $user || ! app(DriveAccess::class)->canEdit($user, $folder)) {
                $validator->errors()->add('folder_id', 'You cannot upload files here.');
            }
        });
    }
}
