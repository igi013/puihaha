@extends('members.layout')
@section('title', 'New transaction')
@section('content')
<a class="back" href="{{ route('transactions.index') }}">← Transactions</a>
<div class="heading"><div><h1>New transaction</h1><p class="muted">The fee and validity are taken from the member's assigned level.</p></div></div>
<form method="POST" action="{{ route('transactions.store') }}" class="panel">@csrf<div class="grid">
<div class="full"><label for="member_id">Member *</label><select id="member_id" name="member_id" required><option value="">Choose an active member</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(old('member_id', request('member_id')) == $member->id)>{{ $member->first_name }} {{ $member->last_name }} · {{ $member->membership_number }} · {{ $member->level->name }} · {{ $member->level->fee !== null ? '₱'.number_format((float)$member->level->fee,2) : 'Fee not set' }} · {{ $member->level->validity_months ? $member->level->validity_months.' months' : 'Validity not set' }}</option>@endforeach</select></div>
<div><label for="type">Transaction type *</label><select id="type" name="type" required><option value="Application" @selected(old('type') === 'Application')>Application</option><option value="Renewal" @selected(old('type') === 'Renewal')>Renewal</option></select></div>
<p class="muted">Choose Application for the first membership. Renewal requires an approved application. Early renewals extend the existing validity period.</p>
</div><div class="actions"><button>Create transaction</button><a class="button secondary" href="{{ route('transactions.index') }}">Cancel</a></div></form>
@endsection
