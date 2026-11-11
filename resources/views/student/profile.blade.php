@extends('layouts.internlink')
@section('title', 'My student profile')
@push('head')
<style>
    .student-profile{max-width:900px;margin:34px auto 64px}
    .profile-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}
    .profile-heading h1{margin:7px 0;font-size:clamp(28px,5vw,36px)}
    .profile-progress{width:min(270px,100%);padding:16px 18px;border:1px solid var(--line);border-radius:14px;background:#fff}
    .profile-progress .row{margin-bottom:10px}
    .profile-progress-bar{display:block;width:100%;height:8px;overflow:hidden;border:0;border-radius:20px;background:#e8efed;accent-color:var(--green)}
    .profile-progress-bar::-webkit-progress-bar{border-radius:20px;background:#e8efed}
    .profile-progress-bar::-webkit-progress-value{border-radius:20px;background:var(--green)}
    .profile-section{margin-top:20px;padding:clamp(20px,4vw,30px)}
    .profile-section h2{margin:0 0 6px;font-size:20px}
    .profile-section>p{margin:0 0 20px;color:var(--muted);line-height:1.6}
    .profile-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px}
    .profile-fields .field{min-width:0}
    .profile-fields .field.full-width{grid-column:1/-1}
    .profile-errors{margin-bottom:18px}
    .profile-errors ul{margin:8px 0 0;padding-left:20px}
    .profile-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:22px}
    .document-note{display:flex;align-items:center;gap:12px;margin-bottom:14px;padding:13px 15px;border:1px solid var(--line);border-radius:11px;background:#f8faf9}
    .document-note p{margin:0;color:var(--muted);font-size:14px}
    @media(max-width:680px){.student-profile{margin:22px auto 45px}.profile-heading{align-items:flex-start;flex-direction:column}.profile-fields{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="wrap student-profile">
    <div class="profile-heading">
        <div><span class="eyebrow">Student workspace</span><h1>My profile</h1><p class="muted">Keep your personal and academic details up to date.</p></div>
        <div class="profile-progress" aria-label="Profile completion: {{ $profileCompletion }} percent">
            <div class="row"><b>Profile completion</b><strong>{{ $profileCompletion }}%</strong></div>
            <progress class="profile-progress-bar" max="100" value="{{ $profileCompletion }}">{{ $profileCompletion }}%</progress>
        </div>
    </div>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice profile-errors" role="alert"><b>Please check the information below.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <section class="card profile-section">
            <h2>Personal information</h2><p>Your basic contact details and account credentials.</p>
            <div class="profile-fields">
                <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"></div>
                <div class="field"><label for="email">Email address</label><input class="input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"></div>
                <div class="field"><label for="phone">Telephone number</label><input class="input" id="phone" name="phone" type="tel" value="{{ old('phone', $profile?->phone) }}" required autocomplete="tel"></div>
                <div class="field"><label for="password">Change password <span class="muted">(optional)</span></label><input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="8"><small class="muted">Leave blank to keep your current password.</small></div>
                <div class="field"><label for="password_confirmation">Confirm new password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
            </div>
        </section>
        <section class="card profile-section">
            <h2>Academic information</h2><p>Your institution and programme details.</p>
            <div class="profile-fields">
                <div class="field"><label for="university">University or institution</label><select class="input" id="university" name="university" required><option value="">Choose your institution</option>@foreach(config('internlink.universities') as $university)<option value="{{ $university }}" @selected(old('university', $user->university) === $university)>{{ $university }}</option>@endforeach</select></div>
                <div class="field"><label for="campus">Campus</label><input class="input" id="campus" name="campus" value="{{ old('campus', $profile?->campus) }}" required></div>
                <div class="field"><label for="faculty">Faculty or school</label><input class="input" id="faculty" name="faculty" value="{{ old('faculty', $profile?->faculty) }}" required></div>
                <div class="field"><label for="course">Course or academic programme</label><input class="input" id="course" name="course" value="{{ old('course', $user->course) }}" required></div>
                <div class="field"><label for="year_of_study">Year of study</label><select class="input" id="year_of_study" name="year_of_study" required><option value="">Choose your year</option>@foreach(range(1, 12) as $year)<option value="{{ $year }}" @selected((string) old('year_of_study', $profile?->year_of_study) === (string) $year)>Year {{ $year }}</option>@endforeach</select></div>
                <div class="field"><label for="student_registration_number">Student registration number</label><input class="input" id="student_registration_number" name="student_registration_number" value="{{ old('student_registration_number', $profile?->student_registration_number) }}" required></div>
            </div>
        </section>
        <section class="card profile-section">
            <h2>Application information</h2><p>Share your interests and maintain your application letter.</p>
            <div class="field"><label for="academic_interests">Academic interests</label><textarea class="input" id="academic_interests" name="academic_interests" rows="4" maxlength="2000" required>{{ old('academic_interests', $profile?->academic_interests) }}</textarea></div>
            @if($profile?->application_letter_path)
                <div class="document-note"><span class="tag">Uploaded</span><p>Your application letter is stored privately. <a href="{{ route('student.profile.application-letter') }}" style="color:var(--green);font-weight:700">Download your current letter</a></p></div>
            @endif
            <div class="field"><label for="application_letter">{{ $profile?->application_letter_path ? 'Replace application letter' : 'Application letter' }}</label><input class="input" id="application_letter" name="application_letter" type="file" accept=".pdf,.doc,.docx,.odt,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text" @required(blank($profile?->application_letter_path))><small class="muted">PDF, DOC, DOCX or ODT; maximum 5 MB. Do not upload a CV in place of your application letter.</small></div>
        </section>
        <div class="profile-actions"><button class="button" type="submit">Save profile</button><a class="button light" href="{{ route('dashboard.student') }}">Back to student dashboard</a></div>
    </form>
</div>
@endsection
