@extends(auth()->user()->role === 'professor' ? 'layouts.professor' : 'layouts.admin')

@section('title', 'Modifier le cours')

@section('content')
<x-admin-page-header kicker="Contenu" title="Modifier le cours" subtitle="Mettez à jour les informations du cours." />

@if(session('error'))
    <div class="flash-toast flash-toast--error" style="margin-bottom:16px;">
        <i class="fas fa-exclamation-circle"></i>
        {{ session('error') }}
    </div>
@endif

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        <form method="POST" action="{{ route('courses.update', $course) }}" enctype="multipart/form-data" class="form-grid" style="display:grid;gap:16px;max-width:640px;">
            @csrf
            @method('PUT')

            <label>Titre
                <input type="text" name="title" class="form-input" required value="{{ old('title', $course->title) }}">
            </label>

            <label>Description
                <textarea name="description" class="form-input" rows="5" required>{{ old('description', $course->description) }}</textarea>
            </label>

            <label>Catégorie
                <select name="category_id" class="form-input" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $course->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>Difficulté
                <select name="difficulty" class="form-input">
                    <option value="">—</option>
                    <option value="débutant" @selected(old('difficulty', $course->difficulty) == 'débutant')>Débutant</option>
                    <option value="intermédiaire" @selected(old('difficulty', $course->difficulty) == 'intermédiaire')>Intermédiaire</option>
                    <option value="avancé" @selected(old('difficulty', $course->difficulty) == 'avancé')>Avancé</option>
                </select>
            </label>

            <label>Prix (XOF, 0 = gratuit)
                <input type="number" name="price" class="form-input" min="0" step="1" value="{{ old('price', (int) $course->price) }}">
            </label>

            <label>Miniature du cours (laisser vide pour conserver l'actuelle)
                @if($course->thumbnail)
                    <div style="margin-bottom: 8px;">
                        <img src="{{ $course->thumbnailUrl() }}" alt="" style="width: 120px; border-radius: var(--radius-sm);">
                    </div>
                @endif
                <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
            </label>

            <label>Lien vidéo (YouTube embed)
                <input type="url" name="video_url" class="form-input" value="{{ old('video_url', $course->video_url) }}">
            </label>

            <label>Ou téléverser une nouvelle vidéo
                @if($course->video_path)
                    <div style="margin-bottom: 8px; font-size: 0.85rem; color: var(--text-muted);">
                        <i class="fas fa-video"></i> Vidéo existante stockée sur le serveur.
                    </div>
                @endif
                <input type="file" name="video" accept="video/mp4,video/webm,video/ogg" class="form-input">
            </label>

            <div style="display:flex; gap:12px; margin-top:8px;">
                <button type="submit" class="btn btn-primary btn-glow"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                <a href="{{ auth()->user()->role === 'professor' ? route('professor.courses.index') : route('admin.courses') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
</section>
@endsection
