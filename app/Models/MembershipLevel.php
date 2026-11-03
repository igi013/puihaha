<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipLevel extends Model
{
    protected $fillable = ['name', 'fee', 'validity_months', 'benefits'];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2', 'validity_months' => 'integer'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
