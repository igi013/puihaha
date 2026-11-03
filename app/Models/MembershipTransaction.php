<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount_due' => 'decimal:2', 'starts_at' => 'date', 'expires_at' => 'date', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->status === 'Approved' && $this->expires_at?->lt(today()) ? 'Expired' : $this->status;
    }

    public function paidCents(): int
    {
        return (int) $this->payments->sum(fn (Payment $payment) => self::cents($payment->amount));
    }

    public function balanceCents(): int
    {
        return max(0, self::cents($this->amount_due) - $this->paidCents());
    }

    public static function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
