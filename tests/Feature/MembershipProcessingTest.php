<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipProcessingTest extends TestCase
{
    use RefreshDatabase;

    private function setupMember(): Member
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin);
        $level = MembershipLevel::firstOrFail();
        $level->update(['fee' => 5000, 'validity_months' => 12, 'benefits' => 'Test benefits']);

        return Member::register(['first_name' => 'Yuji', 'last_name' => 'Itadori', 'email' => 'yuji@gmail.jjk', 'membership_category_id' => MembershipCategory::firstOrFail()->id, 'membership_level_id' => $level->id, 'status' => 'active']);
    }

    private function application(Member $member): MembershipTransaction
    {
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Application'])->assertSessionHasNoErrors()->assertRedirect();

        return MembershipTransaction::latest('id')->firstOrFail();
    }

    private function payment(MembershipTransaction $transaction, array $details = [])
    {
        return $this->post('/transactions/'.$transaction->id.'/payments', array_replace(['amount' => '5000', 'method' => 'Cash', 'idempotency_key' => (string) Str::uuid()], $details));
    }

    public function test_application_partial_payment_full_payment_and_approval(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->startOfDay());
        $member = $this->setupMember();
        $transaction = $this->application($member);
        $this->assertSame('Pending', $transaction->status);
        $this->assertStringStartsWith('TXN-', $transaction->reference_number);
        $this->get('/transactions')->assertOk()->assertSee($transaction->reference_number);
        $this->get('/transactions/'.$transaction->id)->assertOk()->assertSee('Record payment');
        $this->post('/transactions/'.$transaction->id.'/approve')->assertSessionHasErrors('transaction');
        $this->payment($transaction, ['amount' => '2000'])->assertSessionHasNoErrors();
        $this->assertSame('Pending', $transaction->fresh()->status);
        $this->assertSame(300000, $transaction->fresh()->balanceCents());
        $this->payment($transaction, ['amount' => '3000'])->assertSessionHasNoErrors();
        $this->assertSame('Paid', $transaction->fresh()->status);
        $this->post('/transactions/'.$transaction->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame('Approved', $transaction->fresh()->status);
        $this->assertSame('2026-11-03', $member->fresh()->membership_starts_at->toDateString());
        $this->assertSame('2027-11-02', $member->fresh()->membership_expires_at->toDateString());
        $this->get('/payments')->assertOk()->assertSee(Payment::first()->reference_number);
        $this->get('/members/'.$member->id)->assertOk()
            ->assertSee('Membership details')->assertSee('Nov 3, 2026')->assertSee('Nov 2, 2027')
            ->assertSee('Test benefits')->assertSee($transaction->reference_number)->assertSee(Payment::first()->reference_number);
    }

    public function test_payment_validation_and_overpayment_are_rejected(): void
    {
        $transaction = $this->application($this->setupMember());
        $this->payment($transaction, ['amount' => 0])->assertSessionHasErrors('amount');
        $this->payment($transaction, ['amount' => -1])->assertSessionHasErrors('amount');
        $this->payment($transaction, ['amount' => '1.234'])->assertSessionHasErrors('amount');
        $this->payment($transaction, ['amount' => 5001])->assertSessionHasErrors('transaction');
        $this->payment($transaction, ['method' => 'GCash'])->assertSessionHasErrors('external_reference');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_duplicate_payment_submission_is_not_recorded_twice(): void
    {
        $transaction = $this->application($this->setupMember());
        $key = (string) Str::uuid();
        $this->payment($transaction, ['idempotency_key' => $key])->assertSessionHasNoErrors();
        $this->payment($transaction, ['idempotency_key' => $key])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->payment($transaction)->assertSessionHasErrors('transaction');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_duplicate_external_reference_is_rejected(): void
    {
        $transaction = $this->application($this->setupMember());
        $details = ['amount' => 1000, 'method' => 'GCash', 'external_reference' => 'REFERENCE-1'];
        $this->payment($transaction, $details)->assertSessionHasNoErrors();
        $this->payment($transaction, $details)->assertSessionHasErrors('transaction');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_renewal_extends_validity_and_cannot_be_approved_twice(): void
    {
        $member = $this->setupMember();
        $application = $this->application($member);
        $this->payment($application);
        $this->post('/transactions/'.$application->id.'/approve')->assertSessionHasNoErrors();
        $oldExpiry = $member->fresh()->membership_expires_at->copy();
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Renewal'])->assertSessionHasNoErrors();
        $renewal = MembershipTransaction::latest('id')->firstOrFail();
        $this->payment($renewal);
        $this->post('/transactions/'.$renewal->id.'/approve')->assertSessionHasNoErrors();
        $this->assertSame($oldExpiry->copy()->addDay()->toDateString(), $renewal->fresh()->starts_at->toDateString());
        $newExpiry = $member->fresh()->membership_expires_at->toDateString();
        $this->post('/transactions/'.$renewal->id.'/approve')->assertSessionHasErrors('transaction');
        $this->assertSame($newExpiry, $member->fresh()->membership_expires_at->toDateString());
    }

    public function test_duplicate_application_and_unfinished_transactions_are_rejected(): void
    {
        $member = $this->setupMember();
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Renewal'])->assertSessionHasErrors('transaction');
        $transaction = $this->application($member);
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Application'])->assertSessionHasErrors('transaction');
        $this->payment($transaction);
        $this->post('/transactions/'.$transaction->id.'/approve');
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Application'])->assertSessionHasErrors('transaction');
    }

    public function test_cancellation_and_paid_history_protection(): void
    {
        $member = $this->setupMember();
        $transaction = $this->application($member);
        $this->post('/transactions/'.$transaction->id.'/cancel')->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $transaction->fresh()->status);
        $this->payment($transaction)->assertSessionHasErrors('transaction');
        $this->delete('/members/'.$member->id, ['confirm_delete' => 1])->assertSessionHasErrors('member');
        $transaction = $this->application($member);
        $this->payment($transaction, ['amount' => 1000]);
        $this->post('/transactions/'.$transaction->id.'/cancel')->assertSessionHasErrors('transaction');
        $this->assertSame('Pending', $transaction->fresh()->status);
    }

    public function test_snapshot_and_expiry_are_preserved(): void
    {
        $member = $this->setupMember();
        $transaction = $this->application($member);
        $member->level->update(['fee' => 20000, 'benefits' => 'New benefits']);
        $this->assertSame('5000.00', $transaction->fresh()->amount_due);
        $this->assertSame('Test benefits', $transaction->fresh()->benefits);
        $this->payment($transaction);
        $this->post('/transactions/'.$transaction->id.'/approve');
        $this->travelTo($transaction->fresh()->expires_at->copy()->addDay());
        $this->assertSame('Expired', $transaction->fresh()->display_status);
        $this->get('/transactions?status=Expired')->assertOk()->assertSee($transaction->reference_number);
        $this->get('/transactions?status=Approved')->assertOk()->assertDontSee($transaction->reference_number);
    }

    public function test_free_membership_and_invalid_member_assignments(): void
    {
        $member = $this->setupMember();
        $member->level->update(['fee' => 0]);
        $transaction = $this->application($member);
        $this->assertSame('Paid', $transaction->status);
        $this->post('/transactions/'.$transaction->id.'/approve')->assertSessionHasNoErrors();
        $member->update(['status' => 'inactive']);
        $this->post('/transactions', ['member_id' => $member->id, 'type' => 'Renewal'])->assertSessionHasErrors('transaction');
        $this->post('/transactions', ['member_id' => 9999, 'type' => 'Application'])->assertSessionHasErrors('member_id');
    }

    public function test_tps_is_admin_only(): void
    {
        $this->get('/transactions')->assertRedirect('/login');
        $this->get('/payments')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get('/transactions')->assertForbidden();
        $this->post('/transactions', [])->assertForbidden();
        $this->get('/payments')->assertForbidden();
    }
}
