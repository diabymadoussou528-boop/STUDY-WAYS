@extends('layouts.professor')

@section('title', 'Mon profil')

@section('content')
<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Compte · Profil</span>
        <h1 class="dash-title">Mon <span class="dash-title-accent">profil</span></h1>
        <p class="dash-subtitle">Gérez vos informations et votre photo de profil.</p>
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
                <div class="modern-form-field">
                    <label class="modern-form-label" for="specialization">Spécialisation</label>
                    <input type="text" id="specialization" name="specialization" value="{{ old('specialization', $user->specialization) }}" required class="modern-input" placeholder="Ex. : Développement Web, UI/UX...">
                    @error('specialization')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="modern-form-field">
                    <label class="modern-form-label" for="bio">Biographie</label>
                    <textarea id="bio" name="bio" rows="4" class="modern-input" placeholder="Présentez votre parcours et votre expertise...">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-glow">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </section>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile-avatar.css') }}">
<style>.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.profile-avatar-card{grid-column:1/-1}</style>
@endsection

@section('scripts')
<script src="{{ asset('js/profile-avatar.js') }}" defer></script>
@endsection
