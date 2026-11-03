@extends('members.layout')
@section('title', 'Payments')
@section('content')
<div class="heading"><div><h1>Payments</h1><p class="muted">Payment records linked to membership transactions.</p></div><a class="button" href="{{ route('transactions.index', ['status' => 'Pending']) }}">Find transaction to pay</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Payment reference</th><th>Member</th><th>Transaction</th><th>Amount</th><th>Method</th><th>Recorded</th></tr></thead><tbody>
@forelse($payments as $payment)<tr><td class="number">{{ $payment->reference_number }}</td><td>{{ $payment->transaction->member->first_name }} {{ $payment->transaction->member->last_name }}</td><td class="number"><a href="{{ route('transactions.show', $payment->transaction) }}">{{ $payment->transaction->reference_number }}</a></td><td>₱{{ number_format((float)$payment->amount,2) }}</td><td>{{ $payment->method }}</td><td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td></tr>@empty<tr><td colspan="6" class="empty">No payments recorded.</td></tr>@endforelse
</tbody></table></div><div class="pagination"><span>{{ $payments->total() }} payments · Page {{ $payments->currentPage() }} of {{ $payments->lastPage() }}</span><div>@if($payments->previousPageUrl())<a href="{{ $payments->previousPageUrl() }}">Previous</a>@endif @if($payments->nextPageUrl())<a href="{{ $payments->nextPageUrl() }}">Next</a>@endif</div></div></section>
@endsection
