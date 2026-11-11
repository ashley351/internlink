@extends('layouts.internlink')
@section('title', 'Internship opportunities · InternLink')
@push('head')
<style>
    .opportunity-page{padding:42px 0 64px}
    .opportunity-hero{position:relative;overflow:hidden;padding:38px clamp(22px,5vw,54px);border:1px solid #dfece7;border-radius:24px;background:radial-gradient(circle at 85% 12%,rgba(230,173,88,.19),transparent 28%),linear-gradient(125deg,#edf7f2,#f8fbf9 68%,#fff9ef)}
    .opportunity-hero h1{max-width:740px;margin:12px 0;font-size:clamp(34px,5vw,52px);line-height:1.1}
    .opportunity-hero p{max-width:680px;margin:0;color:var(--muted);font-size:16px;line-height:1.7}
    .opportunity-search{margin:22px 0 26px;padding:22px}
    .opportunity-search h2{margin:0 0 4px;font-size:19px}
    .opportunity-search>p{margin:0 0 16px;color:var(--muted);font-size:14px}
    .opportunity-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0 14px;align-items:end}
    .opportunity-filter-grid .field{min-width:0}
    .opportunity-filter-grid .field.search-field{grid-column:span 2}
    .opportunity-filter-grid input{min-width:0}
    .opportunity-filter-actions{display:flex;gap:9px;align-items:center;margin:0 0 15px}
    .opportunity-results{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 14px;color:var(--muted);font-size:14px}
    .opportunity-cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
    .opportunity-card{display:flex;flex-direction:column;min-width:0;padding:22px;gap:14px;transition:transform .18s ease,box-shadow .18s ease}
    .opportunity-card:hover{transform:translateY(-3px);box-shadow:0 16px 34px rgba(16,45,58,.09)}
    .opportunity-card h2{margin:3px 0 6px;font-size:19px;line-height:1.35}
    .opportunity-company{margin:0;color:var(--muted);font-size:14px}
    .opportunity-description{margin:0;color:var(--muted);font-size:14px;line-height:1.7}
    .opportunity-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:14px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
    .opportunity-meta span{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.06em}
    .opportunity-meta strong{display:block;margin-top:3px;font-size:13px;overflow-wrap:anywhere}
    .opportunity-card-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:auto}
    .opportunity-posted{color:var(--muted);font-size:12px}
    .opportunity-empty{padding:36px;text-align:center}
    .opportunity-empty h2{margin:0 0 8px}
    .opportunity-empty p{margin:0 0 18px;color:var(--muted)}
    .opportunity-pagination{margin-top:24px}
    @media(max-width:900px){.opportunity-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.opportunity-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:600px){.opportunity-page{padding:24px 0 45px}.opportunity-hero{padding:28px 22px;border-radius:18px}.opportunity-filter-grid,.opportunity-cards{grid-template-columns:1fr}.opportunity-filter-grid .field.search-field{grid-column:auto}.opportunity-filter-actions{flex-wrap:wrap}.opportunity-filter-actions .button{flex:1}.opportunity-results{align-items:flex-start;flex-direction:column}.opportunity-card{padding:19px}}
</style>
@endpush
@section('content')
<div class="wrap opportunity-page">
    <header class="opportunity-hero">
        <span class="eyebrow">Kampala opportunities</span>
        <h1>Find an internship that moves you forward.</h1>
        <p>Search current opportunities by role, organisation, field, location or duration. Open a listing to review the full details before deciding whether to apply.</p>
    </header>

    @if(session('status'))<div class="notice" role="status" style="margin-top:16px">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice" role="alert" style="margin-top:16px">Please check the search filters and try again.</div>@endif

    <form class="card opportunity-search" method="GET" action="{{ route('internships.index') }}" role="search">
        <h2>Search and filter opportunities</h2>
        <p>Enter an organisation name to find its open Kampala internships. Results update when you search.</p>
        <div class="opportunity-filter-grid">
            <div class="field search-field"><label for="internship-search">Role or keyword</label><input class="input" type="search" id="internship-search" name="q" value="{{ $filters['q'] }}" placeholder="e.g. design, engineering"></div>
            <div class="field"><label for="field-filter">Field of study</label><input class="input" id="field-filter" name="field" value="{{ $filters['field'] }}" placeholder="e.g. Technology"></div>
            <div class="field"><label for="location-filter">Location</label><input class="input" id="location-filter" name="location" value="{{ $filters['location'] }}" placeholder="e.g. Kampala"></div>
            <div class="field"><label for="organization-filter">Organisation</label><input class="input" id="organization-filter" name="organization" value="{{ $filters['organization'] }}" placeholder="Type an organisation name" autocomplete="off"></div>
            <div class="field"><label for="duration-filter">Duration (weeks)</label><input class="input" id="duration-filter" name="duration_weeks" type="number" min="1" max="52" value="{{ $filters['duration_weeks'] }}" placeholder="Any duration"></div>
        </div>
        <div class="opportunity-filter-actions"><button class="button" type="submit">Search internships</button>@if($hasFilters)<a class="button light" href="{{ route('internships.index') }}">Clear filters</a>@endif</div>
    </form>

    <div class="opportunity-results"><strong>{{ $internships->total() }} {{ \Illuminate\Support\Str::plural('opportunity', $internships->total()) }} available</strong><span>Open Kampala internships · Closing date has not passed</span></div>
    <div class="grid opportunity-cards">
        @forelse($internships as $internship)
            <article class="card opportunity-card">
                <div class="row"><span class="tag">{{ $internship->field }}</span><span class="slots">{{ max(0, $internship->slots_total - $internship->filled_slots) }} of {{ $internship->slots_total }} positions</span></div>
                <div><h2>{{ $internship->title }}</h2><p class="opportunity-company">{{ $internship->organisation->name }}</p></div>
                <p class="opportunity-description">{{ \Illuminate\Support\Str::limit($internship->description, 160) }}</p>
                <div class="opportunity-meta">
                    <div><span>Location</span><strong>{{ $internship->location }}</strong></div>
                    <div><span>Duration</span><strong>{{ $internship->duration_weeks }} weeks</strong></div>
                    <div><span>Apply by</span><strong>{{ $internship->closes_at->format('j M Y') }}</strong></div>
                    <div><span>Posted</span><strong>{{ $internship->created_at->format('j M Y') }}</strong></div>
                </div>
                <div class="opportunity-card-footer"><span class="opportunity-posted">{{ $internship->eligible_courses ? 'Course-specific listing' : 'Open to eligible students' }}</span><a class="button light small" href="{{ route('internships.show', $internship) }}">View details →</a></div>
            </article>
        @empty
            <section class="card opportunity-empty" style="grid-column:1/-1">
                <h2>{{ $hasFilters ? 'No matching internships found' : 'No open internships right now' }}</h2>
                <p>{{ $hasFilters ? 'Try adjusting your search or clear the filters to see all available Kampala opportunities.' : 'New opportunities will appear here when organisations publish open listings.' }}</p>
                @if($hasFilters)<a class="button light" href="{{ route('internships.index') }}">Clear filters</a>@endif
            </section>
        @endforelse
    </div>
    @if($internships->hasPages())<div class="opportunity-pagination">{{ $internships->links() }}</div>@endif
</div>
@endsection
