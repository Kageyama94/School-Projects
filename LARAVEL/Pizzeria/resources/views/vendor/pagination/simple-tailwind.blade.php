@if ($paginator->hasPages())
<nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    @if ($paginator->onFirstPage())
        <span class="pagination-nav is-disabled">‹ Précédent</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-nav">‹ Précédent</a>
    @endif

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-nav">Suivant ›</a>
    @else
        <span class="pagination-nav is-disabled">Suivant ›</span>
    @endif
</nav>
@endif
