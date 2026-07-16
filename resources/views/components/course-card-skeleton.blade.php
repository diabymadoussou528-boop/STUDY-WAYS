@props(['count' => 6])

<div {{ $attributes->class(['sw-skeleton-grid']) }} aria-hidden="true">
    @for($i = 0; $i < $count; $i++)
        <div class="sw-skeleton-card">
            <div class="sw-skeleton-card__media"></div>
            <div class="sw-skeleton-card__body">
                <div class="sw-skeleton-line sw-skeleton-line--meta"></div>
                <div class="sw-skeleton-line sw-skeleton-line--title"></div>
                <div class="sw-skeleton-line sw-skeleton-line--text"></div>
                <div class="sw-skeleton-line sw-skeleton-line--short"></div>
            </div>
        </div>
    @endfor
</div>
