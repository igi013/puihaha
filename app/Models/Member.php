<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Member extends Model
{
    protected $fillable = ['membership_number', 'first_name', 'last_name', 'email', 'phone', 'address', 'membership_category_id', 'membership_level_id', 'status'];

    public static function register(array $details): self
    {
        return DB::transaction(function () use ($details) {
            $details['membership_number'] = 'PENDING-'.Str::ulid();
            $member = static::create($details);
            if ($member->id > 999999) {
                throw new \RuntimeException('The six-digit membership number range is exhausted.');
            }
            $member->update(['membership_number' => 'GRADE-'.str_pad((string) $member->id, 6, '0', STR_PAD_LEFT)]);

            return $member;
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    protected function casts(): array
    {
        return ['membership_starts_at' => 'date', 'membership_expires_at' => 'date'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }
}
