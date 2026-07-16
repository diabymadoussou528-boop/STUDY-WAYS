@extends('layouts.student')

@section('title', 'Tuteur IA')

@section('content')
<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Intelligence artificielle</span>
        <h1 class="dash-title">Tuteur <span class="dash-title-accent">IA</span></h1>
        <p class="dash-subtitle">Posez vos questions, demandez des explications, des exemples, des quiz ou des recommandations personnalisées.</p>
        <div class="dash-chips">
            <span class="dash-chip"><i class="fas fa-robot"></i> Style ChatGPT</span>
            <span class="dash-chip"><i class="fas fa-book"></i> Contexte par cours</span>
            @unless($isPremium)
                <span class="dash-chip"><i class="fas fa-crown"></i> Premium requis pour discuter</span>
            @endunless
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
    data-create-url="{{ route('student.ai-tutor.conversations.store') }}"
    data-rename-url="{{ url('/student/ai-tutor/conversations') }}"
    data-delete-url="{{ url('/student/ai-tutor/conversations') }}"
    data-user-avatar="{{ auth()->user()->avatarUrl() }}"
    data-csrf="{{ csrf_token() }}"
    data-is-premium="{{ $isPremium ? '1' : '0' }}"
    data-conversation-id="{{ $activeConversationId }}">

    <div class="sw-ai-layout">
        <aside class="sw-ai-sidebar">
            <div class="sw-ai-sidebar__head">
                <strong>Conversations</strong>
                <button type="button" class="btn btn-primary btn-sm" id="aiNewConversation"><i class="fas fa-plus"></i></button>
            </div>
            <ul class="sw-ai-conversation-list" id="aiConversationList">
                @forelse($conversations as $conversation)
                    <li class="sw-ai-conversation {{ (int) $activeConversationId === (int) $conversation->id ? 'is-active' : '' }}" data-id="{{ $conversation->id }}">
                        <button type="button" class="sw-ai-conversation__open" data-open-conversation="{{ $conversation->id }}">{{ $conversation->title }}</button>
                        <button type="button" class="sw-ai-conversation__delete" data-delete-conversation="{{ $conversation->id }}" aria-label="Supprimer"><i class="fas fa-trash"></i></button>
                    </li>
                @empty
                    <li class="sw-ai-conversation-empty">Aucune conversation</li>
                @endforelse
            </ul>
        </aside>

        <div class="widget-body sw-ai-main" style="display:flex;flex-direction:column;min-height:640px;">
            <div class="sw-chat-context">
                <div class="modern-form-field" style="margin:0;">
                    <label class="modern-form-label" for="aiCourseSelect">Cours inscrit</label>
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

            <div class="sw-ai-modes" id="aiModes">
                <button type="button" class="sw-ai-mode is-active" data-mode="chat">Discussion</button>
                <button type="button" class="sw-ai-mode" data-mode="explain" data-prompt="Explique-moi ce concept clairement avec une analogie puis un détail technique.">Expliquer</button>
                <button type="button" class="sw-ai-mode" data-mode="examples" data-prompt="Donne-moi des exemples concrets et progressifs sur ce sujet.">Exemples</button>
                <button type="button" class="sw-ai-mode" data-mode="quiz" data-prompt="Génère un quiz de 5 questions pour tester ma compréhension.">Quiz</button>
                <button type="button" class="sw-ai-mode" data-mode="recommend" data-prompt="Recommande-moi un plan d'étude personnalisé pour progresser.">Recommandations</button>
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
                                Sélectionnez un cours, choisissez un mode, puis posez votre question.
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
                                <div class="sw-chat-content" data-md>{{ $msg['content'] }}</div>
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
                        <textarea id="aiChatInput" class="sw-chat-input" rows="2" placeholder="Posez votre question..." @disabled(! $isPremium)></textarea>
                        <button type="button" class="btn btn-primary btn-glow" id="aiSendBtn" @disabled(! $isPremium)>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    @unless($isPremium)
                        <p class="sw-chat-premium-hint">Passez Premium pour envoyer des messages au tuteur IA.</p>
                    @endunless
                </div>
            @endif
        </div>
    </div>
</section>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/student-chat.css') }}">
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="{{ asset('js/student-ai-tutor.js') }}" defer></script>
@endsection
