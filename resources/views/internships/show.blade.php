@extends('layouts.internlink')
@section('title', $internship->title.' · InternLink')
@push('head')
<style>
    .internship-detail-page{padding:34px 0 64px}
    .detail-back{display:inline-flex;align-items:center;gap:8px;margin-bottom:18px;color:var(--green);font-weight:700}
    .detail-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(270px,.7fr);gap:20px;align-items:start}
    .detail-main,.detail-aside{padding:clamp(21px,4vw,34px)}
    .detail-title{margin:13px 0 8px;font-size:clamp(30px,4.5vw,43px);line-height:1.15}
    .detail-company{margin:0;color:var(--muted);font-size:16px}
    .detail-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:25px 0;padding:19px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
    .detail-fact span{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.06em}
    .detail-fact strong{display:block;margin-top:5px;font-size:14px}
    .detail-section{padding:20px 0;border-bottom:1px solid var(--line)}
    .detail-section:last-child{border-bottom:0;padding-bottom:0}
    .detail-section h2{margin:0 0 10px;font-size:19px}
    .detail-copy{margin:0;color:var(--muted);line-height:1.8;white-space:pre-line;overflow-wrap:anywhere}
    .detail-contact{display:grid;gap:8px;margin-top:16px;padding:15px;border-radius:12px;background:#f6f9f8}
    .detail-contact a{color:var(--green);font-weight:700;overflow-wrap:anywhere}
    .detail-aside{position:sticky;top:20px}
    .detail-aside h2{margin:0 0 10px;font-size:21px}
    .detail-aside>p{color:var(--muted);line-height:1.65}
    .detail-slots{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin:16px 0}
    .detail-slots strong{color:var(--green)}
    .detail-aside .button{width:100%;margin-top:8px}
    @media(max-width:800px){.detail-layout{grid-template-columns:1fr}.detail-aside{position:static}}
    @media(max-width:520px){.internship-detail-page{padding:23px 0 44px}.detail-facts{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endpush
@section('content')
<div class="wrap internship-detail-page">
    <a class="detail-back" href="{{ route('internships.index') }}">← All internship opportunities</a>
    <div class="detail-layout">
        <article class="card detail-main">
            <span class="tag">{{ $internship->field }}</span>
            <h1 class="detail-title">{{ $internship->title }}</h1>
            <p class="detail-company">{{ $internship->organisation->name }}</p>
            <div class="detail-facts">
                <div class="detail-fact"><span>Location</span><strong>{{ $internship->location }}</strong></div>
                <div class="detail-fact"><span>Duration</span><strong>{{ $internship->duration_weeks }} weeks</strong></div>
                <div class="detail-fact"><span>Field</span><strong>{{ $internship->field }}</strong></div>
                <div class="detail-fact"><span>Positions available</span><strong>{{ max(0, $internship->slots_total - $internship->filled_slots) }} of {{ $internship->slots_total }}</strong></div>
                <div class="detail-fact"><span>Application deadline</span><strong>{{ $internship->closes_at->format('j M Y') }}</strong></div>
                <div class="detail-fact"><span>Date posted</span><strong>{{ $internship->created_at->format('j M Y') }}</strong></div>
            </div>

            <section class="detail-section"><h2>About this internship</h2><p class="detail-copy">{{ $internship->description }}</p></section>
            <section class="detail-section"><h2>Responsibilities and duties</h2><p class="detail-copy">{{ filled($internship->responsibilities) ? $internship->responsibilities : 'The organisation has not provided separate responsibilities for this listing.' }}</p></section>
            <section class="detail-section"><h2>Qualifications and skills</h2>
                @if(filled($internship->required_qualifications))
                    <p class="detail-copy">{{ $internship->required_qualifications }}</p>
                @elseif($internship->eligible_courses)
                    <p class="detail-copy">Eligible courses: {{ implode(', ', $internship->eligible_courses) }}.</p>
                @else
                    <p class="detail-copy">The organisation has not provided specific qualifications or eligible-course restrictions for this listing.</p>
                @endif
            </section>
            <section class="detail-section"><h2>Payment information</h2><p class="detail-copy">{{ filled($internship->payment_information) ? $internship->payment_information : 'Payment information has not been provided by the organisation.' }}</p></section>
            <section class="detail-section"><h2>Application requirements</h2><p class="detail-copy">{{ filled($internship->application_requirements) ? $internship->application_requirements : 'No additional application-document instructions have been provided.' }}</p></section>
            <section class="detail-section"><h2>Organisation</h2><p class="detail-copy">{{ $internship->organisation->name }}</p>
                @if(filled($internship->organisation->email))
                    <div class="detail-contact"><span class="muted">Organisation contact</span><a href="mailto:{{ $internship->organisation->email }}">{{ $internship->organisation->email }}</a></div>
                @endif
            </section>
        </article>

        <aside class="card detail-aside">
            <span class="eyebrow">Application</span><h2>Interested in this placement?</h2>
            <p>Review the organisation’s requirements above before applying. Applications require a student account.</p>
            <div class="detail-slots"><span>Remaining positions</span><strong>{{ max(0, $internship->slots_total - $internship->filled_slots) }}</strong></div>
            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="notice" role="alert">{{ $errors->first() }}</div>@endif
            @guest
                <p class="muted">Sign in with your student account to continue.</p>
                <a class="button" href="{{ route('login') }}">Sign in</a>
                <a class="button light" href="{{ route('register') }}">Create account</a>
            @else
                @if(! auth()->user()->hasRole('student'))
                    <p class="muted">Only student accounts can apply to internship opportunities.</p>
                @elseif($alreadyApplied)
                    <p class="notice">You have already applied to this internship.</p>
                @elseif($internship->slots_total - $internship->filled_slots < 1)
                    <p class="notice">This internship has filled all its positions.</p>
                @else
                    <form method="POST" action="{{ route('internships.apply', $internship) }}">
                        @csrf
                        <div class="field"><label for="cover_letter">Introductory letter</label><textarea class="input" id="cover_letter" name="cover_letter" rows="8" minlength="40" maxlength="5000" required>{{ old('cover_letter') }}</textarea><small class="muted">Tell this organisation why you are interested in this specific placement.</small>@error('cover_letter')<small style="color:#b42318">{{ $message }}</small>@enderror</div>
                        <button class="button" type="submit">Submit application</button>
                    </form>
                @endif
            @endguest
        </aside>
    </div>
</div>
@endsection
