@if ($paginator->total() > 0)
    <nav role="navigation" aria-label="Pagination" class="custom-pagination-container">
        <div class="pagination-left">
            <div class="pagination-summary">
                {{ __('acl::common.showing') }} <span class="fw-bold">{{ $paginator->firstItem() }}</span> {{ __('acl::common.to') }} <span class="fw-bold">{{ $paginator->lastItem() }}</span> {{ __('acl::common.of') }} <span class="fw-bold">{{ $paginator->total() }}</span> {{ __('acl::common.results') }}
            </div>

            <div class="pagination-per-page">
                <label for="perPageSelect" class="per-page-label">{{ __('acl::common.per_page') }}:</label>
                <select id="perPageSelect" class="per-page-select" onchange="window.location.href=this.value">
                    @php
                        $currentPerPage = request('per_page', $paginator->perPage() >= 10000 ? 'all' : $paginator->perPage());
                    @endphp
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 25, 'page' => 1]) }}" {{ $currentPerPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}" {{ $currentPerPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}" {{ $currentPerPage == 100 ? 'selected' : '' }}>100</option>
                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 'all', 'page' => 1]) }}" {{ $currentPerPage === 'all' || $currentPerPage >= 10000 ? 'selected' : '' }}>{{ __('acl::common.all') }}</option>
                </select>
            </div>
        </div>

        @if ($paginator->hasPages())
            <ul class="pagination-list">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">&laquo;</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">&raquo;</span>
                    </li>
                @endif
            </ul>
        @endif
    </nav>
@endif
