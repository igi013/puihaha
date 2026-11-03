@extends('members.layout')
@section('title', 'Administrator sign in')
@section('content')
<section class="panel login"><h1>Administrator sign in</h1><p class="muted">Sign in to manage member records.</p><form method="POST" action="{{ route('login.store') }}">@csrf
<div class="field"><label for="email">Username or email</label><input id="email" name="email" type="text" value="{{ old('email') }}" required autocomplete="username" autofocus></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
<div class="actions"><button style="width:100%">Sign in</button></div>
</form></section>
@endsection
