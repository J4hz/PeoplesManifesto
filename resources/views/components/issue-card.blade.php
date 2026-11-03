@props(['cluster', 'rank' => null, 'citizen' => null, 'reported' => false, 'rated' => false])
{{-- Shared by the public ward page, the resident's ward list and the leader record. --}}
<li class="card card-tight issue-card">
    <div>
        <h3>
            @if ($rank)<span class="rank">{{ $rank }}</span>@endif
            <a href="{{ route('clusters.show', $cluster) }}">{{ $cluster->title }}</a>
        </h3>
        <div class="issue-meta">
            <x-status-pill :status="$cluster->status" />
            <x-severity-tag :severity="$cluster->severity" />
            <span>{{ __('civic.category.'.$cluster->category) }}</span>
            <span><x-icon name="users" /> {{ trans_choice('civic.residents_count', $cluster->resident_count, ['count' => $cluster->resident_count]) }}</span>
            @if ($cluster->areas_count > 1)
                <span><x-icon name="map-pin" /> {{ trans_choice('civic.areas_count', $cluster->areas_count, ['count' => $cluster->areas_count]) }}</span>
            @endif
        </div>
    </div>
    @if ($citizen)
        <div class="issue-actions">
            @if ($cluster->isDone() && $reported && ! $rated && $cluster->isRatingOpen())
                <a class="btn btn-small" href="{{ route('citizen.rate', $cluster) }}">{{ __('citizen.ward.rate_fix') }}</a>
            @elseif ($cluster->isDone() && $rated)
                <span class="tag"><x-icon name="check" /> {{ __('citizen.ward.rated') }}</span>
            @elseif (! $cluster->isDone() && $reported)
                <span class="tag"><x-icon name="check" /> {{ __('citizen.ward.counted') }}</span>
            @elseif (! $cluster->isDone())
                <form method="POST" action="{{ route('citizen.voice', $cluster) }}">
                    @csrf
                    <button type="submit" class="btn btn-quiet voice-button" aria-label="{{ __('citizen.ward.add_voice_label', ['title' => $cluster->title]) }}" title="{{ __('citizen.ward.add_voice') }}">
                        <x-icon name="plus" />
                    </button>
                </form>
            @endif
        </div>
    @endif
</li>
