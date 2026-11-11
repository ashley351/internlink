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
        <p class="muted registration-intro">Choose how you’re joining. Student registration creates your placement profile. Employer accounts are listed as Organisations and may require administrator approval.</p>
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
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected($selectedRole === \App\Models\User::ROLE_ADMIN) @disabled(! $adminSignupEnabled)>Administrator{{ $adminSignupEnabled ? '' : ' (invitation code not configured)' }}</option>
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

            <section class="registration-section" data-role-fields="student" @if($selectedRole !== \App\Models\User::ROLE_STUDENT) hidden @endif>
                <h2>Academic information</h2>
                <p>These details help connect your studies with suitable placement opportunities.</p>
                <div class="registration-fields">
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
                    <div class="field">
                        <label for="campus">Campus</label>
                        <input class="input" id="campus" name="campus" value="{{ old('campus') }}">
                        @error('campus')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="faculty">Faculty or school</label>
                        <input class="input" id="faculty" name="faculty" value="{{ old('faculty') }}">
                        @error('faculty')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="course">Course or academic programme</label>
                        <input class="input" id="course" name="course" value="{{ old('course') }}">
                        @error('course')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="year_of_study">Year of study</label>
                        <select class="input" id="year_of_study" name="year_of_study">
                            <option value="">Choose your year</option>
                            @foreach(range(1, 12) as $year)
                                <option value="{{ $year }}" @selected((string) old('year_of_study') === (string) $year)>Year {{ $year }}</option>
                            @endforeach
                        </select>
                        @error('year_of_study')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="field">
                        <label for="student_registration_number">Student registration number</label>
                        <input class="input" id="student_registration_number" name="student_registration_number" value="{{ old('student_registration_number') }}">
                        @error('student_registration_number')<small class="registration-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </section>

            <section class="registration-section" data-role-fields="supervisor" @if($selectedRole !== \App\Models\User::ROLE_SUPERVISOR) hidden @endif>
                <div class="field">
                    <label for="university-supervisor">University in Kampala</label>
                    <input class="input" id="university-supervisor" name="university" type="text" list="supervisor-institution-options" value="{{ old('university') }}" placeholder="Type to search institutions" autocomplete="off">
                    <datalist id="supervisor-institution-options">
                        @foreach($institutions as $institution)
                            <option value="{{ $institution->name }}"></option>
                        @endforeach
                    </datalist>
                    @error('university')<small class="registration-error">{{ $message }}</small>@enderror
                </div>
            </section>

            <section class="registration-section" data-role-fields="student" @if($selectedRole !== \App\Models\User::ROLE_STUDENT) hidden @endif>
                <h2>Application information</h2>
                <p>Upload an application letter—not a CV. Accepted formats: PDF, DOC, DOCX or ODT, up to 5 MB.</p>
                <div class="field">
                    <label for="academic_interests">Academic interests</label>
                    <textarea class="input" id="academic_interests" name="academic_interests" rows="4" maxlength="2000" placeholder="Tell us about the subjects or areas you are interested in">{{ old('academic_interests') }}</textarea>
                    @error('academic_interests')<small class="registration-error">{{ $message }}</small>@enderror
                </div>
                <div class="field">
                    <label for="application_letter">Application letter</label>
                    <input class="input" id="application_letter" name="application_letter" type="file" accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text">
                    <small class="muted">Your document is stored privately and is only accessible from your account.</small>
                    @error('application_letter')<small class="registration-error">{{ $message }}</small>@enderror
                </div>
            </section>

            <div data-role-fields="admin" @if($selectedRole !== \App\Models\User::ROLE_ADMIN) hidden @endif>
                <div class="field">
                    <label for="admin_invitation_code">Administrator invitation code</label>
                    <input class="input" id="admin_invitation_code" name="admin_invitation_code" type="password" autocomplete="off">
                    @error('admin_invitation_code')<small class="registration-error">{{ $message }}</small>@enderror
                    @if(! $adminSignupEnabled)<small class="muted">Administrator signup is unavailable until an invitation code is configured.</small>@endif
                </div>
            </div>
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
                field.required = isVisible && ['university', 'university-supervisor', 'course', 'phone', 'campus', 'faculty', 'year_of_study', 'student_registration_number', 'academic_interests', 'application_letter', 'admin_invitation_code'].includes(field.id);
            });
        });
        nameLabel.textContent = roleSelect.value === '{{ \App\Models\User::ROLE_ORGANISATION }}' ? 'Organisation name' : 'Full name';
    }

    roleSelect.addEventListener('change', updateRegistrationFields);
    updateRegistrationFields();
</script>
@endpush
@endsection
