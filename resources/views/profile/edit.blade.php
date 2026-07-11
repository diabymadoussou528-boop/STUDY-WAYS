@extends('layouts.app')

@section('title', 'Mon profil')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile-avatar.css') }}">
@endsection

@section('content')
<div class="topbar">
    <div class="topbar-title">
        <h1>Mon profil</h1>
        <p>Gérez vos informations et votre photo de profil</p>
    </div>
</div>

<div class="profile-page-wrap">
    <section class="section-card profile-avatar-card">
        <div class="section-card-header">
            <div>
                <h2 class="section-card-title">Photo de profil</h2>
                <p class="section-card-sub">JPG, PNG, GIF ou WEBP — max 5 Mo</p>
            </div>
        </div>
        <div class="profile-avatar-body" style="padding: 0 24px 24px;">
            @include('profile.partials.avatar-upload-form', ['user' => $user])
        </div>
    </section>

    <section class="section-card">
        <div class="section-card-header">
            <div>
                <h2 class="section-card-title">Informations</h2>
            </div>
        </div>
        <div style="padding: 0 24px 24px;">
            @include('profile.partials.update-profile-information-form')
        </div>
    </section>

    <section class="section-card">
        <div style="padding: 24px;">
            @include('profile.partials.update-password-form')
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/profile-avatar.js') }}" defer></script>
@endsection
