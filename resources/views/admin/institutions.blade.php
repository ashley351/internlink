@extends('layouts.internlink')
@section('title', 'Manage institutions')
@push('head')
<style>
    .institution-admin{max-width:1080px;margin:34px auto 64px}
    .institution-admin h1{margin:8px 0;font-size:clamp(28px,5vw,36px)}
    .institution-admin .intro{max-width:720px;color:var(--muted);line-height:1.7}
    .institution-admin .card{padding:clamp(20px,4vw,30px)}
    .institution-admin .admin-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px}
    .institution-admin .field.full{grid-column:1/-1}
    .institution-admin .institution-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;align-items:start;padding:22px 0;border-top:1px solid var(--line)}
    .institution-admin .institution-row h3{margin:0 0 7px;font-size:17px}
    .institution-admin .institution-row p{margin:4px 0;color:var(--muted);font-size:13px;overflow-wrap:anywhere}
    .institution-admin .institution-row form.edit-institution{display:grid;grid-template-columns:minmax(180px,1.3fr) minmax(170px,1fr) minmax(180px,1.3fr) auto;gap:10px;align-items:end}
    .institution-admin .institution-row .field{margin:0}
    .institution-admin .institution-row .input{font-size:13px;padding:9px}
    .institution-admin .status-actions{display:flex;justify-content:flex-end;margin-top:8px}
    .institution-admin .state-tag{display:inline-flex;padding:5px 9px;border-radius:20px;background:var(--mint);color:var(--green);font-size:11px;font-weight:700}
    .institution-admin .state-tag.inactive{background:#f1f3f4;color:#667988}
    @media(max-width:850px){.institution-admin .institution-row form.edit-institution{grid-template-columns:repeat(2,minmax(0,1fr))}.institution-admin .institution-row .save-field{grid-column:1/-1}}
    @media(max-width:600px){.institution-admin{margin:22px auto 45px}.institution-admin .admin-form-grid,.institution-admin .institution-row{grid-template-columns:1fr}.institution-admin .field.full{grid-column:auto}.institution-admin .institution-row form.edit-institution{grid-template-columns:1fr}.institution-admin .institution-row .save-field{grid-column:auto}.institution-admin .status-actions{justify-content:flex-start}}
</style>
@endpush
@section('content')
<div class="wrap institution-admin">
    <a class="button light small" href="{{ route('dashboard.admin') }}">← Admin dashboard</a>
    <div style="margin:20px 0 28px"><span class="eyebrow">Platform settings</span><h1>Kampala institutions</h1><p class="intro">Manage the single A–Z institution list students use during registration and profile updates. New options are restricted to Kampala District and require a source reference. Deactivating an entry preserves existing student profiles.</p></div>
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="notice" role="alert"><b>Please check the institution details.</b><ul style="margin:8px 0 0;padding-left:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="card">
        <span class="eyebrow">Add institution</span><h2 style="margin:8px 0">New Kampala selection</h2>
        <form method="POST" action="{{ route('admin.institutions.store') }}" class="admin-form-grid">
            @csrf
            <div class="field"><label for="new-name">Official institution or campus name</label><input class="input" id="new-name" name="name" value="{{ old('name') }}" required maxlength="180"></div>
            <div class="field"><label for="new-status">NCHE recognition / accreditation status</label><input class="input" id="new-status" name="accreditation_status" value="{{ old('accreditation_status') }}" required maxlength="180"></div>
            <div class="field full"><label for="new-source">Official source URL</label><input class="input" id="new-source" name="source_url" type="url" value="{{ old('source_url', 'https://unche.or.ug/universities/') }}" required maxlength="500"></div>
            <div class="field full"><button class="button" type="submit">Add to list</button></div>
        </form>
    </section>
    <section class="card" style="margin-top:20px">
        <div class="row" style="margin-bottom:18px"><div><span class="eyebrow">A–Z selection list</span><h2 style="margin:8px 0">{{ $institutions->count() }} institution records</h2></div></div>
        @foreach($institutions as $institution)
            <article class="institution-row">
                <div>
                    <h3>{{ $institution->name }}</h3>
                    <span class="state-tag {{ $institution->is_active ? '' : 'inactive' }}">{{ $institution->is_active ? 'Active in student list' : 'Inactive' }}</span>
                    <p>{{ $institution->accreditation_status }} · {{ $institution->district }} District</p>
                    <p><a href="{{ $institution->source_url }}" target="_blank" rel="noopener noreferrer">Verify source ↗</a></p>
                    <div class="status-actions"><form method="POST" action="{{ route('admin.institutions.status', $institution) }}">@csrf @method('PATCH')<button class="button light small" type="submit">{{ $institution->is_active ? 'Deactivate' : 'Activate' }}</button></form></div>
                </div>
                <form method="POST" action="{{ route('admin.institutions.update', $institution) }}" class="edit-institution">
                    @csrf @method('PATCH')
                    <div class="field"><label for="name-{{ $institution->id }}">Name</label><input class="input" id="name-{{ $institution->id }}" name="name" value="{{ $institution->name }}" required maxlength="180"></div>
                    <div class="field"><label for="status-{{ $institution->id }}">NCHE status</label><input class="input" id="status-{{ $institution->id }}" name="accreditation_status" value="{{ $institution->accreditation_status }}" required maxlength="180"></div>
                    <div class="field"><label for="source-{{ $institution->id }}">Official source URL</label><input class="input" id="source-{{ $institution->id }}" name="source_url" type="url" value="{{ $institution->source_url }}" required maxlength="500"></div>
                    <div class="field save-field"><label>&nbsp;</label><button class="button light small" type="submit">Save edits</button></div>
                </form>
            </article>
        @endforeach
    </section>
</div>
@endsection
