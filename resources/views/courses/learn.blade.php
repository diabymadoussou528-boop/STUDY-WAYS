<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $currentLesson->title }} — {{ $course->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/course-detail.css') }}">
</head>
<body class="course-learn-page">

<header class="learn-header">
    <div class="learn-header__left">
        <a href="{{ route('courses.show', $course) }}" class="learn-back"><i class="fas fa-arrow-left"></i> Retour au cours</a>
        <h1>{{ $course->title }}</h1>
    </div>
    <div class="learn-header__right">
        @if($progressPercent > 0)
            <div class="learn-progress">
                <div class="learn-progress__bar" style="width: {{ $progressPercent }}%"></div>
            </div>
            <span>{{ $progressPercent }}%</span>
        @endif
    </div>
</header>

@if(session('success'))
    <div class="course-flash course-flash--success">{{ session('success') }}</div>
@endif

<div class="learn-layout">
    <aside class="learn-sidebar">
        <h2>Contenu du cours</h2>
        @foreach($modules as $module)
            <div class="learn-module">
                <h3>{{ $module['title'] }}</h3>
                <ul class="learn-lesson-nav">
                    @foreach($module['lessons'] as $lesson)
                        <li class="{{ (int) $currentLesson->id === (int) $lesson['id'] ? 'is-active' : '' }} {{ $lesson['isCompleted'] ? 'is-done' : '' }}">
                            <a href="{{ route('courses.learn', [$course, $lesson['id']]) }}">
                                <i class="fas {{ $lesson['typeIcon'] }}"></i>
                                <span>{{ $lesson['title'] }}</span>
                                @if($lesson['isCompleted'])
                                    <i class="fas fa-check-circle"></i>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </aside>

    <main class="learn-content">
        <div class="learn-content__head">
            <span class="learn-lesson-type"><i class="fas {{ $currentLesson->lesson_type?->icon() ?? 'fa-play-circle' }}"></i> {{ $currentLesson->lesson_type?->label() ?? 'Leçon' }}</span>
            <h2>{{ $currentLesson->title }}</h2>
            <span class="learn-duration"><i class="fas fa-clock"></i> {{ $currentLesson->formattedDuration() }}</span>
        </div>

        @if($currentLesson->lesson_type?->value === 'video' || filled($currentLesson->video_url) || filled($currentLesson->resource_path))
            <div class="learn-player">
                @if($currentLesson->storedVideoUrl())
                    <video controls preload="metadata" src="{{ $currentLesson->storedVideoUrl() }}" title="{{ $currentLesson->title }}"></video>
                @elseif($currentLesson->video_url)
                    <iframe src="{{ $currentLesson->video_url }}" allowfullscreen title="{{ $currentLesson->title }}"></iframe>
                @else
                    <div class="learn-player__placeholder">
                        <i class="fas fa-video"></i>
                        <p>Vidéo non disponible pour cette leçon.</p>
                    </div>
                @endif
            </div>
        @endif

        @if(filled($currentLesson->content))
            <div class="learn-text-content">
                {!! nl2br(e($currentLesson->content)) !!}
            </div>
        @endif

        @if(filled($currentLesson->resource_url) || filled($currentLesson->resource_path))
            <div class="learn-resources">
                <h3>Ressources</h3>
                @if($currentLesson->resource_url)
                    <a href="{{ $currentLesson->resource_url }}" target="_blank" rel="noopener" class="btn btn-outline">
                        <i class="fas fa-external-link-alt"></i> Ouvrir la ressource
                    </a>
                @endif
                @if($currentLesson->resource_path)
                    <a href="{{ $currentLesson->resourceUrl() }}" class="btn btn-outline" download>
                        <i class="fas fa-download"></i> Télécharger
                    </a>
                @endif
            </div>
        @endif

        @auth
            @if($isEnrolled || auth()->user()->isAdmin() || (int) $course->user_id === (int) auth()->id())
                <form method="POST" action="{{ route('courses.lessons.complete', [$course, $currentLesson]) }}" class="learn-complete-form">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Marquer comme terminée
                    </button>
                </form>
            @endif
        @endauth

        @php
            $lessonIds = $allLessons->pluck('id')->all();
            $currentIndex = array_search($currentLesson->id, $lessonIds, true);
            $prevLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
            $nextLesson = $currentIndex !== false && isset($lessonIds[$currentIndex + 1]) ? $allLessons[$currentIndex + 1] : null;
        @endphp

        <div class="learn-nav-buttons">
            @if($prevLesson)
                <a href="{{ route('courses.learn', [$course, $prevLesson]) }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Précédent</a>
            @endif
            @if($nextLesson)
                <a href="{{ route('courses.learn', [$course, $nextLesson]) }}" class="btn btn-primary">Suivant <i class="fas fa-chevron-right"></i></a>
            @endif
        </div>
    </main>
</div>

</body>
</html>
