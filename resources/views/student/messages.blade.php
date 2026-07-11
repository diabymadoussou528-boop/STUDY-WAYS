@extends('layouts.student')

@section('title', 'Messages')

@section('content')
<div class="dash-header reveal-up">
    <div class="dash-header-main">
        <span class="dash-kicker"><span class="pulse-dot"></span> Communication</span>
        <h1 class="dash-title">Mes <span class="dash-title-accent">messages</span></h1>
        <p class="dash-subtitle">Échangez avec les professeurs de vos cours inscrits.</p>
    </div>
    <div class="dash-header-aside">
        @if($unreadCount > 0)
            <span class="badge badge-warning">{{ $unreadCount }} non lu(s)</span>
        @endif
    </div>
</div>

<div class="sw-chat-layout reveal-up" id="messagingApp"
    data-thread-url="{{ route('student.messages.thread') }}"
    data-send-url="{{ route('student.messages.send') }}"
    data-user-id="{{ auth()->id() }}"
    data-user-avatar="{{ auth()->user()->avatarUrl() }}"
    data-csrf="{{ csrf_token() }}">

    <aside class="widget-card glass-card sw-chat-sidebar">
        <div class="widget-header">
            <h3 class="widget-title">Conversations</h3>
        </div>
        <div class="widget-body">
            <div class="modern-form-field" style="margin-bottom:12px;">
                <input type="search" id="convSearch" class="modern-input" placeholder="Rechercher...">
            </div>
            <div class="sw-conv-list" id="convList">
                @forelse($conversations as $conv)
                    <div class="sw-conv-item"
                         data-key="{{ $conv['key'] }}"
                         data-other-id="{{ $conv['other_user_id'] }}"
                         data-course-id="{{ $conv['course_id'] }}"
                         data-name="{{ $conv['other_user']?->name }}"
                         data-avatar="{{ $conv['other_user']?->avatarUrl() }}"
                         data-course="{{ $conv['course']?->title }}">
                        <img src="{{ $conv['other_user']?->avatarUrl() }}" alt="">
                        <div class="sw-conv-body">
                            <div class="sw-conv-name">{{ $conv['other_user']?->name }}</div>
                            <div class="sw-conv-preview">{{ Str::limit($conv['last_message']->body, 48) }}</div>
                            <span class="dash-chip" style="margin-top:4px;font-size:0.7rem;"><i class="fas fa-book"></i> {{ Str::limit($conv['course']?->title, 24) }}</span>
                        </div>
                        @if($conv['unread_count'] > 0)
                            <span class="sw-conv-badge">{{ $conv['unread_count'] }}</span>
                        @endif
                    </div>
                @empty
                    <div class="empty-state premium-empty" id="noConversations">
                        <i class="fas fa-envelope-open"></i>
                        <p>Aucune conversation pour le moment.</p>
                    </div>
                @endforelse
            </div>

            @if($contacts->isNotEmpty())
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,0.06);">
                    <p class="widget-subtitle" style="margin-bottom:10px;">Nouveau message</p>
                    <select id="newConvSelect" class="modern-input">
                        <option value="">— Professeur / cours —</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact['teacher_id'] }}:{{ $contact['course_id'] }}"
                                data-teacher="{{ $contact['teacher']->name }}"
                                data-avatar="{{ $contact['teacher']->avatarUrl() }}"
                                data-course="{{ $contact['course_title'] }}">
                                {{ $contact['teacher']->name }} — {{ Str::limit($contact['course_title'], 28) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" style="margin-top:10px;width:100%;" id="startConvBtn">
                        <i class="fas fa-plus"></i> Démarrer
                    </button>
                </div>
            @endif
        </div>
    </aside>

    <section class="widget-card glass-card sw-chat-panel">
        <div class="widget-body" style="display:flex;flex-direction:column;min-height:560px;">
            <div class="sw-chat-header" id="chatHeader">
                <div class="user-cell" id="chatHeaderUser" style="display:none;">
                    <img src="" alt="" class="user-cell-avatar" id="chatHeaderAvatar">
                    <div>
                        <div class="user-cell-name" id="chatHeaderName"></div>
                        <div class="user-cell-sub" id="chatHeaderCourse"></div>
                    </div>
                </div>
                <span class="text-muted" id="chatHeaderPlaceholder">Sélectionnez une conversation</span>
            </div>

            <div class="sw-chat-messages" id="chatMessages">
                <div class="empty-state premium-empty" id="chatEmpty">
                    <i class="fas fa-comments"></i>
                    <p>Choisissez une conversation ou démarrez un nouvel échange avec un professeur.</p>
                </div>
            </div>

            <div class="sw-chat-compose" id="chatCompose" style="display:none;">
                <div class="sw-chat-compose-row">
                    <textarea id="chatInput" class="sw-chat-input" rows="2" placeholder="Écrivez votre message..."></textarea>
                    <button type="button" class="btn btn-primary btn-glow" id="chatSendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/student-chat.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('js/student-messaging.js') }}" defer></script>
@endsection
