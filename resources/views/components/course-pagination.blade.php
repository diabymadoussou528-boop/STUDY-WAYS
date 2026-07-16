@props(['paginator'])

@if($paginator->hasPages())
    <div {{ $attributes->class(['sw-pagination-shell']) }}>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
