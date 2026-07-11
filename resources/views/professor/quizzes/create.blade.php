@extends('layouts.professor')

@section('title', 'Créer un quiz')

@section('content')
<x-admin-page-header kicker="{{ $course->title }}" title="Nouveau quiz" />

<form method="POST" action="{{ route('professor.quizzes.store', $course) }}" class="widget-card glass-card">
    @csrf
    <div class="widget-body">
        <div class="form-group"><label>Titre</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
            <div><label>Score requis (%)</label><input type="number" name="passing_score" value="70" min="1" max="100" class="form-control"></div>
            <div><label>Tentatives max</label><input type="number" name="max_attempts" value="3" min="1" max="10" class="form-control"></div>
            <div><label>Durée (min)</label><input type="number" name="time_limit_minutes" class="form-control"></div>
        </div>
        <label style="display:block;margin:16px 0 8px;"><input type="checkbox" name="is_published" value="1"> Publier immédiatement</label>

        <h3 style="margin:24px 0 12px;">Question 1</h3>
        <select name="questions[0][type]" class="form-control" style="margin-bottom:8px;">
            <option value="multiple_choice">Choix multiple</option>
            <option value="true_false">Vrai / Faux</option>
            <option value="short_answer">Réponse courte</option>
            <option value="essay">Dissertation</option>
        </select>
        <input type="text" name="questions[0][question]" placeholder="Intitulé de la question" class="form-control" required style="margin-bottom:8px;">
        <input type="text" name="questions[0][options][]" placeholder="Option A" class="form-control" style="margin-bottom:6px;">
        <input type="text" name="questions[0][options][]" placeholder="Option B" class="form-control" style="margin-bottom:6px;">
        <input type="text" name="questions[0][correct_answer]" placeholder="Bonne réponse" class="form-control" style="margin-bottom:6px;">
        <input type="hidden" name="questions[0][points]" value="1">

        <button type="submit" class="btn btn-primary" style="margin-top:20px;">Enregistrer le quiz</button>
    </div>
</form>
@endsection
