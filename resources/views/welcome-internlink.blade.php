@extends('layouts.internlink')
@section('title', 'Internships that move you forward')
@push('head')
<style>
	.home-page{overflow:hidden}
	.home-page .wrap{max-width:1180px}
	.home-page .home-hero{position:relative;padding:88px 0 84px;background:radial-gradient(ellipse at 82% 12%,rgba(230,173,88,.18),transparent 30%),linear-gradient(135deg,#edf7f2 0%,#f8faf8 58%,#fdf8ef 100%)}
	.home-page .hero-grid{grid-template-columns:1.04fr .96fr;gap:64px}
	.home-page .hero-copy{max-width:590px}
	.home-page .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:.11em;text-transform:uppercase}
	.home-page .eyebrow:before{content:"";width:7px;height:7px;border-radius:50%;background:var(--gold)}
	.home-page h1{max-width:650px;margin:17px 0 20px;font-size:clamp(42px,5.4vw,68px);line-height:1.08;letter-spacing:-.055em}
	.home-page .hero-copy>p{max-width:570px;color:var(--muted);font-size:17px;line-height:1.8}
	.home-page .hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin:28px 0 18px}
	.home-page .button{min-height:48px;padding:13px 19px;transition:transform .18s ease,box-shadow .18s ease,background .18s ease}
	.home-page .button:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,45,58,.12)}
	.home-page .hero-note{color:var(--muted);font-size:13px;line-height:1.6}
	.home-page .hero-art{position:relative;min-height:405px;display:grid;place-items:center;overflow:hidden;border:1px solid rgba(16,45,58,.1);border-radius:26px;background:linear-gradient(135deg,rgba(16,45,58,.28),rgba(22,123,98,.24)),url('/assets/img/pexels-fauxels-3183152.jpg') center/cover;box-shadow:0 22px 46px rgba(16,45,58,.14)}
	.home-page .art-orbit{position:absolute;width:min(78%,330px);aspect-ratio:1;border:1px solid rgba(255,255,255,.28);border-radius:50%;background:radial-gradient(circle at 50% 42%,rgba(255,255,255,.2),rgba(231,245,239,.12) 58%,transparent 59%)}
	.home-page .art-orbit:before,.home-page .art-orbit:after{content:"";position:absolute;border:1px dashed rgba(255,255,255,.38);border-radius:50%;inset:10%}
	.home-page .art-orbit:after{inset:22%;border-style:solid;border-color:rgba(230,173,88,.25)}
	.home-page .art-center{z-index:1;width:104px;height:104px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.38);border-radius:32px;background:rgba(22,123,98,.91);color:white;box-shadow:0 18px 38px rgba(16,45,58,.2);transform:rotate(-4deg);backdrop-filter:blur(8px)}
	.home-page .art-center svg{width:58px;height:58px}
	.home-page .art-card{position:absolute;z-index:2;width:172px;min-height:112px;padding:13px 15px;background:rgba(255,255,255,.79);border:1px solid rgba(255,255,255,.72);border-radius:16px;box-shadow:0 12px 28px rgba(16,45,58,.12);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)}
	.home-page .art-card strong{display:block;margin:7px 0 3px;font:700 13px Manrope,sans-serif}
	.home-page .art-card span{color:#405867;font-size:11px;line-height:1.4}
	.home-page .art-card .art-icon{width:29px;height:29px;display:grid;place-items:center;border-radius:9px;background:rgba(231,245,239,.9);color:var(--green);font-weight:800}
	.home-page .art-card.student{top:20px;left:20px}
	.home-page .art-card.employer{top:20px;right:20px}
	.home-page .art-card.university{bottom:20px;left:20px}
	.home-page .art-card.path{right:20px;bottom:20px}
	.home-page .section{padding:82px 0}
	.home-page .section-head{max-width:690px;margin:0 auto 36px;text-align:center}
	.home-page .section-head h2,.home-page .benefit-intro h2{margin:12px 0;font-size:clamp(30px,3.8vw,43px);line-height:1.18}
	.home-page .section-head p{margin:0;color:var(--muted);font-size:16px;line-height:1.7}
	.home-page .how-section{background:#fff}
	.home-page .steps{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
	.home-page .step-card,.home-page .benefit-card{height:100%;padding:27px;border:1px solid var(--line);border-radius:18px;background:white;box-shadow:0 8px 26px rgba(16,45,58,.035)}
	.home-page .step-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:23px}
	.home-page .step-number{font:800 13px Manrope;color:var(--green);letter-spacing:.08em}
	.home-page .step-symbol{width:43px;height:43px;display:grid;place-items:center;border-radius:13px;background:var(--mint);color:var(--green);font-size:19px;font-weight:800}
	.home-page .step-card h3,.home-page .benefit-card h3{margin:0 0 9px;font-size:19px}
	.home-page .step-card p,.home-page .benefit-card p{margin:0;color:var(--muted);line-height:1.75}
	.home-page .benefits-section{background:var(--paper)}
	.home-page .benefit-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
	.home-page .benefit-card{padding:29px;background:#fff}
	.home-page .benefit-photo{height:142px;margin:-29px -29px 23px;border-radius:17px 17px 0 0;background:linear-gradient(135deg,rgba(16,45,58,.2),rgba(22,123,98,.16)),url('/assets/img/pexels-rdne-7683694.jpg') center 42%/cover}
	.home-page .benefit-photo.student-photo{background-image:linear-gradient(135deg,rgba(16,45,58,.16),rgba(22,123,98,.12)),url('/assets/img/pexels-rdne-7683694.jpg')}
	.home-page .benefit-photo.employer-photo{background-image:linear-gradient(135deg,rgba(16,45,58,.2),rgba(22,123,98,.14)),url('/assets/img/pexels-fauxels-3183152.jpg')}
	.home-page .benefit-card .role-icon{margin-bottom:20px}
	.home-page .benefit-card ul{display:grid;gap:11px;padding:17px 0 0;margin:17px 0 0;border-top:1px solid var(--line);list-style:none}
	.home-page .benefit-card li{display:flex;gap:9px;color:var(--muted);font-size:14px;line-height:1.5}
	.home-page .benefit-card li:before{content:"✓";color:var(--green);font-weight:800}
	.home-page .opportunities{background:#fff}
	.home-page .opportunities .section-head{text-align:left;margin-left:0}
	.home-page .opportunities .section-head h2{margin-top:8px}
	.home-page .internship{min-height:210px}
	.home-page .browse-all{text-align:center;margin-top:27px}
	.home-page .closing-cta{padding:64px 0;background:var(--navy);color:#fff}
	.home-page .closing-inner{display:flex;align-items:center;justify-content:space-between;gap:30px}
	.home-page .closing-inner h2{max-width:600px;margin:10px 0 8px;font-size:clamp(27px,3.4vw,38px)}
	.home-page .closing-inner p{margin:0;color:#c0d0d2;line-height:1.7}
	.home-page .closing-inner .eyebrow{color:#9ed8bb}
	.home-page .closing-actions{display:flex;flex-wrap:wrap;gap:11px;flex-shrink:0}
	.home-page .closing-actions .button.light{background:transparent;border-color:rgba(255,255,255,.28);color:white}
	.home-page .footer{padding:30px 0;background:#0b222d;color:#d4e0e1}
	.home-page .footer-inner{display:flex;align-items:center;justify-content:space-between;gap:22px}
	.home-page .footer .brand{color:white}
	.home-page .footer-links{display:flex;flex-wrap:wrap;gap:20px;color:#c0d0d2;font-size:13px}
	.home-page .footer-links a:hover{color:white}
	.home-page .footer-note{margin:0;color:#9eb2b6;font-size:13px}
	@media(max-width:900px){.home-page .hero-grid{gap:28px}.home-page .hero-art{min-height:350px}.home-page .art-card{width:160px}.home-page .art-card.student,.home-page .art-card.university{left:14px}.home-page .art-card.employer,.home-page .art-card.path{right:14px}.home-page .section{padding:66px 0}.home-page .closing-inner{align-items:flex-start;flex-direction:column}}
	@media(max-width:680px){.home-page .wrap{padding-right:20px;padding-left:20px}.home-page .home-hero{padding:60px 0 55px}.home-page .hero-grid{grid-template-columns:1fr;gap:22px}.home-page h1{font-size:clamp(39px,11vw,54px)}.home-page .hero-copy>p{font-size:16px}.home-page .hero-art{min-height:330px;margin:0 -8px}.home-page .art-orbit{width:min(70%,270px)}.home-page .art-center{width:88px;height:88px;border-radius:27px}.home-page .art-center svg{width:48px;height:48px}.home-page .art-card{width:151px;min-height:104px;padding:11px 12px}.home-page .art-card strong{font-size:12px}.home-page .art-card span{font-size:10px}.home-page .art-card.student{top:12px;left:8px}.home-page .art-card.employer{top:12px;right:8px}.home-page .art-card.university{bottom:12px;left:8px}.home-page .art-card.path{right:8px;bottom:12px}.home-page .section{padding:58px 0}.home-page .section-head{margin-bottom:27px}.home-page .steps,.home-page .benefit-grid,.home-page .grid.three{grid-template-columns:1fr}.home-page .step-card,.home-page .benefit-card{padding:23px}.home-page .benefit-photo{margin:-23px -23px 20px}.home-page .opportunities .section-head{text-align:center;margin-left:auto}.home-page .closing-cta{padding:48px 0}.home-page .closing-actions{width:100%}.home-page .closing-actions .button{flex:1}.home-page .footer-inner{align-items:flex-start;flex-direction:column}.home-page .footer-links{gap:14px}}
	@media(max-width:440px){.home-page .hero-actions{display:grid}.home-page .hero-actions .button{width:100%}.home-page .hero-art{min-height:300px}.home-page .art-card{width:calc(50% - 18px);min-height:100px;padding:10px}.home-page .art-card.student{left:8px}.home-page .art-card.employer{right:8px}.home-page .art-card.university{left:8px}.home-page .art-card.path{right:8px}.home-page .closing-actions{flex-direction:column}.home-page .closing-actions .button{width:100%}}
	@media(max-width:760px){body:has(.home-page) .nav{height:auto;min-height:76px;padding:12px 0}body:has(.home-page) .navin{align-items:flex-start;gap:12px}body:has(.home-page) .links{justify-content:flex-end;flex-wrap:wrap;gap:10px 15px;font-size:13px}body:has(.home-page) .links .button{min-height:38px;padding:9px 12px}}
	@media(prefers-reduced-motion:reduce){.home-page .button{transition:none}}
</style>
@endpush
@section('content')
<main class="home-page">
	<section class="home-hero">
		<div class="wrap hero-grid">
			<div class="hero-copy">
				<span class="eyebrow">A clearer path to your career</span>
				<h1>Turn what you learn into <span style="color:var(--green)">what you can do.</span></h1>
				<p>InternLink connects students, employers and university teams around the whole internship journey—from discovering an opportunity to supporting progress on placement.</p>
				<div class="hero-actions">
					<a class="button" href="{{ route('internships.index') }}">Explore internships <span aria-hidden="true">→</span></a>
					<a class="button light" href="{{ route('register') }}#role">Register as an employer</a>
				</div>
				<div class="hero-note">Students can browse current opportunities. Employers can choose Organisation when creating an account.</div>
			</div>
			<div class="hero-art" role="img" aria-label="Illustration of students, employers and universities connected through an internship journey">
				<div class="art-orbit"></div>
				<div class="art-center" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none"><path d="M14 19.5 32 11l18 8.5L32 28l-18-8.5Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M21 24.5V36c0 3 5 6 11 6s11-3 11-6V24.5M50 20v16" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="50" cy="41" r="3" fill="currentColor"/><path d="M23 51h18M32 43v8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg></div>
				<div class="art-card student"><span class="art-icon">S</span><strong>Student</strong><span>Discover and apply for opportunities</span></div>
				<div class="art-card employer"><span class="art-icon">E</span><strong>Employer</strong><span>Share roles and review applicants</span></div>
				<div class="art-card university"><span class="art-icon">U</span><strong>University</strong><span>Support placement progress</span></div>
				<div class="art-card path"><span class="art-icon">↗</span><strong>One connected journey</strong><span>From first application to placement</span></div>
			</div>
		</div>
	</section>

	<section class="section how-section" id="how">
		<div class="wrap">
			<div class="section-head"><span class="eyebrow">How InternLink works</span><h2>Three sides, working toward one great placement.</h2><p>Keep opportunity discovery, recruitment and university support connected in one place.</p></div>
			<div class="grid steps">
				<article class="step-card"><div class="step-top"><span class="step-number">01 / DISCOVER</span><span class="step-symbol" aria-hidden="true">⌕</span></div><h3>Find an opportunity</h3><p>Students explore internship listings and review role details, requirements and closing dates.</p></article>
				<article class="step-card"><div class="step-top"><span class="step-number">02 / CONNECT</span><span class="step-symbol" aria-hidden="true">↗</span></div><h3>Apply and recruit</h3><p>Students submit applications, while employers publish roles and manage candidates in their account.</p></article>
				<article class="step-card"><div class="step-top"><span class="step-number">03 / GROW</span><span class="step-symbol" aria-hidden="true">✓</span></div><h3>Stay supported</h3><p>Students keep placement progress together, and university supervisors can review logbooks and coordinate support.</p></article>
			</div>
		</div>
	</section>

	<section class="section benefits-section" id="benefits">
		<div class="wrap">
			<div class="section-head"><span class="eyebrow">Made for the placement community</span><h2>Useful at every stage of the journey.</h2><p>Purpose-built tools help each participant focus on the work that matters to them.</p></div>
			<div class="grid benefit-grid">
				<article class="benefit-card"><div class="benefit-photo student-photo" role="img" aria-label="Students collaborating on placement opportunities"></div><span class="role-icon">S</span><h3>For students</h3><p>Move from searching to learning with a clearer view of your placement.</p><ul><li>Browse opportunities and role requirements</li><li>Keep application updates in one place</li><li>Submit weekly progress for review</li></ul></article>
				<article class="benefit-card"><div class="benefit-photo employer-photo" role="img" aria-label="People working together on an opportunity"></div><span class="role-icon">E</span><h3>For employers</h3><p>Share opportunities and follow applicants through your recruitment process.</p><ul><li>Publish internship opportunities</li><li>Set capacity and review applications</li><li>Coordinate with placement stakeholders</li></ul></article>
				<article class="benefit-card"><div class="benefit-photo" role="img" aria-label="Students learning together"></div><span class="role-icon">U</span><h3>For universities</h3><p>Keep placement supervision and student progress easier to follow.</p><ul><li>Support assigned students</li><li>Review logbook entries and provide feedback</li><li>Coordinate placement visits and messages</li></ul></article>
			</div>
		</div>
	</section>

	<section class="section opportunities" id="internships">
		<div class="wrap">
			<div class="section-head"><span class="eyebrow">Explore opportunities</span><h2>Internships to get you started.</h2><p>Browse current listings and open a role to see its details.</p></div>
			<div class="grid three">
				@forelse($internships as $internship)
					<article class="card internship"><div class="row"><span class="tag">{{ $internship->field }}</span><span class="slots">{{ max(0, $internship->slots_total - $internship->filled_slots) }} of {{ $internship->slots_total }} slots left</span></div><div><h3>{{ $internship->title }}</h3><p>{{ $internship->organisation->name }} · {{ $internship->location }} · {{ $internship->duration_weeks }} weeks</p></div><div class="row"><span class="tag {{ $internship->is_closing_soon ? 'warn' : '' }}">{{ $internship->is_closing_soon ? 'Closing soon · ' : 'Closes ' }}{{ $internship->closes_at->format('j M') }}</span><a class="button light small" href="{{ route('internships.show', $internship) }}">View role <span aria-hidden="true">→</span></a></div></article>
				@empty
					<div class="card"><h3>No open internships just yet</h3><p>Check back for new opportunities, or browse all listings.</p></div>
				@endforelse
			</div>
			<div class="browse-all"><a class="button light" href="{{ route('internships.index') }}">Browse all internships <span aria-hidden="true">→</span></a></div>
		</div>
	</section>

	<section class="closing-cta">
		<div class="wrap closing-inner"><div><span class="eyebrow">Take the next step</span><h2>Make your next placement a more connected experience.</h2><p>Explore opportunities or join InternLink as an employer.</p></div><div class="closing-actions"><a class="button" href="{{ route('internships.index') }}">Find an internship</a><a class="button light" href="{{ route('register') }}#role">Employer registration</a></div></div>
	</section>

	<footer class="footer">
		<div class="wrap footer-inner"><a class="brand" href="{{ route('home') }}"><span class="brandmark">i</span> internlink</a><nav class="footer-links" aria-label="Footer navigation"><a href="{{ route('home') }}#how">How it works</a><a href="{{ route('internships.index') }}">Internships</a><a href="{{ route('login') }}">Sign in</a><a href="{{ route('register') }}">Create an account</a></nav><p class="footer-note">© {{ date('Y') }} InternLink · Better connections. Better placements.</p></div>
	</footer>
</main>
@endsection
