<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('members')->ignore($this->route('member'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'membership_category_id' => ['required', 'integer', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'integer', 'exists:membership_levels,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
