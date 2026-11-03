<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipProcessing
{
    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['transaction' => $message]);
        }
    }

    public function create(int $memberId, string $type, int $adminId): MembershipTransaction
    {
        return DB::transaction(function () use ($memberId, $type, $adminId) {
            $member = Member::lockForUpdate()->findOrFail($memberId);
            $this->require($member->status === 'active', 'Reactivate this member before creating a transaction.');
            $this->require(! $member->transactions()->whereIn('status', ['Pending', 'Paid'])->exists(), 'This member already has an unfinished transaction. Complete or cancel it first.');
            $hasApplication = $member->transactions()->where('type', 'Application')->whereNotNull('approved_at')->exists();
            $this->require($type === 'Renewal' ? $hasApplication : ! $hasApplication, $type === 'Renewal' ? 'Approve an initial application before renewing.' : 'This member already has an approved application. Choose renewal.');
            $level = $member->level()->lockForUpdate()->firstOrFail();
            $category = $member->category()->firstOrFail();
            $this->require($level->fee !== null && $level->validity_months > 0, 'Set the membership level fee and validity period first.');

            return MembershipTransaction::create([
                'reference_number' => 'TXN-'.Str::ulid(), 'member_id' => $member->id,
                'membership_category_id' => $category->id, 'membership_level_id' => $level->id,
                'category_name' => $category->name, 'level_name' => $level->name, 'benefits' => $level->benefits,
                'type' => $type, 'status' => MembershipTransaction::cents($level->fee) === 0 ? 'Paid' : 'Pending',
                'amount_due' => $level->fee, 'validity_months' => $level->validity_months, 'created_by' => $adminId,
            ]);
        });
    }

    public function pay(MembershipTransaction $transaction, array $details, int $adminId): void
    {
        DB::transaction(function () use ($transaction, $details, $adminId) {
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            $existing = Payment::where('idempotency_key', $details['idempotency_key'])->first();
            if ($existing) {
                $this->require($existing->membership_transaction_id === $transaction->id, 'This payment submission belongs to another transaction.');
                $this->require(MembershipTransaction::cents($existing->amount) === MembershipTransaction::cents((string) $details['amount']) && $existing->method === $details['method'], 'This payment submission has already been recorded with different details. Reload the transaction first.');

                return;
            }
            $this->require($transaction->status === 'Pending', 'Payments can only be recorded for pending transactions.');
            $transaction->load('payments');
            $balance = $transaction->balanceCents();
            $cents = MembershipTransaction::cents((string) $details['amount']);
            $this->require($cents > 0 && $cents <= $balance, 'Payment must be greater than zero and must not exceed the outstanding balance.');
            if (! empty($details['external_reference'])) {
                $this->require(! Payment::where('external_reference', $details['external_reference'])->exists(), 'This external payment reference has already been recorded.');
            }
            $transaction->payments()->create($details + [
                'reference_number' => 'PAY-'.Str::ulid(), 'recorded_by' => $adminId, 'paid_at' => now(),
            ]);
            if ($cents === $balance) {
                $transaction->update(['status' => 'Paid']);
            }
        });
    }

    public function approve(MembershipTransaction $transaction, int $adminId): void
    {
        DB::transaction(function () use ($transaction, $adminId) {
            // All membership-changing operations acquire the member lock first.
            $member = Member::lockForUpdate()->findOrFail($transaction->member_id);
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            $transaction->load('payments');
            $this->require($transaction->status === 'Paid' && $transaction->balanceCents() === 0, 'Only fully paid transactions may be approved.');
            $this->require($member->status === 'active', 'Reactivate the member before approving.');
            $start = today();
            if ($transaction->type === 'Renewal' && $member->membership_expires_at?->gte(today())) {
                $start = $member->membership_expires_at->copy()->addDay();
            }
            $expiry = $start->copy()->addMonthsNoOverflow($transaction->validity_months)->subDay();
            $transaction->update(['status' => 'Approved', 'starts_at' => $start, 'expires_at' => $expiry, 'approved_at' => now(), 'approved_by' => $adminId]);
            $member->forceFill([
                'membership_starts_at' => $member->membership_starts_at ?? $start,
                'membership_expires_at' => $expiry,
                'membership_category_id' => $transaction->membership_category_id,
                'membership_level_id' => $transaction->membership_level_id,
            ])->save();
        });
    }

    public function cancel(MembershipTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction = MembershipTransaction::lockForUpdate()->findOrFail($transaction->id);
            $this->require($transaction->status === 'Pending' && ! $transaction->payments()->exists(), 'Only pending transactions without recorded payments can be cancelled.');
            $transaction->update(['status' => 'Cancelled', 'cancelled_at' => now()]);
        });
    }
}
