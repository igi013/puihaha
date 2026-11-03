@extends('members.layout')
@section('title', 'Transaction details')
@section('content')
<a class="back" href="{{ route('transactions.index') }}">← Transactions</a>
<div class="heading"><div><h1>{{ $transaction->type }}</h1><p class="number muted">{{ $transaction->reference_number }}</p></div><span class="status {{ strtolower($transaction->display_status) }}">{{ $transaction->display_status }}</span></div>
<section class="panel"><h2>Transaction details</h2><dl>
<div><dt>Member</dt><dd><a href="{{ route('members.show', $transaction->member) }}">{{ $transaction->member->first_name }} {{ $transaction->member->last_name }}</a></dd></div><div><dt>Membership number</dt><dd>{{ $transaction->member->membership_number }}</dd></div>
<div><dt>Category / Level at application</dt><dd>{{ $transaction->category_name }} / {{ $transaction->level_name }}</dd></div><div><dt>Validity purchased</dt><dd>{{ $transaction->validity_months }} months</dd></div>
<div><dt>Amount due</dt><dd>₱{{ number_format((float)$transaction->amount_due, 2) }}</dd></div><div><dt>Recorded payments</dt><dd>₱{{ number_format($transaction->paidCents()/100, 2) }}</dd></div><div><dt>Balance</dt><dd>₱{{ number_format($transaction->balanceCents()/100, 2) }}</dd></div>
<div><dt>Created</dt><dd>{{ $transaction->created_at->format('M j, Y g:i A') }}</dd></div>
<div><dt>Validity starts</dt><dd>{{ $transaction->starts_at?->format('M j, Y') ?? 'Not approved yet' }}</dd></div><div><dt>Validity ends</dt><dd>{{ $transaction->expires_at?->format('M j, Y') ?? 'Not approved yet' }}</dd></div>
<div class="full"><dt>Benefits at application</dt><dd>{{ $transaction->benefits ?: 'Not set for this level' }}</dd></div>
</dl>
@if($transaction->status === 'Paid')<form class="actions" method="POST" action="{{ route('transactions.approve', $transaction) }}" onsubmit="return confirm('Approve this paid membership and apply its validity period?')">@csrf<button>Approve membership</button></form>@endif
@if($transaction->status === 'Pending' && $transaction->payments->isEmpty())<form class="actions" method="POST" action="{{ route('transactions.cancel', $transaction) }}" onsubmit="return confirm('Cancel this unpaid transaction?')">@csrf<button class="secondary">Cancel transaction</button></form>@endif
</section>
@if($transaction->status === 'Pending')
<section class="panel" style="margin-top:24px"><h2>Record payment</h2><p class="muted">Record a payment already received. This form does not charge cards, GCash or bank accounts. Partial payments are allowed; overpayments are rejected.</p>
<form method="POST" action="{{ route('payments.store', $transaction) }}">@csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $paymentKey) }}"><div class="grid">
<div><label for="amount">Amount (PHP / ₱) *</label><input id="amount" name="amount" type="number" step="0.01" min="0.01" max="{{ number_format($transaction->balanceCents()/100, 2, '.', '') }}" required value="{{ old('amount', number_format($transaction->balanceCents()/100, 2, '.', '')) }}"></div>
<div><label for="method">Method *</label><select id="method" name="method" required>@foreach(['Cash','Bank transfer','GCash','Card'] as $method)<option @selected(old('method') === $method)>{{ $method }}</option>@endforeach</select></div>
<div class="full"><label for="external_reference">External payment reference (required except for Cash)</label><input id="external_reference" name="external_reference" maxlength="100" value="{{ old('external_reference') }}"></div>
<div class="full"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="2000">{{ old('notes') }}</textarea></div></div><div class="actions"><button>Record payment</button></div></form></section>
@endif
<section class="panel" style="margin-top:24px"><h2>Payment history</h2><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>External reference</th><th>Recorded</th><th>Notes</th></tr></thead><tbody>
@forelse($transaction->payments as $payment)<tr><td class="number">{{ $payment->reference_number }}</td><td>₱{{ number_format((float)$payment->amount,2) }}</td><td>{{ $payment->method }}</td><td>{{ $payment->external_reference ?: '—' }}</td><td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td><td style="white-space:pre-wrap;overflow-wrap:anywhere;max-width:240px">{{ $payment->notes ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="empty">No payments recorded.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
