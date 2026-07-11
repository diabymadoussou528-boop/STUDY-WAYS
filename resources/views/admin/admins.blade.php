@extends('layouts.admin')

@section('title', 'Gérer les Admins')

@section('content')

<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Super Admin · Gestion</span>
        <h1 class="dash-title">Gestion des <span class="dash-title-accent">administrateurs</span></h1>
        <p class="page-subtitle">Créez, modérez et sécurisez les comptes administrateurs simples.</p>
    </div>
    <div class="dash-header-aside">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<section class="widget-card glass-card reveal-up" style="margin-bottom:22px;">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Créer un administrateur simple</h3>
            <p class="widget-subtitle">Un mot de passe temporaire sera généré et envoyé par e-mail automatiquement.</p>
        </div>
    </div>
    <div class="widget-body">
        <form method="POST" action="{{ route('admin.admins.store') }}" class="modern-form-row">
            @csrf
            <div class="modern-form-field" style="min-width:160px;">
                <label class="modern-form-label">Prénom</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="modern-input" placeholder="Jean">
            </div>
            <div class="modern-form-field" style="min-width:160px;">
                <label class="modern-form-label">Nom</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="modern-input" placeholder="Dupont">
            </div>
            <div class="modern-form-field">
                <label class="modern-form-label">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="modern-input" placeholder="admin@studyways.com">
            </div>
            <div class="modern-form-field" style="min-width:180px;">
                <label class="modern-form-label">Téléphone (optionnel)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="modern-input" placeholder="+223 00 00 00 00">
            </div>
            <button type="submit" class="btn btn-primary btn-glow">
                <i class="fas fa-user-plus"></i> Créer le compte
            </button>
        </form>
        @if($errors->any() && ! $editingAdmin)
            <p class="form-error">{{ $errors->first() }}</p>
        @endif

        @if(! $editingAdmin && ! empty($temporaryPasswordDisplay))
            @include('admin.partials.temporary-password-display', [
                'display' => $temporaryPasswordDisplay,
                'adminId' => $temporaryPasswordDisplay['user_id'] ?? null,
            ])
        @endif
    </div>
</section>

@if($editingAdmin)
    @php [$firstName, $lastName] = $editingAdmin->nameParts(); @endphp
    <section class="widget-card glass-card reveal-up" style="margin-bottom:22px;">
        <div class="widget-header">
            <div>
                <h3 class="widget-title">Modifier {{ $editingAdmin->name }}</h3>
                <p class="widget-subtitle">Mettre à jour les informations du compte</p>
            </div>
            <a href="{{ route('admin.admins') }}" class="btn btn-outline btn-sm">Annuler</a>
        </div>
        <div class="widget-body">
            <form method="POST" action="{{ route('admin.admins.update', $editingAdmin) }}" class="modern-form-row">
                @csrf
                @method('PUT')
                <div class="modern-form-field" style="min-width:160px;">
                    <label class="modern-form-label">Prénom</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $firstName) }}" required class="modern-input">
                </div>
                <div class="modern-form-field" style="min-width:160px;">
                    <label class="modern-form-label">Nom</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $lastName) }}" required class="modern-input">
                </div>
                <div class="modern-form-field">
                    <label class="modern-form-label">E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $editingAdmin->email) }}" required class="modern-input">
                </div>
                <div class="modern-form-field" style="min-width:180px;">
                    <label class="modern-form-label">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $editingAdmin->phone) }}" class="modern-input">
                </div>
                <button type="submit" class="btn btn-primary btn-glow">Enregistrer</button>
            </form>
            @if($errors->any())
                <p class="form-error">{{ $errors->first() }}</p>
            @endif

            @if(! empty($temporaryPasswordDisplay) && (int) ($temporaryPasswordDisplay['user_id'] ?? 0) === $editingAdmin->id)
                <div style="margin-top:20px;">
                    @include('admin.partials.temporary-password-display', [
                        'display' => $temporaryPasswordDisplay,
                        'adminId' => $editingAdmin->id,
                        'showRegenerate' => true,
                        'admin' => $editingAdmin,
                    ])
                </div>
            @else
                <div style="margin-top:20px;">
                    <label class="modern-form-label">Mot de passe temporaire</label>
                    <div class="temp-password-field">
                        <input type="text" class="modern-input" value="••••••••••••••" readonly disabled aria-readonly="true">
                    </div>
                    <p class="temp-password-notice">
                        Généré automatiquement par le système. Utilisez « Mot de passe temporaire » dans le menu actions pour en créer un nouveau.
                    </p>
                    <form method="POST" action="{{ route('admin.admins.temporary-password', $editingAdmin) }}" style="margin-top:12px;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Générer un nouveau mot de passe temporaire ? L\'ancien ne fonctionnera plus.')">
                            <i class="fas fa-rotate"></i> Régénérer le mot de passe temporaire
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </section>
@endif

<section class="widget-card glass-card reveal-up">
    <div class="widget-header">
        <div>
            <h3 class="widget-title">Administrateurs</h3>
            <p class="widget-subtitle">{{ $admins->count() }} compte(s) admin</p>
        </div>
    </div>
    <div class="widget-body widget-body--flush">
        <div class="table-scroll">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="{{ $admin->avatarUrl() }}" alt="" class="user-cell-avatar">
                                <span class="user-cell-name">{{ $admin->name }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="user-cell-name">{{ $admin->email }}</div>
                            @if($admin->phone)
                                <div class="user-cell-sub">{{ $admin->phone }}</div>
                            @endif
                        </td>
                        <td>
                            @if($admin->isSuperAdmin())
                                <span class="badge badge-primary"><i class="fas fa-crown"></i> Super Admin</span>
                            @else
                                <span class="badge badge-admin">Admin simple</span>
                            @endif
                        </td>
                        <td>
                            @if(! $admin->is_active)
                                <span class="badge badge-warning">Suspendu</span>
                            @elseif($admin->first_login)
                                <span class="badge badge-warning">Première connexion</span>
                            @else
                                <span class="badge badge-success">Actif</span>
                            @endif
                        </td>
                        <td><span class="table-date">{{ $admin->created_at->format('d/m/Y') }}</span></td>
                        <td>
                            @if($admin->isSimpleAdmin())
                                <div class="row-actions">
                                    <button type="button" class="row-action-btn" title="Actions"><i class="fas fa-ellipsis-vertical"></i></button>
                                    <div class="row-action-menu">
                                        <a href="{{ route('admin.admins', ['edit' => $admin->id]) }}"><i class="fas fa-pen"></i> Modifier</a>
                                        <form method="POST" action="{{ route('admin.admins.toggle-status', $admin) }}">
                                            @csrf
                                            <button type="submit"><i class="fas fa-user-slash"></i> {{ $admin->is_active ? 'Suspendre' : 'Activer' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.admins.temporary-password', $admin) }}">
                                            @csrf
                                            <button type="submit"><i class="fas fa-key"></i> Mot de passe temporaire</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.admins.reset-link', $admin) }}">
                                            @csrf
                                            <button type="submit"><i class="fas fa-link"></i> Lien de réinitialisation</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.admins.logout', $admin) }}">
                                            @csrf
                                            <button type="submit"><i class="fas fa-power-off"></i> Déconnecter</button>
                                        </form>
                                        @if(auth()->id() !== $admin->id)
                                            <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" onsubmit="return confirm('Supprimer ce compte administrateur ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"><i class="fas fa-trash-alt"></i> Supprimer</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state premium-empty">
                                <i class="fas fa-shield-halved"></i>
                                <p>Aucun administrateur trouvé.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile-avatar.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('js/admin-dashboard.js') }}" defer></script>
<script src="{{ asset('js/profile-avatar.js') }}" defer></script>
@endsection
