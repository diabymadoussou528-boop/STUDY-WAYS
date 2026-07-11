@props([
    'kicker' => null,
    'title',
    'subtitle' => null,
    'accent' => null,
])

<div class="dash-header reveal-up">
    <div class="dash-header-main">
        @if($kicker)
            <span class="dash-kicker"><span class="pulse-dot"></span> {{ $kicker }}</span>
        @endif
        <h1 class="dash-title">
            @if($accent)
                {!! str_replace($accent, '<span class="dash-title-accent">'.$accent.'</span>', e($title)) !!}
            @else
                {{ $title }}
            @endif
        </h1>
        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(trim($slot))
        <div class="dash-header-aside">
            {{ $slot }}
        </div>
    @endif
</div>
