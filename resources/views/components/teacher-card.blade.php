@props(['instructor'])

<article {{ $attributes->class(['sw-teacher-card']) }}>
    <img src="{{ $instructor['avatar'] }}" alt="{{ $instructor['name'] }}" class="sw-teacher-card__avatar">
    <div class="sw-teacher-card__body">
        <div class="sw-teacher-card__eyebrow">Instructeur</div>
        <h3 class="sw-teacher-card__name">{{ $instructor['name'] }}</h3>
        @if(!empty($instructor['specialization']))
            <p class="sw-teacher-card__role">{{ $instructor['specialization'] }}</p>
        @endif

        <x-course-rating :rating="$instructor['rating']" :reviews="$instructor['reviewsCount']" size="sm" />

        <p class="sw-teacher-card__bio">
            {{ $instructor['bio'] ?? 'Instructeur passionné par le partage de connaissances et l’accompagnement des apprenants.' }}
        </p>

        <div class="sw-teacher-card__stats">
            <span><i class="fas fa-book-open"></i> {{ $instructor['coursesCount'] }} cours</span>
            <span><i class="fas fa-users"></i> {{ number_format($instructor['studentsCount']) }} étudiants</span>
        </div>
    </div>
</article>
