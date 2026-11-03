@extends('members.layout')
@section('title', $member->exists ? 'Edit member' : 'Register member')
@section('content')
<a class="back" href="{{ route('members.index') }}">← Members</a>
<div class="heading"><div><h1>{{ $member->exists ? 'Edit member' : 'Register member' }}</h1><p class="muted">A membership number is generated automatically. Required fields are marked *.</p></div></div>
<form class="panel" method="POST" action="{{ $member->exists ? route('members.update', $member) : route('members.store') }}">
@csrf @if($member->exists) @method('PUT') @endif
<div class="grid">
@foreach(['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'phone' => 'Phone'] as $field => $label)
<div><label for="{{ $field }}">{{ $label }}{{ $field !== 'phone' ? ' *' : '' }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" value="{{ old($field, $member->$field) }}" maxlength="{{ $field === 'email' ? 255 : ($field === 'phone' ? 30 : 100) }}" @required($field !== 'phone') @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>@error($field)<p class="error" id="{{ $field }}-error">{{ $message }}</p>@enderror</div>
@endforeach
<div><label for="membership_category_id">Category *</label><select id="membership_category_id" name="membership_category_id" required><option value="">Choose a category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('membership_category_id', $member->membership_category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
<div><label for="membership_level_id">Level *</label><select id="membership_level_id" name="membership_level_id" required><option value="">Choose a level</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected(old('membership_level_id', $member->membership_level_id) == $level->id)>{{ $level->name }}</option>@endforeach</select></div>
<div class="full"><label for="address">Address</label><textarea id="address" name="address" maxlength="2000">{{ old('address', $member->address) }}</textarea></div>
<div><label for="member-status">Status *</label><select id="member-status" name="status" required><option value="active" @selected(old('status', $member->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $member->status) === 'inactive')>Inactive</option></select></div>
</div><div class="actions"><button>{{ $member->exists ? 'Save changes' : 'Register member' }}</button><a class="button secondary" href="{{ $member->exists ? route('members.show', $member) : route('members.index') }}">Cancel</a></div>
</form>
@endsection
