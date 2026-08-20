@if ($paginator->hasPages())
<nav class="pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    @if ($paginator->onFirstPage())
        <span class="pagination-nav is-disabled">‹ Précédent</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-nav">‹ Précédent</a>
    @endif

    <span class="pagination-pages">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-dots">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-page is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination-page">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </span>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-nav">Suivant ›</a>
    @else
        <span class="pagination-nav is-disabled">Suivant ›</span>
    @endif
</nav>
@endif
