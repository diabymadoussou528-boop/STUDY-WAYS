<div class="avatar-upload" data-avatar-upload>
    <div class="avatar-upload-preview-wrap">
        <div class="avatar-upload-ring">
            <img
                src="{{ $user->avatarUrl() }}"
                alt="{{ $user->name }}"
                class="avatar-upload-preview"
                data-avatar-preview
            >
            <div class="avatar-upload-loading" data-avatar-loading hidden>
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>

    <form
        method="POST"
        action="{{ route('profile.avatar.update') }}"
        enctype="multipart/form-data"
        class="avatar-upload-form"
        data-avatar-form
    >
        @csrf
        <input
            type="file"
            name="avatar"
            id="avatar-input-{{ $user->id }}"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="avatar-upload-input"
            data-avatar-input
        >

        <div class="avatar-upload-actions">
            <label for="avatar-input-{{ $user->id }}" class="btn btn-outline btn-sm avatar-upload-choose">
                <i class="fas fa-camera"></i> Choisir une photo
            </label>

            @if($user->hasUploadedAvatar())
                <button
                    type="button"
                    class="btn btn-outline btn-sm avatar-upload-remove"
                    data-avatar-remove
                    data-remove-url="{{ route('profile.avatar.destroy') }}"
                >
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>
            @endif
        </div>

        @error('avatar')
            <p class="form-error">{{ $message }}</p>
        @enderror

        <p class="avatar-upload-hint">La photo sera redimensionnée et optimisée automatiquement.</p>
    </form>
</div>

<form id="avatar-remove-form" method="POST" action="{{ route('profile.avatar.destroy') }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
