<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('membership_levels')->ignore($this->route('level'))],
            'fee' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'validity_months' => ['required', 'integer', 'min:1', 'max:1200'],
            'benefits' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
