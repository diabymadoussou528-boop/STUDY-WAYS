@props([
    'name',
    'role',
    'message',
    'rating' => 5,
    'avatarUrl' => null,
    'index' => 0,
])

<article class="testi-card" data-index="{{ $index }}">
    <div class="testi-card-inner">
        <span class="testi-card-quote-icon" aria-hidden="true">“</span>

        <div class="testi-card-avatar-wrap">
            <div class="testi-card-avatar">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $name }}">
                @else
                    <span class="testi-card-avatar-fallback"><i class="fas fa-user"></i></span>
                @endif
            </div>
            <span class="testi-card-verified" aria-hidden="true"><i class="fas fa-check"></i></span>
        </div>

        <div class="testi-card-content">
            <h3 class="testi-card-name">{{ $name }}</h3>
            <p class="testi-card-role">{{ $role }}</p>
            <div class="testi-card-stars" aria-label="{{ $rating }} sur 5">
                @for($s = 0; $s < min(5, max(1, (int) $rating)); $s++)
                    <i class="fas fa-star"></i>
                @endfor
            </div>
            <hr class="testi-card-divider">
            <blockquote class="testi-card-quote">
                <p class="testi-card-text">« {{ $message }} »</p>
            </blockquote>
        </div>
    </div>
</article>
