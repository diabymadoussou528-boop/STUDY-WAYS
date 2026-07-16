@props([
    'title',
    'thumbnail',
    'videoUrl' => null,
    'isEmbed' => false,
    'label' => 'Aperçu du cours',
    'playerId' => 'sw-course-player',
])

<div
    {{ $attributes->class(['sw-video-player']) }}
    id="{{ $playerId }}"
    data-video-url="{{ $videoUrl ?? '' }}"
    data-is-embed="{{ $isEmbed ? '1' : '0' }}"
>
    <div class="sw-video-player__cover" data-player-cover>
        <img src="{{ $thumbnail }}" alt="{{ $title }}" class="sw-video-player__poster">
        <div class="sw-video-player__shade"></div>
        @if($videoUrl)
            <button type="button" class="sw-video-player__play" data-player-trigger aria-label="Lire la vidéo">
                <span class="sw-video-player__play-ring"></span>
                <i class="fas fa-play"></i>
            </button>
            <div class="sw-video-player__label">
                <i class="fas fa-circle-play"></i>
                <span>{{ $label }}</span>
            </div>
        @else
            <div class="sw-video-player__empty">
                <i class="fas fa-photo-film"></i>
                <span>Aperçu vidéo bientôt disponible</span>
            </div>
        @endif
    </div>

    <div class="sw-video-player__loader" data-player-loader hidden>
        <span class="sw-video-player__spinner"></span>
    </div>

    <div class="sw-video-player__error" data-player-error hidden>
        <i class="fas fa-circle-exclamation"></i>
        <p>Impossible de charger la vidéo.</p>
        <button type="button" class="sw-video-player__retry" data-player-retry>Réessayer</button>
    </div>

    <div class="sw-video-player__media" data-player-media hidden></div>
</div>
