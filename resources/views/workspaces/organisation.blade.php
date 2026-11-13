@extends('layouts.internlink')
@section('title', 'Organisation workspace')
@push('head')
<style>
    .organisation-workspace{padding-top:34px;padding-bottom:60px}
    .workspace-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:22px}
    .workspace-heading h1{margin:5px 0;font-size:clamp(28px,5vw,36px)}
    .workspace-heading p{margin:0}
    .organisation-sections{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;align-items:start}
    .organisation-sections .card{min-width:0}
    .organisation-list{display:grid;gap:14px}
    .organisation-listing{padding:16px 0;border-bottom:1px solid var(--line)}
    .organisation-listing:last-child{border-bottom:0}
    .listing-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px}
    .listing-edit{margin-top:12px;padding:14px;border:1px solid var(--line);border-radius:12px;background:#f8faf9}
    .listing-edit[open]{margin-bottom:6px}
    .profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 14px}
    .profile-grid .full-width{grid-column:1/-1}
    .organisation-table{margin-top:18px}
    .organisation-table h2{margin-top:5px}
    @media(max-width:760px){.organisation-sections{grid-template-columns:1fr}}
    @media(max-width:560px){.organisation-workspace{padding-top:22px}.workspace-heading{align-items:flex-start;flex-direction:column}.profile-grid{grid-template-columns:1fr}.profile-grid .full-width{grid-column:auto}.listing-actions form{flex:1}.listing-actions .button{width:100%}}
</style>
@endpush
@section('content')
<main class="wrap dash organisation-workspace">
    <header class="workspace-heading">
        <div><span class="eyebrow">Approved organisation · {{ $user->name }}</span><h1>Your organisation workspace</h1><p class="muted">Maintain your organisation profile and manage your Kampala internship opportunities.</p></div>
        <span class="tag">Approved</span>
    </header>
    @if(session('status'))<div class="notice" role="status" style="margin-bottom:18px">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice" role="alert" style="margin-bottom:18px">Please review the form errors and try again.</div>@endif

    <div class="stats">
        <div class="stat"><strong>{{ $internships->count() }}</strong><span>Your opportunities</span></div>
        <div class="stat"><strong>{{ $totalApplicants }}</strong><span>Applications received</span></div>
        <div class="stat"><strong>{{ $interviews }}</strong><span>Interviews</span></div>
        <div class="stat"><strong>{{ $placements }}</strong><span>Offers / placements</span></div>
    </div>

    <div class="organisation-sections">
        <section class="card">
            <span class="eyebrow">Organisation details</span><h2>Update your profile</h2>
            <form method="POST" action="{{ route('organisation.profile.update') }}">
                @csrf @method('PATCH')
                <div class="profile-grid">
                    <div class="field"><label for="profile-name">Organisation name</label><input class="input" id="profile-name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></div>
                    <div class="field"><label for="profile-email">Contact email</label><input class="input" id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255"></div>
                    <div class="field"><label for="organisation_phone">Telephone</label><input class="input" id="organisation_phone" name="organisation_phone" type="tel" value="{{ old('organisation_phone', $user->organisation_phone) }}" maxlength="30"></div>
                    <div class="field"><label for="organisation_website">Website</label><input class="input" id="organisation_website" name="organisation_website" type="url" value="{{ old('organisation_website', $user->organisation_website) }}" maxlength="255" placeholder="https://"></div>
                    <div class="field full-width"><label for="organisation_description">About your organisation</label><textarea class="input" id="organisation_description" name="organisation_description" rows="4" maxlength="5000">{{ old('organisation_description', $user->organisation_description) }}</textarea></div>
                </div>
                <button class="button" type="submit">Save organisation profile</button>
            </form>
        </section>

        <section class="card">
            <span class="eyebrow">Publish an opportunity</span><h2>Create internship</h2>
            <form method="POST" action="{{ route('organisation.internships.store') }}">
                @csrf
                <div class="field"><label for="title">Role title</label><input class="input" id="title" name="title" required maxlength="180" value="{{ old('title') }}"></div>
                <div class="field"><label for="field">Career field</label><input class="input" id="field" name="field" required maxlength="100" value="{{ old('field') }}" placeholder="e.g. Information Technology"></div>
                <div class="field"><label for="description">Role description</label><textarea class="input" id="description" name="description" rows="4" required minlength="30" maxlength="10000">{{ old('description') }}</textarea></div>
                <div class="field"><label for="responsibilities">Responsibilities and duties</label><textarea class="input" id="responsibilities" name="responsibilities" rows="3" maxlength="10000">{{ old('responsibilities') }}</textarea></div>
                <div class="field"><label for="required_qualifications">Qualifications and skills</label><textarea class="input" id="required_qualifications" name="required_qualifications" rows="3" maxlength="10000">{{ old('required_qualifications') }}</textarea></div>
                <div class="field"><label for="payment_information">Payment information</label><textarea class="input" id="payment_information" name="payment_information" rows="2" maxlength="3000">{{ old('payment_information') }}</textarea></div>
                <div class="field"><label for="application_requirements">Application requirements</label><textarea class="input" id="application_requirements" name="application_requirements" rows="2" maxlength="3000">{{ old('application_requirements') }}</textarea></div>
                <div class="field"><label for="eligible_courses">Eligible courses (optional)</label><select class="input" id="eligible_courses" name="eligible_courses[]" multiple size="4"><option value="Computer Science">Computer Science</option><option value="Business Administration">Business Administration</option><option value="Engineering">Engineering</option><option value="Information Technology">Information Technology</option></select><small class="muted">Leave unselected to accept all courses.</small></div>
                <div class="grid two">
                    <div class="field"><label for="slots_total">Available positions</label><input class="input" id="slots_total" name="slots_total" type="number" min="1" max="500" required value="{{ old('slots_total', 1) }}"></div>
                    <div class="field"><label for="duration_weeks">Duration (weeks)</label><input class="input" id="duration_weeks" name="duration_weeks" type="number" min="1" max="52" required value="{{ old('duration_weeks', 12) }}"></div>
                </div>
                <div class="field"><label for="closes_at">Application deadline</label><input class="input" id="closes_at" name="closes_at" type="date" min="{{ today()->addDay()->format('Y-m-d') }}" required value="{{ old('closes_at') }}"></div>
                <button class="button" type="submit">Publish in Kampala</button>
            </form>
        </section>
    </div>

    <section class="card organisation-table">
        <span class="eyebrow">Your published opportunities</span><h2>Manage internship listings</h2>
        <div class="organisation-list">
            @forelse($internships as $internship)
                <article class="organisation-listing">
                    <div class="row"><div><h3>{{ $internship->title }}</h3><p>{{ $internship->field }} · {{ $internship->location }} · {{ $internship->duration_weeks }} weeks</p></div><span class="tag {{ $internship->status === 'closed' ? 'warn' : '' }}">{{ ucfirst($internship->status) }}</span></div>
                    <div class="row" style="margin-top:10px"><span class="slots">{{ max(0, $internship->slots_total - $internship->placed_count) }} of {{ $internship->slots_total }} positions left</span><span class="muted">{{ $internship->applications->count() }} applications · deadline {{ $internship->closes_at->format('j M Y') }}</span></div>
                    <div class="listing-actions">
                        <form method="POST" action="{{ route('organisation.internships.status', $internship) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $internship->status === 'open' ? 'closed' : 'open' }}"><button class="button light small" type="submit">{{ $internship->status === 'open' ? 'Deactivate / close' : 'Activate' }}</button></form>
                        <a class="button light small" href="{{ route('internships.show', $internship) }}">View public listing</a>
                    </div>
                    <details class="listing-edit">
                        <summary style="cursor:pointer;color:var(--green);font-weight:700">Edit opportunity details</summary>
                        <form method="POST" action="{{ route('organisation.internships.update', $internship) }}" style="margin-top:12px">
                            @csrf @method('PATCH')
                            <div class="field"><label for="title-{{ $internship->id }}">Role title</label><input class="input" id="title-{{ $internship->id }}" name="title" value="{{ $internship->title }}" required maxlength="180"></div>
                            <div class="field"><label for="field-{{ $internship->id }}">Career field</label><input class="input" id="field-{{ $internship->id }}" name="field" value="{{ $internship->field }}" required maxlength="100"></div>
                            <div class="field"><label for="description-{{ $internship->id }}">Role description</label><textarea class="input" id="description-{{ $internship->id }}" name="description" rows="4" required minlength="30" maxlength="10000">{{ $internship->description }}</textarea></div>
                            <div class="field"><label for="responsibilities-{{ $internship->id }}">Responsibilities</label><textarea class="input" id="responsibilities-{{ $internship->id }}" name="responsibilities" rows="3" maxlength="10000">{{ $internship->responsibilities }}</textarea></div>
                            <div class="field"><label for="qualifications-{{ $internship->id }}">Qualifications and skills</label><textarea class="input" id="qualifications-{{ $internship->id }}" name="required_qualifications" rows="3" maxlength="10000">{{ $internship->required_qualifications }}</textarea></div>
                            <div class="field"><label for="payment-{{ $internship->id }}">Payment information</label><textarea class="input" id="payment-{{ $internship->id }}" name="payment_information" rows="2" maxlength="3000">{{ $internship->payment_information }}</textarea></div>
                            <div class="field"><label for="requirements-{{ $internship->id }}">Application requirements</label><textarea class="input" id="requirements-{{ $internship->id }}" name="application_requirements" rows="2" maxlength="3000">{{ $internship->application_requirements }}</textarea></div>
                            <div class="field"><label for="courses-{{ $internship->id }}">Eligible courses (optional)</label><select class="input" id="courses-{{ $internship->id }}" name="eligible_courses[]" multiple size="4">@foreach(['Computer Science', 'Business Administration', 'Engineering', 'Information Technology'] as $course)<option value="{{ $course }}" @selected(in_array($course, old('eligible_courses', $internship->eligible_courses ?? []), true))>{{ $course }}</option>@endforeach</select></div>
                            <div class="grid two">
                                <div class="field"><label for="slots-{{ $internship->id }}">Positions</label><input class="input" id="slots-{{ $internship->id }}" name="slots_total" type="number" min="1" max="500" required value="{{ $internship->slots_total }}"></div>
                                <div class="field"><label for="duration-{{ $internship->id }}">Duration (weeks)</label><input class="input" id="duration-{{ $internship->id }}" name="duration_weeks" type="number" min="1" max="52" required value="{{ $internship->duration_weeks }}"></div>
                            </div>
                            <div class="field"><label for="deadline-{{ $internship->id }}">Application deadline</label><input class="input" id="deadline-{{ $internship->id }}" name="closes_at" type="date" min="{{ today()->addDay()->format('Y-m-d') }}" required value="{{ $internship->closes_at->format('Y-m-d') }}"></div>
                            <button class="button" type="submit">Save listing</button>
                        </form>
                    </details>
                </article>
            @empty
                <p class="muted">You have not published any internship opportunities yet.</p>
            @endforelse
        </div>
    </section>

    <section class="card organisation-table">
        <span class="eyebrow">Applications</span><h2>Applicant pipeline</h2>
        <div class="table-wrap"><table class="table"><thead><tr><th>Applicant</th><th>University / course</th><th>Opportunity</th><th>Status</th><th>Update</th></tr></thead><tbody>
            @forelse($applications as $application)
                <tr><td>{{ $application->student->name }}<details><summary style="color:var(--green);cursor:pointer">Read introductory letter</summary><p>{{ $application->cover_letter }}</p></details></td><td>{{ $application->student->university }}<br>{{ $application->student->course }}</td><td>{{ $application->internship->title }}</td><td>{{ ucfirst($application->status) }}@if($application->interview_at)<br>{{ $application->interview_at->format('j M, g:i a') }}@endif</td><td><form method="POST" action="{{ route('organisation.applications.update', $application) }}">@csrf @method('PATCH')<select class="input" name="status" required><option value="shortlisted">Shortlist</option><option value="interview">Interview</option><option value="offered">Offer</option><option value="placed">Placed</option><option value="rejected">Reject</option></select><input class="input" name="interview_at" type="datetime-local" style="margin-top:5px" aria-label="Interview date and time"><button class="button small" style="margin-top:5px">Update</button></form></td></tr>
            @empty
                <tr><td colspan="5">No applications received yet.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</main>
@endsection
