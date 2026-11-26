@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Постраничная навигация">
        @if ($paginator->onFirstPage())
            <span class="is-disabled">← Назад</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Назад</a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page === $paginator->currentPage())
                <span class="is-current" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд →</a>
        @else
            <span class="is-disabled">Вперёд →</span>
        @endif
    </nav>
@endif

<p class="pagination-info">
    Показано {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} из {{ $paginator->total() }} записей
</p>