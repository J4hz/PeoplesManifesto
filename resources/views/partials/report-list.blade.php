{{-- Anonymous on purpose: there is no author on this page and the identity table is never loaded. --}}
<ul class="issue-list">
    @foreach ($reports as $report)
        <li class="card card-tight stack">
            <div class="row row-between">
                <span class="small muted">
                    {{ $report->created_at->translatedFormat('j M Y, H:i') }}
                    &middot; {{ $report->ward->name }}@if ($report->area), {{ $report->area->name }}@endif
                    &middot; {{ __('civic.channel.'.$report->channel) }}
                </span>
                <span class="row">
                    @if ($report->severity)<x-severity-tag :severity="$report->severity" />@endif
                    @if ($report->category)<span class="small">{{ __('civic.category.'.$report->category) }}</span>@endif
                </span>
            </div>

            @if ($report->contains_personal_info)
                <p class="muted">{{ __('leader.reports.hidden') }}</p>
            @else
                <p>{{ $report->translation ?: $report->body }}</p>
                @if ($report->translation && $report->body !== $report->translation)
                    <details>
                        <summary class="small">{{ __('leader.respond.original', ['language' => __('leader.languages.'.($report->original_language ?? 'other'))]) }}</summary>
                        <p>{{ $report->body }}</p>
                    </details>
                @endif
            @endif

            <p class="small">
                @if ($report->cluster)
                    {{ __('leader.reports.in_issue') }}
                    <a href="{{ route($issueRoute, $report->cluster) }}">{{ $report->cluster->title }}</a>
                    &middot; <x-status-pill :status="$report->cluster->status" />
                    &middot; {{ trans_choice('civic.residents_count', $report->cluster->resident_count, ['count' => $report->cluster->resident_count]) }}
                @elseif ($report->processing_status === 'failed')
                    <span class="muted">{{ __('citizen.reports.waiting') }}</span>
                @else
                    <span class="muted">{{ __('citizen.reports.sorting') }}</span>
                @endif
            </p>
        </li>
    @endforeach
</ul>
