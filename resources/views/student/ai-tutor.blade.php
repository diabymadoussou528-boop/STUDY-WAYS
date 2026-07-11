@extends('layouts.student')

@section('title', 'Tuteur IA')

@section('content')
<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Intelligence artificielle</span>
        <h1 class="dash-title">Tuteur <span class="dash-title-accent">IA</span></h1>
        <p class="dash-subtitle">Assistant personnalisé pour vos questions, révisions et évaluations de niveau.</p>
        <div class="dash-chips">
            <span class="dash-chip"><i class="fas fa-robot"></i> Réponses en temps réel</span>
            <span class="dash-chip"><i class="fas fa-book"></i> Contexte par cours</span>
        </div>
    </div>
    <div class="dash-header-aside">
        <button type="button" class="btn btn-outline" id="aiEvaluateBtn">
            <i class="fas fa-chart-line"></i> Évaluer mon niveau
        </button>
        <button type="button" class="btn btn-outline" id="aiClearBtn">
            <i class="fas fa-trash-alt"></i> Effacer
        </button>
    </div>
</div>

<section class="widget-card glass-card reveal-up sw-chat-panel" id="aiTutorApp"
    data-chat-url="{{ route('student.ai-tutor.chat') }}"
    data-clear-url="{{ route('student.ai-tutor.clear') }}"
    data-history-url="{{ route('student.ai-tutor.history') }}"
    data-user-avatar="{{ auth()->user()->avatarUrl() }}"
    data-csrf="{{ csrf_token() }}">

    <div class="widget-body" style="display:flex;flex-direction:column;min-height:600px;">
        <div class="sw-chat-context">
            <div class="modern-form-field" style="margin:0;">
                <label class="modern-form-label" for="aiCourseSelect">Cours</label>
                <select id="aiCourseSelect" class="modern-input">
                    <option value="">— Sélectionner un cours —</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" data-lessons='@json($course->lessons_json)'>
                            {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="modern-form-field" style="margin:0;">
                <label class="modern-form-label" for="aiLessonSelect">Leçon</label>
                <select id="aiLessonSelect" class="modern-input" disabled>
                    <option value="">— Choisir une leçon —</option>
                </select>
            </div>
            <div class="modern-form-field" style="margin:0;">
                <label class="modern-form-label" for="aiTopicInput">Sujet / thème</label>
                <input type="text" id="aiTopicInput" class="modern-input" placeholder="Ex : Boucles, variables...">
            </div>
        </div>

        @if($courses->isEmpty())
            <div class="empty-state premium-empty" style="flex:1;">
                <i class="fas fa-book-open"></i>
                <p>Inscrivez-vous à un cours pour utiliser le tuteur IA avec un contexte personnalisé.</p>
                <a href="{{ route('home') }}#catalogue" class="btn btn-primary btn-sm" style="margin-top:12px;">Explorer les cours</a>
            </div>
        @else
            <div class="sw-chat-messages" id="aiChatMessages">
                <div class="sw-chat-bubble sw-chat-bubble--assistant" id="aiWelcome">
                    <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
                    <div>
                        <div class="sw-chat-content">
                            Bonjour {{ explode(' ', auth()->user()->name)[0] }} ! Je suis votre tuteur IA StudyWays.
                            Sélectionnez un cours, posez vos questions ou cliquez sur « Évaluer mon niveau » pour une analyse personnalisée.
                        </div>
                    </div>
                </div>
                @foreach($history as $msg)
                    <div class="sw-chat-bubble sw-chat-bubble--{{ $msg['role'] === 'user' ? 'user' : 'assistant' }}">
                        @if($msg['role'] === 'assistant')
                            <div class="sw-chat-avatar sw-chat-avatar--ai"><i class="fas fa-robot"></i></div>
                        @else
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="sw-chat-avatar">
                        @endif
                        <div>
                            <div class="sw-chat-content">{!! nl2br(e($msg['content'])) !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sw-suggested-questions" id="aiSuggested">
                @foreach($suggestedQuestions as $question)
                    <button type="button" class="sw-suggested-btn" data-suggest="{{ $question }}">{{ $question }}</button>
                @endforeach
            </div>

            <div class="sw-chat-compose">
                <div class="sw-chat-compose-row">
                    <textarea id="aiChatInput" class="sw-chat-input" rows="2" placeholder="Posez votre question..."></textarea>
                    <button type="button" class="btn btn-primary btn-glow" id="aiSendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/student-chat.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('js/student-ai-tutor.js') }}" defer></script>
@endsection
