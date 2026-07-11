@php
    $fieldId = 'temp-password-' . ($adminId ?? 'new');
@endphp

<div class="temp-password-block">
    <label class="modern-form-label" for="{{ $fieldId }}">Mot de passe temporaire</label>
    <div class="temp-password-field">
        <input
            type="text"
            id="{{ $fieldId }}"
            class="modern-input"
            value="{{ $display['password'] ?? '' }}"
            readonly
            aria-readonly="true"
        >
        <button type="button" class="btn btn-outline btn-sm btn-copy" data-copy-password data-copy-target="{{ $fieldId }}">
            <i class="fas fa-copy"></i> Copier
        </button>
    </div>
    <p class="temp-password-notice">
        <i class="fas fa-lock"></i>
        Ce mot de passe est affiché une seule fois. Il a été généré automatiquement et hashé en base de données.
        @if($display['email_sent'] ?? true)
            Un e-mail a été envoyé à l'administrateur.
        @endif
    </p>

    @if(! empty($showRegenerate) && ! empty($admin))
        <form method="POST" action="{{ route('admin.admins.temporary-password', $admin) }}" style="margin-top:12px;">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Générer un nouveau mot de passe temporaire ? L\'ancien ne fonctionnera plus et un e-mail sera renvoyé.')">
                <i class="fas fa-rotate"></i> Régénérer le mot de passe temporaire
            </button>
        </form>
    @endif
</div>
