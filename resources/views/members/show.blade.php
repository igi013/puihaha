@extends('members.layout')
@section('title', 'Member profile')
@section('content')
<a class="back" href="{{ route('members.index') }}">← Members</a>
<div class="heading"><div><h1>{{ $member->first_name }} {{ $member->last_name }}</h1><p class="muted number">{{ $member->membership_number }}</p></div><a class="button" href="{{ route('members.edit', $member) }}">Edit details</a></div>
<section class="panel"><h2>Member profile</h2><dl>
<div><dt>Email</dt><dd>{{ $member->email }}</dd></div><div><dt>Phone</dt><dd>{{ $member->phone ?: 'Not provided' }}</dd></div>
<div><dt>Category</dt><dd>{{ $member->category->name }}</dd></div><div><dt>Level</dt><dd>{{ $member->level->name }}</dd></div>
<div><dt>Status</dt><dd><span class="status {{ $member->status }}">{{ ucfirst($member->status) }}</span></dd></div><div><dt>Registered</dt><dd>{{ $member->created_at->format('M j, Y') }}</dd></div>
<div class="full"><dt>Address</dt><dd>{{ $member->address ?: 'Not provided' }}</dd></div>
</dl></section>
<section class="panel" style="margin-top:24px"><div class="heading"><div><h2>Membership details</h2><p class="muted">Validity begins when a paid application is approved.</p></div>@if($member->status === 'active')<a class="button" href="{{ route('transactions.create', ['member_id' => $member->id]) }}">New transaction</a>@endif</div>
<dl>
<div><dt>Assigned level fee</dt><dd>{{ $member->level->fee !== null ? '₱'.number_format((float)$member->level->fee, 2) : 'Not set' }}</dd></div>
<div><dt>Assigned level validity</dt><dd>{{ $member->level->validity_months ? $member->level->validity_months.' months' : 'Not set' }}</dd></div>
<div><dt>Membership joined / first approval date</dt><dd>{{ $member->membership_starts_at?->format('M j, Y') ?? 'No approved membership yet' }}</dd></div>
<div><dt>Membership valid through</dt><dd>{{ $member->membership_expires_at?->format('M j, Y') ?? 'No approved membership yet' }}</dd></div>
<div><dt>Membership coverage</dt><dd>{{ !$member->membership_expires_at ? 'Not approved' : ($member->membership_expires_at->lt(today()) ? 'Expired' : ($member->status === 'inactive' ? 'Deactivated' : 'Valid')) }}</dd></div>
<div><dt>Assigned level benefits</dt><dd>{{ $member->level->benefits ?: 'Benefits have not been configured for this level.' }}</dd></div>
@if($activeMembership)<div class="full"><dt>Benefits included in the current approved term ({{ $activeMembership->level_name }})</dt><dd>{{ $activeMembership->benefits ?: 'No benefits were configured when this term was purchased.' }}</dd></div>@endif
</dl></section>
<section class="panel" style="margin-top:24px"><div class="heading"><h2>Transaction history</h2><a href="{{ route('transactions.index', ['member_id' => $member->id]) }}">View all transactions</a></div>
<div class="table-wrap"><table><thead><tr><th>Reference / Created</th><th>Type / Level</th><th>Fee / Paid / Balance</th><th>Status</th><th>Validity</th></tr></thead><tbody>
@forelse($transactions as $transaction)
<tr><td><a class="number" href="{{ route('transactions.show', $transaction) }}">{{ $transaction->reference_number }}</a><small>{{ $transaction->created_at->format('M j, Y g:i A') }}</small></td><td>{{ $transaction->type }}<small>{{ $transaction->category_name }} / {{ $transaction->level_name }}</small></td><td>₱{{ number_format((float)$transaction->amount_due, 2) }}<small>Paid: ₱{{ number_format($transaction->paidCents()/100, 2) }}<br>Balance: ₱{{ number_format($transaction->balanceCents()/100, 2) }}</small></td><td><span class="status {{ strtolower($transaction->display_status) }}">{{ $transaction->display_status }}</span></td><td>{{ $transaction->starts_at?->format('M j, Y') ?? 'Awaiting approval' }}@if($transaction->expires_at)<small>Through {{ $transaction->expires_at->format('M j, Y') }}</small>@endif</td></tr>
@if($transaction->payments->isNotEmpty())<tr><td colspan="5"><details><summary>Payment details ({{ $transaction->payments->count() }})</summary><ul>@foreach($transaction->payments as $payment)<li><span class="number">{{ $payment->reference_number }}</span> — ₱{{ number_format((float)$payment->amount, 2) }} · {{ $payment->method }} · {{ $payment->paid_at->format('M j, Y g:i A') }}@if($payment->external_reference) · External reference: {{ $payment->external_reference }}@endif @if($payment->notes)<p>{{ $payment->notes }}</p>@endif</li>@endforeach</ul></details></td></tr>@endif
@empty<tr><td colspan="5" class="empty">No transactions recorded for this member.</td></tr>@endforelse
</tbody></table></div>
<div class="pagination"><span>{{ $transactions->total() }} transactions · Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span><div>@if($transactions->previousPageUrl())<a href="{{ $transactions->previousPageUrl() }}">Previous</a>@endif @if($transactions->nextPageUrl())<a href="{{ $transactions->nextPageUrl() }}">Next</a>@endif</div></div>
</section>
@if($member->status === 'active')
<section class="panel" style="margin-top:24px"><h2>Deactivate membership</h2><p class="muted">This marks the member as inactive and keeps their profile. You can reactivate them using Edit details.</p><form method="POST" action="{{ route('members.deactivate', $member) }}" onsubmit="return confirm('Deactivate this member? Their profile will be retained.')">@csrf @method('PATCH')<button class="secondary">Deactivate member</button></form></section>
@endif
<section class="panel" style="margin-top:24px"><h2>Delete member permanently</h2><p class="muted">This removes the member's profile from the database. This action cannot be undone. Choose deactivation if you want to keep their record.</p>
@if($member->transactions_count > 0)<p>This member has transaction history and cannot be permanently deleted. Deactivation is available above.</p>@else
<form method="POST" action="{{ route('members.destroy', $member) }}">@csrf @method('DELETE')
<label style="display:flex;align-items:flex-start;gap:12px;margin:20px 0"><input style="width:18px;min-height:18px;flex:0 0 18px;margin-top:3px" type="checkbox" name="confirm_delete" value="1" required>I confirm that I want to permanently delete {{ $member->first_name }} {{ $member->last_name }}.</label>
<button class="danger">Delete member</button></form>@endif</section>
@endsection
