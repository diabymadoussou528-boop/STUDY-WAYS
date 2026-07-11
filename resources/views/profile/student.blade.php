@extends('layouts.student')

@section('title', 'Mon profil')

@section('content')
<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Compte · Profil</span>
        <h1 class="dash-title">Mon <span class="dash-title-accent">profil</span></h1>
        <p class="dash-subtitle">Gérez vos informations personnelles et votre photo de profil.</p>
    </div>
</div>

<div class="profile-grid reveal-up">
    <section class="widget-card glass-card profile-avatar-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Photo de profil</h3>
                <p class="widget-subtitle">JPG, PNG, GIF ou WEBP — max 5 Mo</p>
            </div>
        </div>
        <div class="widget-body profile-avatar-body">
            @include('profile.partials.avatar-upload-form', ['user' => $user])
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Informations</h3>
                <p class="widget-subtitle">Nom et adresse e-mail</p>
            </div>
        </div>
        <div class="widget-body">
            <form method="POST" action="{{ route('profile.update') }}" class="profile-info-form">
                @csrf
                @method('PATCH')
                <div class="modern-form-field">
                    <label class="modern-form-label" for="name">Nom complet</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="modern-input" autocomplete="name">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="modern-form-field">
                    <label class="modern-form-label" for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="modern-input" autocomplete="username">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </section>

    <section class="widget-card glass-card">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Mot de passe</h3>
                <p class="widget-subtitle">Utilisez un mot de passe long et sécurisé.</p>
            </div>
        </div>
        <div class="widget-body">
            <form method="POST" action="{{ route('password.update') }}" class="profile-info-form">
                @csrf
                @method('PUT')
                <div class="modern-form-field">
                    <label class="modern-form-label" for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" class="modern-input" autocomplete="current-password">
                    @error('current_password', 'updatePassword')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="modern-form-field">
                    <label class="modern-form-label" for="password">Nouveau mot de passe</label>
                    <input type="password" id="password" name="password" class="modern-input" autocomplete="new-password">
                    @error('password', 'updatePassword')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="modern-form-field">
                    <label class="modern-form-label" for="password_confirmation">Confirmer</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="modern-input" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="fas fa-lock"></i> Mettre à jour
                </button>
            </form>
        </div>
    </section>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile-avatar.css') }}">
<style>.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.profile-avatar-card{grid-column:1/-1}@media(max-width:900px){.profile-grid{grid-template-columns:1fr}}</style>
@endsection

@section('scripts')
<script src="{{ asset('js/profile-avatar.js') }}" defer></script>
@endsection
