@extends('members.layout')
@php($isLevel = $kind === 'levels')
@section('title', $isLevel ? 'Membership levels' : 'Membership categories')
@section('content')
<div class="heading"><div><h1>{{ $isLevel ? 'Membership levels' : 'Membership categories' }}</h1><p class="muted">{{ $isLevel ? 'Set membership fees, validity periods and benefits.' : 'Manage the categories available when registering members.' }}</p></div><a class="button" href="{{ route($kind.'.create') }}">{{ $isLevel ? 'Add level' : 'Add category' }}</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Name</th>@if($isLevel)<th>Fee</th><th>Validity</th><th>Benefits</th>@endif<th>Members</th><th>Details</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td><strong>{{ $item->name }}</strong></td>@if($isLevel)<td>{{ $item->fee !== null ? '₱'.number_format((float) $item->fee, 2) : 'Not set' }}</td><td>{{ $item->validity_months !== null ? $item->validity_months.' '.Str::plural('month', $item->validity_months) : 'Not set' }}</td><td style="max-width:300px;overflow-wrap:anywhere">{{ $item->benefits ? Str::limit($item->benefits, 100) : 'Not set' }}</td>@endif<td>{{ $item->members_count }}</td><td><a href="{{ route($kind.'.show', $item) }}">View</a></td></tr>
@empty<tr><td colspan="{{ $isLevel ? 6 : 3 }}" class="empty">{{ $isLevel ? 'No membership levels yet.' : 'No membership categories yet.' }}</td></tr>@endforelse
</tbody></table></div><div class="pagination"><span class="muted">{{ $items->total() }} {{ $isLevel ? Str::plural('level', $items->total()) : Str::plural('category', $items->total()) }} · Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span><div>@if($items->previousPageUrl())<a href="{{ $items->previousPageUrl() }}">Previous</a>@endif @if($items->nextPageUrl())<a href="{{ $items->nextPageUrl() }}">Next</a>@endif</div></div></section>
@endsection
