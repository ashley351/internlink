@extends('layouts.internlink')
@section('title', 'Sign in')
@section('content')
<div class="wrap"><div class="auth card"><span class="eyebrow">Welcome back</span><h1 style="font-size:30px;margin:8px 0">Sign in to InternLink</h1><p class="muted">Students, university lecturers and organisation staff all sign in here with their own email and password. Lecturer and organisation accounts are provided by an administrator.</p>
@if($errors->any())<div class="notice" style="margin-top:16px">{{ $errors->first() }}</div>@endif
<form id="login-form" method="POST" action="{{ route('login.store') }}">@csrf<div class="field"><label for="email">Email address</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div><div class="field"><label for="password">Password</label><input class="input" id="password" name="password" type="password" required autocomplete="current-password"></div><label class="muted" style="font-size:13px"><input type="checkbox" name="remember"> Remember me</label><button class="button" style="width:100%;margin-top:18px">Sign in</button></form>
<div style="margin-top:20px;border-top:1px solid var(--line);padding-top:18px">
    <p class="muted" style="margin:0 0 12px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em">Demo accounts</p>
    <div style="display:grid;gap:10px">
        <button type="button" class="button light" data-demo-login="student@internlink.test" data-demo-password="password" style="justify-content:space-between;gap:16px;padding:12px 14px;width:100%"><span>Student</span><small>student@internlink.test</small></button>
        <button type="button" class="button light" data-demo-login="supervisor@internlink.test" data-demo-password="password" style="justify-content:space-between;gap:16px;padding:12px 14px;width:100%"><span>University lecturer / supervisor</span><small>supervisor@internlink.test</small></button>
        <button type="button" class="button light" data-demo-login="employer@internlink.test" data-demo-password="password" style="justify-content:space-between;gap:16px;padding:12px 14px;width:100%"><span>Organisation staff</span><small>employer@internlink.test</small></button>
        <button type="button" class="button light" data-demo-login="admin@internlink.test" data-demo-password="password" style="justify-content:space-between;gap:16px;padding:12px 14px;width:100%"><span>Administrator</span><small>admin@internlink.test</small></button>
    </div>
</div>
<p class="muted" style="text-align:center;margin:20px 0 0">New to InternLink? <a style="color:var(--green);font-weight:700" href="{{ route('register') }}">Create a student account</a></p></div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-demo-login]').forEach(function (button) {
        button.addEventListener('click', function () {
            const form = document.getElementById('login-form');
            if (! form) return;
            form.email.value = button.dataset.demoLogin;
            form.password.value = button.dataset.demoPassword;
            form.submit();
        });
    });
});
</script>
@endsection
