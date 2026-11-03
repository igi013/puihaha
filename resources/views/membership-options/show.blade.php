@extends('members.layout')
@php($isLevel = $kind === 'levels')
@php($label = $isLevel ? 'level' : 'category')
@section('title', $item->name)
@section('content')
<a class="back" href="{{ route($kind.'.index') }}">← {{ ucfirst($kind) }}</a>
<div class="heading"><div><h1>{{ $item->name }}</h1><p class="muted">Membership {{ $label }}</p></div><a class="button" href="{{ route($kind.'.edit', $item) }}">Edit {{ $label }}</a></div>
<section class="panel"><h2>{{ ucfirst($label) }} details</h2><dl>
<div><dt>Assigned members</dt><dd><a href="{{ route('members.index', [$isLevel ? 'level' : 'category' => $item->id]) }}">{{ $item->members_count }} {{ Str::plural('member', $item->members_count) }}</a></dd></div>
@if($isLevel)<div><dt>Membership fee (PHP)</dt><dd>{{ $item->fee !== null ? '₱'.number_format((float) $item->fee, 2) : 'Not set' }}</dd></div><div><dt>Validity period</dt><dd>{{ $item->validity_months !== null ? $item->validity_months.' '.Str::plural('month', $item->validity_months) : 'Not set' }}</dd></div><div class="full"><dt>Benefits</dt><dd>{{ $item->benefits ?: 'Not set' }}</dd></div>@endif
</dl></section>
<section class="panel" style="margin-top:24px"><h2>Delete {{ $label }}</h2>
@if($item->members_count > 0)<p class="muted">This {{ $label }} has assigned members and cannot be deleted. Reassign all members, including inactive members, first.</p>
@else<p class="muted">No members are assigned. Deleting this {{ $label }} removes it from registration options.</p><form method="POST" action="{{ route($kind.'.destroy', $item) }}" onsubmit="return confirm('Delete this {{ $label }}? This cannot be undone.')">@csrf @method('DELETE')<button class="danger">Delete {{ $label }}</button></form>@endif
</section>
@endsection
