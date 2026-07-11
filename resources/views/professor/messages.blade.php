@extends('layouts.professor')

@section('title', 'Messages')

@section('content')
<x-admin-page-header kicker="Communication" title="Messages" subtitle="Échanges avec vos étudiants inscrits.">
    @if($unreadCount > 0)
        <span class="badge badge-warning">{{ $unreadCount }} non lu(s)</span>
    @endif
</x-admin-page-header>

<div class="sw-chat-layout reveal-up" id="messagingApp"
    data-thread-url="{{ route('professor.messages.thread') }}"
    data-send-url="{{ route('professor.messages.send') }}"
    data-user-id="{{ auth()->id() }}"
    data-user-avatar="{{ auth()->user()->avatarUrl() }}"
    data-csrf="{{ csrf_token() }}">

    <aside class="widget-card glass-card sw-chat-sidebar">
        <div class="widget-header"><h3 class="widget-title">Conversations</h3></div>
        <div class="widget-body">
            <input type="search" id="convSearch" class="modern-input" placeholder="Rechercher..." style="margin-bottom:12px;">
            <div class="sw-conv-list" id="convList">
                @forelse($conversations as $conv)
                    <div class="sw-conv-item"
                         data-other-id="{{ $conv['other_user_id'] }}"
                         data-course-id="{{ $conv['course_id'] }}"
                         data-name="{{ $conv['other_user']?->name }}"
                         data-avatar="{{ $conv['other_user']?->avatarUrl() }}"
                         data-course="{{ $conv['course']?->title }}">
                        <img src="{{ $conv['other_user']?->avatarUrl() }}" alt="">
                        <div class="sw-conv-body">
                            <div class="sw-conv-name">{{ $conv['other_user']?->name }}</div>
                            <div class="sw-conv-preview">{{ Str::limit($conv['last_message']->body, 48) }}</div>
                        </div>
                        @if($conv['unread_count'] > 0)
                            <span class="sw-conv-badge">{{ $conv['unread_count'] }}</span>
                        @endif
                    </div>
                @empty
                    <div class="empty-state premium-empty"><p>Aucun message reçu.</p></div>
                @endforelse
            </div>
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
                    <p>Sélectionnez un étudiant pour répondre.</p>
                </div>
            </div>
            <div class="sw-chat-compose" id="chatCompose" style="display:none;">
                <div class="sw-chat-compose-row">
                    <textarea id="chatInput" class="sw-chat-input" rows="2" placeholder="Votre réponse..."></textarea>
                    <button type="button" class="btn btn-primary btn-glow" id="chatSendBtn"><i class="fas fa-paper-plane"></i></button>
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
