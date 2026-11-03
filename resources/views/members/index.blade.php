@extends('members.layout')
@section('title', 'Members')
@section('content')
<div class="heading"><div><h1>Members</h1><p class="muted">Register members and keep their details up to date.</p></div><a class="button" href="{{ route('members.create') }}">Add member</a></div>
<form class="filters" method="GET" action="{{ route('members.index') }}">
    <div><label for="q">Name or membership number</label><input id="q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Search members"></div>
    <div><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
    <div><label for="level">Level</label><select id="level" name="level"><option value="">All levels</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected(request('level') == $level->id)>{{ $level->name }}</option>@endforeach</select></div>
    <div><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
    <button>Search</button><a class="button secondary" href="{{ route('members.index') }}">Reset</a>
</form>
<div class="panel"><div class="table-wrap"><table><thead><tr><th>Member</th><th>Membership number</th><th>Category</th><th>Level</th><th>Status</th><th>Profile</th></tr></thead><tbody>
@forelse($members as $member)
<tr><td><strong>{{ $member->first_name }} {{ $member->last_name }}</strong><small>{{ $member->email }}</small></td><td class="number">{{ $member->membership_number }}</td><td>{{ $member->category->name }}</td><td>{{ $member->level->name }}</td><td><span class="status {{ $member->status }}">{{ ucfirst($member->status) }}</span></td><td><a href="{{ route('members.show', $member) }}">View</a></td></tr>
@empty<tr><td colspan="6" class="empty">No members found. Add a member or adjust your search.</td></tr>@endforelse
</tbody></table></div>
<div class="pagination"><span class="muted">{{ $members->total() }} {{ Str::plural('member', $members->total()) }} · Page {{ $members->currentPage() }} of {{ $members->lastPage() }}</span><div>@if($members->previousPageUrl())<a href="{{ $members->previousPageUrl() }}">Previous</a>@endif @if($members->nextPageUrl())<a href="{{ $members->nextPageUrl() }}">Next</a>@endif</div></div></div>
@endsection
