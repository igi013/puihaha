@extends('members.layout')
@php($isLevel = $kind === 'levels')
@php($label = $isLevel ? 'level' : 'category')
@section('title', ($item->exists ? 'Edit ' : 'Add ').$label)
@section('content')
<a class="back" href="{{ route($kind.'.index') }}">← {{ ucfirst($kind) }}</a>
<div class="heading"><div><h1>{{ $item->exists ? 'Edit '.$label : 'Add '.$label }}</h1><p class="muted">Required fields are marked *. Changes apply to all members assigned to this {{ $label }}.</p></div></div>
<form class="panel" method="POST" action="{{ $item->exists ? route($kind.'.update', $item) : route($kind.'.store') }}">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid"><div class="full"><label for="name">Name *</label><input id="name" name="name" required maxlength="100" value="{{ old('name', $item->name) }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>@error('name')<p class="error" id="name-error">{{ $message }}</p>@enderror</div>
@if($isLevel)
<div><label for="fee">Membership fee (PHP / ₱) *</label><input id="fee" name="fee" type="number" min="0" max="99999999.99" step="0.01" required value="{{ old('fee', $item->fee) }}" @error('fee') aria-invalid="true" aria-describedby="fee-error" @enderror>@error('fee')<p class="error" id="fee-error">{{ $message }}</p>@enderror<p class="muted" style="font-size:13px;margin-top:7px">Enter 0 for a free membership.</p></div>
<div><label for="validity_months">Validity period (months) *</label><input id="validity_months" name="validity_months" type="number" min="1" max="1200" step="1" required value="{{ old('validity_months', $item->validity_months) }}" @error('validity_months') aria-invalid="true" aria-describedby="validity-error" @enderror>@error('validity_months')<p class="error" id="validity-error">{{ $message }}</p>@enderror</div>
<div class="full"><label for="benefits">Benefits</label><textarea id="benefits" name="benefits" maxlength="5000">{{ old('benefits', $item->benefits) }}</textarea></div>
@endif</div><div class="actions"><button>{{ $item->exists ? 'Save changes' : 'Create '.$label }}</button><a class="button secondary" href="{{ $item->exists ? route($kind.'.show', $item) : route($kind.'.index') }}">Cancel</a></div></form>
@endsection
