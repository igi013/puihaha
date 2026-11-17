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
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'location_consent' => [Rule::when($this->filled('latitude') || $this->filled('longitude'), ['required', 'accepted'], ['nullable', 'boolean'])],
            'membership_category_id' => ['required', 'integer', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'integer', 'exists:membership_levels,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function memberDetails(): array
    {
        $details = $this->safe()->except('location_consent');
        if ($this->hasAny(['latitude', 'longitude'])) {
            $member = $this->route('member');
            $unchanged = $member && (float) $member->latitude === (float) $this->input('latitude') && (float) $member->longitude === (float) $this->input('longitude');
            $details['location_consented_at'] = $this->filled('latitude') && $this->filled('longitude') ? ($unchanged ? ($member->location_consented_at ?? now()) : now()) : null;
        }

        return $details;
    }
}
