<x-layout.staff :title="$cluster->title" active="overview">
    <p class="small"><a href="{{ route('dashboard') }}"><x-icon name="arrow-left" /> {{ __('leader.respond.back') }}</a></p>

    <section class="card stack">
        <div class="issue-meta">
            <x-status-pill :status="$cluster->status" />
            <x-severity-tag :severity="$cluster->severity" />
            <span>{{ __('civic.category.'.$cluster->category) }}</span>
            <span>{{ $cluster->ward->name }}</span>
        </div>
        <h1>{{ $cluster->title }}</h1>
        @if ($cluster->summary)
            <p>{{ $cluster->summary }}</p>
        @endif
        <div class="issue-meta">
            <span><x-icon name="users" /> {{ trans_choice('civic.residents_count', $cluster->resident_count, ['count' => $cluster->resident_count]) }}</span>
            <span>{{ trans_choice('leader.respond.reports_count', $cluster->submission_count, ['count' => $cluster->submission_count]) }}</span>
            <span><x-icon name="map-pin" /> {{ trans_choice('civic.areas_count', $cluster->areas_count, ['count' => $cluster->areas_count]) }}</span>
            <span>{{ __('civic.trend.'.$cluster->trend) }}</span>
            <span><x-icon name="calendar" /> {{ __('public.cluster.first_reported', ['date' => $cluster->first_reported_at?->translatedFormat('j M Y')]) }}</span>
            @if ($cluster->routedOffice)
                <span>{{ __('public.cluster.routed_to', ['office' => $cluster->routedOffice->name]) }}</span>
            @endif
        </div>
        @if ($cluster->done_round > 0)
            <p class="small">
                {{ __('leader.respond.ratings_line', ['fixed' => $verdicts['fixed'], 'partly' => $verdicts['partly'], 'not_fixed' => $verdicts['not_fixed']]) }}
            </p>
        @endif
    </section>

    <div class="grid-2">
        <section class="card stack">
            <h2>{{ __('leader.respond.samples_heading') }}</h2>
            <p class="small muted">{{ __('leader.respond.samples_note') }}</p>
            @forelse ($samples as $sample)
                <figure class="quote">
                    <blockquote>{{ $sample->translation ?: $sample->body }}</blockquote>
                    @if ($sample->original_language && $sample->original_language !== 'en' && $sample->body !== $sample->translation)
                        <details>
                            <summary class="small">{{ __('leader.respond.original', ['language' => __('leader.languages.'.$sample->original_language)]) }}</summary>
                            <p lang="{{ $sample->original_language === 'sw' ? 'sw' : '' }}">{{ $sample->body }}</p>
                        </details>
                    @endif
                    <figcaption class="small muted">
                        {{ $sample->created_at->translatedFormat('j M Y') }}
                        @if ($sample->area) &middot; {{ $sample->area->name }} @endif
                        &middot; {{ __('civic.channel.'.$sample->channel) }}
                        @if ($sample->photo_path) &middot; <a href="{{ route('dashboard.submissions.photo', $sample) }}">{{ __('leader.respond.photo') }}</a> @endif
                    </figcaption>
                </figure>
            @empty
                <p class="muted">{{ __('leader.respond.no_samples') }}</p>
            @endforelse
            @if ($hiddenSamples > 0)
                <p class="small muted">{{ trans_choice('leader.respond.hidden_samples', $hiddenSamples, ['count' => $hiddenSamples]) }}</p>
            @endif
        </section>

        <section class="card stack">
            <h2>{{ __('leader.respond.form_heading') }}</h2>
            @unless ($canMarkDone)
                <div class="notice">{{ __('leader.respond.staff_note') }}</div>
            @endunless
            <form method="POST" action="{{ route('dashboard.updates.store', $cluster) }}" enctype="multipart/form-data" class="stack" novalidate x-data="statusForm">
                @csrf
                <fieldset>
                    <legend>{{ __('leader.respond.status_legend') }}</legend>
                    <div class="chips">
                        <label class="chip">
                            <input type="radio" name="status" value="" x-on:change="choose" @checked(old('status', '') === '')>
                            <span>{{ __('leader.respond.no_change') }}</span>
                        </label>
                        @foreach (config('civic.leader_statuses') as $status)
                            @if ($status !== 'done' || $canMarkDone)
                                <label class="chip">
                                    <input type="radio" name="status" value="{{ $status }}" x-on:change="choose" @checked(old('status') === $status)>
                                    <span>{{ __('civic.status.'.$status) }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <p class="hint" x-show="isDone">{{ __('leader.respond.done_hint') }}</p>
                    <x-field-error name="status" />
                </fieldset>

                <div class="field">
                    <label for="message">{{ __('leader.respond.message_label') }}</label>
                    <span class="hint" id="message-hint">{{ __('leader.respond.message_hint') }}</span>
                    <textarea id="message" name="message" required maxlength="2000" aria-describedby="message-hint">{{ old('message') }}</textarea>
                    <x-field-error name="message" />
                </div>

                <div class="field">
                    <label for="expected_completion_on">{{ __('leader.respond.expected_label') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></label>
                    <input id="expected_completion_on" name="expected_completion_on" type="date" value="{{ old('expected_completion_on') }}" min="{{ now()->toDateString() }}">
                    <x-field-error name="expected_completion_on" />
                </div>

                <div class="field">
                    <label for="photo"><x-icon name="camera" /> {{ __('leader.respond.photo_label') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></label>
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
                    <x-field-error name="photo" />
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="notify_reporters" value="1" @checked(old('notify_reporters', true))>
                    <span>{{ trans_choice('leader.respond.notify_label', $cluster->resident_count, ['count' => $cluster->resident_count]) }}</span>
                </label>

                <button type="submit" class="btn">{{ $canMarkDone ? __('leader.respond.publish') : __('leader.respond.save_draft') }}</button>
            </form>
        </section>
    </div>

    <section class="card stack">
        <h2>{{ __('leader.respond.timeline_heading') }}</h2>
        @if ($updates->isEmpty())
            <p class="muted">{{ __('public.cluster.no_updates') }}</p>
        @else
            <ol class="timeline">
                @foreach ($updates as $update)
                    <li class="stack">
                        <div class="row row-between">
                            <strong>{{ $update->author->name }}</strong>
                            <span class="when">
                                @if ($update->isPublished())
                                    {{ __('leader.respond.published_on', ['date' => $update->published_at->translatedFormat('j M Y, H:i')]) }}
                                @else
                                    {{ __('leader.respond.draft_on', ['date' => $update->created_at->translatedFormat('j M Y, H:i')]) }}
                                @endif
                            </span>
                        </div>
                        @if ($update->status)<div><x-status-pill :status="$update->status" /></div>@endif
                        <p>{{ $update->message }}</p>
                        @if ($update->expected_completion_on)
                            <p class="small muted">{{ __('public.cluster.expected', ['date' => $update->expected_completion_on->translatedFormat('j M Y')]) }}</p>
                        @endif
                        @if ($update->photo_path)
                            <a href="{{ route('photos.update', $update) }}">{{ __('leader.respond.photo') }}</a>
                        @endif
                        @unless ($update->isPublished())
                            <div class="row">
                                @can('publish', $update)
                                    <form method="POST" action="{{ route('dashboard.updates.publish', $update) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-small">{{ __('leader.respond.publish_draft') }}</button>
                                    </form>
                                @endcan
                                @can('delete', $update)
                                    <form method="POST" action="{{ route('dashboard.updates.destroy', $update) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-quiet btn-small">{{ __('leader.respond.discard_draft') }}</button>
                                    </form>
                                @endcan
                            </div>
                        @endunless
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layout.staff>
