@extends('layouts.internlink')
@section('title', 'Access denied')
@section('content')<div class="wrap" style="padding-top:90px;padding-bottom:90px;text-align:center"><span class="eyebrow">403 · Restricted</span><h1>You don't have access to this page.</h1><p class="muted">Your account role does not allow this action.</p><a class="button" href="{{ auth()->check() ? route('dashboard') : route('home') }}">Return to InternLink</a></div>@endsection
