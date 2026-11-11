@extends('layouts.internlink')
@section('title', 'Browse Kampala internships')
@section('content')
<div class="wrap dash"><div class="dash-head"><span class="eyebrow">Kampala opportunities</span><h1>Find your next internship</h1><p class="muted">Browse open listings, check available capacity and apply directly.</p></div>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<form class="card" method="GET" action="{{ route('internships.index') }}" role="search" style="margin-bottom:20px">
    <label for="internship-search" style="display:block;font-weight:700;margin-bottom:9px">Search internships</label>
    <div class="row" style="justify-content:flex-start">
        <input class="input" type="search" id="internship-search" name="q" value="{{ $search }}" placeholder="Try a role, organisation, or field" style="flex:1;min-width:0">
        <button class="button" type="submit">Search</button>
        @if($search !== '')
            <a class="button light" href="{{ route('internships.index') }}">Clear</a>
        @endif
    </div>
    <p class="muted" style="margin-top:9px">Search by role, organisation, field, location, or description.</p>
</form>
<div class="grid three">@forelse($internships as $internship)<article class="card internship"><div class="row"><span class="tag">{{ $internship->field }}</span><span class="slots">{{ max(0, $internship->slots_total - $internship->filled_slots) }} of {{ $internship->slots_total }} slots left</span></div><div><h3>{{ $internship->title }}</h3><p>{{ $internship->organisation->name }} · {{ $internship->location }} · {{ $internship->duration_weeks }} weeks</p></div><p>{{ \Illuminate\Support\Str::limit($internship->description, 130) }}</p><div class="row"><span class="tag {{ $internship->is_closing_soon ? 'warn' : '' }}">Closes {{ $internship->closes_at->format('j M Y') }}</span><a class="button light small" href="{{ route('internships.show', $internship) }}">View details →</a></div></article>@empty<div class="card"><h3>{{ $search !== '' ? 'No matching internships found' : 'No open internships yet' }}</h3><p>{{ $search !== '' ? 'Try another search to find a suitable placement.' : 'Check back soon for new Kampala placements.' }}</p></div>@endforelse</div><div style="margin-top:24px">{{ $internships->links() }}</div></div>
@endsection
