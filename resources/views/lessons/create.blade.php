@extends('layouts.app')

@section('content')
<h1>Ajouter une leçon</h1>

<form method="POST" action="{{ route('lessons.store') }}" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="course_id" value="{{ $course_id }}">

    <input type="text" name="title" placeholder="Titre"><br><br>

    <input type="file" name="file"><br><br>

    <input type="text" name="video_url" placeholder="Lien YouTube"><br><br>

    <button>Ajouter</button>
</form>
@endsection
