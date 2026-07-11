document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-avatar-upload]').forEach((root) => {
        const input = root.querySelector('[data-avatar-input]');
        const preview = root.querySelector('[data-avatar-preview]');
        const form = root.querySelector('[data-avatar-form]');
        const loading = root.querySelector('[data-avatar-loading]');
        const removeBtn = root.querySelector('[data-avatar-remove]');

        if (!input || !preview || !form) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                preview.src = event.target?.result ?? preview.src;
            };
            reader.readAsDataURL(file);

            root.classList.add('is-uploading');
            if (loading) {
                loading.hidden = false;
            }

            form.submit();
        });

        removeBtn?.addEventListener('click', () => {
            const removeForm = document.getElementById('avatar-remove-form');

            if (!removeForm || !confirm('Supprimer votre photo de profil ?')) {
                return;
            }

            root.classList.add('is-uploading');
            if (loading) {
                loading.hidden = false;
            }

            removeForm.submit();
        });
    });

    document.querySelectorAll('[data-copy-password]').forEach((button) => {
        button.addEventListener('click', async () => {
            const targetId = button.dataset.copyTarget;
            const input = targetId ? document.getElementById(targetId) : null;

            if (!input?.value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(input.value);
                button.classList.add('is-copied');
                const original = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copié';

                setTimeout(() => {
                    button.classList.remove('is-copied');
                    button.innerHTML = original;
                }, 2000);
            } catch {
                input.select();
                document.execCommand('copy');
            }
        });
    });
});
