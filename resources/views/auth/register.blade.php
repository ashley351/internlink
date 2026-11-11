@extends('layouts.internlink')
@section('title', 'Get started')
@section('content')
@php($selectedRole = old('role', \App\Models\User::ROLE_STUDENT))
<div class="wrap">
    <div class="auth card">
        <span class="eyebrow">Join InternLink</span>
        <h1 style="font-size:30px;margin:8px 0">Get started</h1>
        <p class="muted">Choose the account type that best describes you. Lecturer and organisation accounts are reviewed by an administrator before access is enabled.</p>
        @if($errors->any())
            <div class="notice" style="margin-top:16px">Please check the information below and try again.</div>
        @endif
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="field">
                <label for="role">I am joining as</label>
                <select class="input" id="role" name="role" required>
                    <option value="{{ \App\Models\User::ROLE_STUDENT }}" @selected($selectedRole === \App\Models\User::ROLE_STUDENT)>Student</option>
                    <option value="{{ \App\Models\User::ROLE_SUPERVISOR }}" @selected($selectedRole === \App\Models\User::ROLE_SUPERVISOR)>University lecturer</option>
                    <option value="{{ \App\Models\User::ROLE_ORGANISATION }}" @selected($selectedRole === \App\Models\User::ROLE_ORGANISATION)>Organisation</option>
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($selectedRole === \App\Models\User::ROLE_ADMIN) @disabled(! $adminSignupEnabled)>Administrator{{ $adminSignupEnabled ? '' : ' (invitation code not configured)' }}</option>
                </select>
                @error('role')<small style="color:#b42318">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label id="name-label" for="name">{{ $selectedRole === \App\Models\User::ROLE_ORGANISATION ? 'Organisation name' : 'Full name' }}</label>
                <input class="input" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
                @error('name')<small style="color:#b42318">{{ $message }}</small>@enderror
            </div>
            <div data-role-fields="student supervisor" @if(! in_array($selectedRole, [\App\Models\User::ROLE_STUDENT, \App\Models\User::ROLE_SUPERVISOR], true)) hidden @endif>
                <div class="field">
                    <label for="university">University in Kampala</label>
                    <select class="input" id="university" name="university">
                        <option value="">Choose your university</option>
                        @foreach($universities as $university)
                            <option value="{{ $university }}" @selected(old('university') === $university)>{{ $university }}</option>
                        @endforeach
                    </select>
                    @error('university')<small style="color:#b42318">{{ $message }}</small>@enderror
                </div>
            </div>
            <div data-role-fields="student" @if($selectedRole !== \App\Models\User::ROLE_STUDENT) hidden @endif>
                <div class="field">
                    <label for="course">Course or programme</label>
                    <input class="input" id="course" name="course" value="{{ old('course') }}">
                    @error('course')<small style="color:#b42318">{{ $message }}</small>@enderror
                </div>
            </div>
            <div data-role-fields="admin" @if($selectedRole !== \App\Models\User::ROLE_ADMIN) hidden @endif>
                <div class="field">
                    <label for="admin_invitation_code">Administrator invitation code</label>
                    <input class="input" id="admin_invitation_code" name="admin_invitation_code" type="password" autocomplete="off">
                    @error('admin_invitation_code')<small style="color:#b42318">{{ $message }}</small>@enderror
                    @if(! $adminSignupEnabled)<small class="muted">Administrator signup is unavailable until an invitation code is configured.</small>@endif
                </div>
            </div>
            <div class="field">
                <label for="email">Email address</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')<small style="color:#b42318">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label for="password">Password (at least 8 characters)</label>
                <input class="input" id="password" name="password" type="password" required autocomplete="new-password">
                @error('password')<small style="color:#b42318">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>
            <button class="button" style="width:100%;margin-top:8px">Create account</button>
        </form>
        <p class="muted" style="text-align:center;margin:20px 0 0">Already registered? <a style="color:var(--green);font-weight:700" href="{{ route('login') }}">Sign in</a></p>
    </div>
</div>
@push('scripts')
<script>
    const roleSelect = document.getElementById('role');
    const nameLabel = document.getElementById('name-label');

    function updateRegistrationFields() {
        document.querySelectorAll('[data-role-fields]').forEach((section) => {
            const roles = section.dataset.roleFields.split(' ');
            const isVisible = roles.includes(roleSelect.value);
            section.hidden = !isVisible;
            section.querySelectorAll('input, select').forEach((field) => {
                field.disabled = !isVisible;
                field.required = isVisible && (field.id === 'university' || field.id === 'course' || field.id === 'admin_invitation_code');
            });
        });
        nameLabel.textContent = roleSelect.value === '{{ \App\Models\User::ROLE_ORGANISATION }}' ? 'Organisation name' : 'Full name';
    }

    roleSelect.addEventListener('change', updateRegistrationFields);
    updateRegistrationFields();
</script>
@endpush
@endsection
