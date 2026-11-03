@extends('members.layout')
@section('title', 'Transactions')
@section('content')
<div class="heading"><div><h1>Transactions</h1><p class="muted">Membership applications, renewals and their payment status.</p></div><a class="button" href="{{ route('transactions.create') }}">New transaction</a></div>
<form method="GET" action="{{ route('transactions.index') }}" class="actions" style="margin:0 0 24px;align-items:flex-end">
@if(request('member_id'))<input type="hidden" name="member_id" value="{{ request('member_id') }}">@endif
<div><label for="status">Status</label><select name="status" id="status" style="height:46px"><option value="">All statuses</option>@foreach(['Pending','Paid','Approved','Expired','Cancelled'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div><button style="height:46px">Filter</button><a href="{{ route('transactions.index') }}" style="display:inline-flex;align-items:center;min-height:46px">Reset</a></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Member</th><th>Type / Level</th><th>Amount due</th><th>Status</th><th>Created</th></tr></thead><tbody>
@forelse($transactions as $transaction)<tr><td class="number"><a href="{{ route('transactions.show', $transaction) }}">{{ $transaction->reference_number }}</a></td><td><a href="{{ route('members.show', $transaction->member) }}">{{ $transaction->member->first_name }} {{ $transaction->member->last_name }}</a></td><td>{{ $transaction->type }}<small>{{ $transaction->level_name }}</small></td><td>₱{{ number_format((float)$transaction->amount_due, 2) }}</td><td><span class="status {{ strtolower($transaction->display_status) }}">{{ $transaction->display_status }}</span></td><td>{{ $transaction->created_at->format('M j, Y') }}</td></tr>
@empty<tr><td colspan="6" class="empty">No transactions found.</td></tr>@endforelse
</tbody></table></div>
<div class="pagination"><span>{{ $transactions->total() }} transactions · Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span><div>@if($transactions->previousPageUrl())<a href="{{ $transactions->previousPageUrl() }}">Previous</a>@endif @if($transactions->nextPageUrl())<a href="{{ $transactions->nextPageUrl() }}">Next</a>@endif</div></div></section>
@endsection
