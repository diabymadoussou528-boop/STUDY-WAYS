@extends('layouts.student')

@section('title', $quiz->title)

@section('content')
<x-admin-page-header kicker="{{ $quiz->course?->title }}" :title="$quiz->title" subtitle="Répondez à toutes les questions puis soumettez." />

<form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="widget-card glass-card">
    @csrf
    <div class="widget-body">
        @foreach($quiz->questions as $index => $question)
            <div style="margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid #eee;">
                <strong>{{ $index + 1 }}. {{ $question->question }}</strong>
                <div style="margin-top:12px;">
                    @if($question->type->value === 'multiple_choice' && is_array($question->options))
                        @foreach($question->options as $option)
                            <label style="display:block;margin-bottom:8px;"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}"> {{ $option }}</label>
                        @endforeach
                    @elseif($question->type->value === 'true_false')
                        <label style="margin-right:16px;"><input type="radio" name="answers[{{ $question->id }}]" value="true"> Vrai</label>
                        <label><input type="radio" name="answers[{{ $question->id }}]" value="false"> Faux</label>
                    @else
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="form-control" style="width:100%;padding:10px;border-radius:10px;border:1px solid #ddd;"></textarea>
                    @endif
                </div>
            </div>
        @endforeach
        <button type="submit" class="btn btn-primary">Soumettre le quiz</button>
    </div>
</form>
@endsection
