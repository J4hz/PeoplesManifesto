<x-layout.citizen :title="__('citizen.rate.title')" active="ward">
    <section class="card stack">
        <div>
            <h1>{{ __('citizen.rate.title') }}</h1>
            <h2>{{ $cluster->title }}</h2>
            @if ($cluster->doneBy)
                <p class="muted">{{ __('citizen.rate.marked_done_by', ['leader' => $cluster->doneBy->name, 'office' => $cluster->doneBy->office->name, 'date' => $cluster->done_at->translatedFormat('j F Y')]) }}</p>
            @endif
            @if ($lastUpdate)
                <blockquote class="quote">{{ $lastUpdate->message }}</blockquote>
            @endif
        </div>

        <p x-data="fixedCount" data-url="{{ route('clusters.fixed-count', $cluster) }}" data-fixed="{{ $fixedCount }}" data-total="{{ $totalCount }}" aria-live="polite">
            {{ __('citizen.rate.live_count_before') }}
            <strong x-ref="fixed">{{ $fixedCount }}</strong>
            {{ __('citizen.rate.live_count_middle') }}
            <strong x-ref="total">{{ $totalCount }}</strong>
            {{ __('citizen.rate.live_count_after') }}
        </p>

        <form method="POST" action="{{ route('citizen.rate.store', $cluster) }}" class="stack" novalidate>
            @csrf
            <fieldset>
                <legend>{{ __('citizen.rate.verdict_legend') }}</legend>
                <div class="choice-row">
                    @foreach (\App\Models\Rating::VERDICTS as $verdict)
                        <label class="chip">
                            <input type="radio" name="verdict" value="{{ $verdict }}" required @checked(old('verdict') === $verdict)>
                            <span>{{ __('civic.verdict.'.$verdict) }}</span>
                        </label>
                    @endforeach
                </div>
                <x-field-error name="verdict" />
            </fieldset>

            <fieldset>
                <legend>{{ __('citizen.rate.stars_legend') }}</legend>
                <div class="stars">
                    @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" id="stars-{{ $i }}" name="stars" value="{{ $i }}" required @checked((int) old('stars') === $i)>
                        <label for="stars-{{ $i }}" title="{{ trans_choice('civic.stars_count', $i, ['count' => $i]) }}">
                            <x-icon name="star" />
                            <span class="visually-hidden">{{ trans_choice('civic.stars_count', $i, ['count' => $i]) }}</span>
                        </label>
                    @endfor
                </div>
                <x-field-error name="stars" />
            </fieldset>

            <fieldset>
                <legend>{{ __('citizen.rate.praise_legend') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></legend>
                <div class="chips">
                    @foreach (config('civic.rating.praise_tags') as $tag)
                        <label class="chip">
                            <input type="checkbox" name="praise_tags[]" value="{{ $tag }}" @checked(in_array($tag, old('praise_tags', []), true))>
                            <span>{{ __('civic.praise.'.$tag) }}</span>
                        </label>
                    @endforeach
                </div>
                <x-field-error name="praise_tags" />
            </fieldset>

            <div class="field">
                <label for="thanks">{{ __('citizen.rate.thanks_label') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></label>
                <span class="hint" id="thanks-hint">{{ __('citizen.rate.thanks_hint') }}</span>
                <textarea id="thanks" name="thanks" maxlength="500" aria-describedby="thanks-hint">{{ old('thanks') }}</textarea>
                <x-field-error name="thanks" />
            </div>

            <button type="submit" class="btn btn-block">{{ __('citizen.rate.submit') }}</button>
        </form>
    </section>
</x-layout.citizen>
