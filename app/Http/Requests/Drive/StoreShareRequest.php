<?php

namespace App\Http\Requests\Drive;

use App\Enums\SharePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('drive.share') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'permission' => ['required', Rule::enum(SharePermission::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'No user was found with that email address.',
        ];
    }
}
