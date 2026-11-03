@if ($paginator->hasPages())
    <nav class="pagination" aria-label="{{ __('civic.pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="btn btn-quiet btn-small" aria-disabled="true">{{ __('pagination.previous_short') }}</span>
        @else
            <a class="btn btn-quiet btn-small" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous_short') }}</a>
        @endif
        <span class="muted small">{{ __('civic.page_of', ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage()]) }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn-quiet btn-small" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next_short') }}</a>
        @else
            <span class="btn btn-quiet btn-small" aria-disabled="true">{{ __('pagination.next_short') }}</span>
        @endif
    </nav>
@endif
