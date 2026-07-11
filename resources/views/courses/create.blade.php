@extends('layouts.professor')

@section('title', 'Nouveau cours')

@section('content')
<x-admin-page-header kicker="Contenu" title="Ajouter un cours" subtitle="Le cours sera enregistré en brouillon." />

@if(auth()->user()?->specialization)
    <p style="margin-bottom:16px;color:#555;">
        <i class="fas fa-graduation-cap" style="color:#8B2032;"></i>
        Votre spécialisation : <strong>{{ auth()->user()->specialization }}</strong>
    </p>
@endif

<section class="widget-card glass-card reveal-up">
    <div class="widget-body">
        <form method="POST" action="{{ route('courses.store') }}" enctype="multipart/form-data" class="form-grid" style="display:grid;gap:16px;max-width:640px;">
            @csrf

            <label>Titre
                <input type="text" name="title" class="form-input" required value="{{ old('title') }}">
            </label>

            <label>Description
                <textarea name="description" class="form-input" rows="5" required>{{ old('description') }}</textarea>
            </label>

            <label>Catégorie
                <select name="category_id" class="form-input" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>Difficulté
                <select name="difficulty" class="form-input">
                    <option value="">—</option>
                    <option value="débutant">Débutant</option>
                    <option value="intermédiaire">Intermédiaire</option>
                    <option value="avancé">Avancé</option>
                </select>
            </label>

            <label>Prix (XOF, 0 = gratuit)
                <input type="number" name="price" class="form-input" min="0" step="1" value="{{ old('price', 0) }}">
            </label>

            <label>Miniature du cours
                <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
            </label>

            <label>Lien vidéo (YouTube embed)
                <input type="url" name="video_url" class="form-input" value="{{ old('video_url') }}">
            </label>

            <label>Ou téléverser une vidéo
                <input type="file" name="video" accept="video/mp4,video/webm,video/ogg" class="form-input">
            </label>

            <button type="submit" class="btn btn-primary btn-glow"><i class="fas fa-save"></i> Enregistrer en brouillon</button>
        </form>
    </div>
</section>
@endsection
