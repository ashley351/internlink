@extends('layouts.internlink')
@section('title', 'Get started')
@push('head')
<style>
    .registration-card{max-width:760px;margin:34px auto 64px;padding:clamp(22px,4vw,40px)}
    .registration-card h1{font-size:clamp(28px,5vw,36px);margin:8px 0}
    .registration-intro{max-width:620px}
    .registration-section{margin-top:27px;padding-top:24px;border-top:1px solid var(--line)}
    .registration-section h2{margin:0 0 5px;font:700 19px Manrope,sans-serif}
    .registration-section>p{margin:0 0 17px;color:var(--muted);font-size:14px;line-height:1.6}
    .registration-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px}
    .registration-fields .field{min-width:0}
    .registration-fields .field.full-width{grid-column:1/-1}
    .registration-card [hidden]{display:none!important}
    .registration-error{display:block;margin-top:5px;color:#b42318}
    @media(max-width:600px){.registration-card{margin:20px auto 42px}.registration-fields{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
@php($selectedRole = old('role', \App\Models\User::ROLE_STUDENT))
<div class="wrap">
    <div class="auth card registration-card">
        <span class="eyebrow">Join InternLink</span>
        <h1>Get started</h1>
        <p class="muted registration-intro">Choose how you’re joining. Student registration creates your placement profile. Organisation and lecturer registrations require administrator approval before access is enabled.</p>
        @if($errors->any())
            <div class="notice" style="margin-top:16px">Please check the information below and try again.</div>
        @endif
        <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label for="role">I am joining as</label>
                <select class="input" id="role" name="role" required>
                    <option value="{{ \App\Models\User::ROLE_STUDENT }}" @selected($selectedRole === \App\Models\User::ROLE_STUDENT)>Student</option>
                    <option value="{{ \App\Models\User::ROLE_SUPERVISOR }}" @selected($selectedRole === \App\Models\User::ROLE_SUPERVISOR)>University lecturer</option>
                    <option value="{{ \App\Models\User::ROLE_ORGANISATION }}" @selected($selectedRole === \App\Models\User::ROLE_ORGANISATION)>Employer / Organisation</option>
                </select>
                @error('role')<small class="registration-error">{{ $message }}</small>@enderror
            </div>
            <section class="registration-section">
                <h2>Personal information</h2>
                <p>Use your full name and an email address you check regularly.</p>
                <div class="registration-fields">
                    <div class="field">
                        <label id="name-label" for="name">{{ $selectedRole === \App\Models\User::ROLE_ORGANISATION ? 'Organisation name' : 'Full name' }}</label>
                        <input class="input" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div data-role-fields="student" class="field full-width" @if($selectedRole !== \App\Models\User::ROLE_STUDENT) hidden @endif>
                        <label for="phone">Telephone number</label>
                        <input class="input" id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="Include your country code where possible">
                        @error('phone')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="password">Password (at least 8 characters)</label>
                        <input class="input" id="password" name="password" type="password" required autocomplete="new-password">
                        @error('password')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>
            </section>

            <section class="registration-section" data-role-fields="student supervisor" @if(! in_array($selectedRole, [\App\Models\User::ROLE_STUDENT, \App\Models\User::ROLE_SUPERVISOR], true)) hidden @endif>
                <div class="field">
                    <label for="university">University or institution</label>
                    <input class="input" id="university" name="university" type="text" list="institution-options" value="{{ old('university') }}" placeholder="Type to search institutions" autocomplete="off">
                    <datalist id="institution-options">
                        @foreach($institutions as $institution)
                            <option value="{{ $institution->name }}"></option>
                        @endforeach
                    </datalist>
                    <small class="muted">Start typing to filter the A–Z institution list, then select a result.</small>
                    @error('university')<small class="registration-error">{{ $message }}</small>@enderror
                </div>
            </section>

            <section class="registration-section" data-role-fields="organisation" @if($selectedRole !== \App\Models\User::ROLE_ORGANISATION) hidden @endif>
                <h2>Organisation profile</h2>
                <p>These details help students and administrators identify your organisation.</p>
                <div class="registration-fields">
                    <div class="field">
                        <label for="organisation_phone">Contact telephone <span class="muted">(optional)</span></label>
                        <input class="input" id="organisation_phone" name="organisation_phone" type="tel" value="{{ old('organisation_phone') }}" maxlength="30">
                    </div>
                    <div class="field">
                        <label for="organisation_website">Website <span class="muted">(optional)</span></label>
                        <input class="input" id="organisation_website" name="organisation_website" type="url" value="{{ old('organisation_website') }}" maxlength="255" placeholder="https://">
                    </div>
                    <div class="field full-width">
                        <label for="organisation_description">About the organisation <span class="muted">(optional)</span></label>
                        <textarea class="input" id="organisation_description" name="organisation_description" rows="3" maxlength="5000">{{ old('organisation_description') }}</textarea>
                    </div>
                </div>
            </section>
            <button class="button" style="width:100%;margin-top:20px">Create account</button>
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
            section.querySelectorAll('input, select, textarea').forEach((field) => {
                field.disabled = !isVisible;
                field.required = isVisible && ['university', 'phone'].includes(field.id);
            });
        });
        nameLabel.textContent = roleSelect.value === '{{ \App\Models\User::ROLE_ORGANISATION }}' ? 'Organisation name' : 'Full name';
    }

    roleSelect.addEventListener('change', updateRegistrationFields);
    updateRegistrationFields();
</script>
@endpush
@endsection
