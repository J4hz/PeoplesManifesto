<x-layout.staff :title="__('admin.ai.title')" active="ai">
    <div class="page-head">
        <h1>{{ __('admin.ai.title') }}</h1>
        <p>{{ __('admin.ai.intro') }}</p>
    </div>

    <div class="stats">
        <x-stat-tile :value="'$'.number_format($spent, 2)" :label="__('admin.ai.spent')" />
        <x-stat-tile :value="'$'.number_format($cap, 2)" :label="__('admin.ai.cap')" />
    </div>

    @if ($briefsPaused)
        <div class="notice notice-warn">{{ __('admin.ai.paused') }}</div>
    @endif

    <section class="card stack">
        <h2>{{ __('admin.ai.by_ward_week') }}</h2>
        @if ($rows->isEmpty())
            <p class="muted">{{ __('admin.ai.none') }}</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('admin.ai.week') }}</th>
                            <th>{{ __('admin.ai.ward') }}</th>
                            <th class="num">{{ __('admin.ai.calls') }}</th>
                            <th class="num">{{ __('admin.ai.input') }}</th>
                            <th class="num">{{ __('admin.ai.output') }}</th>
                            <th class="num">{{ __('admin.ai.cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($row->week)->translatedFormat('j M Y') }}</td>
                                <td>{{ $row->ward_id ? ($wards[$row->ward_id] ?? '') : __('admin.ai.shared') }}</td>
                                <td class="num">{{ number_format($row->calls) }}</td>
                                <td class="num">{{ number_format($row->input_tokens) }}</td>
                                <td class="num">{{ number_format($row->output_tokens) }}</td>
                                <td class="num">${{ number_format((float) $row->cost, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card stack">
        <h2>{{ __('admin.ai.by_job') }}</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.ai.job') }}</th>
                        <th>{{ __('admin.ai.model') }}</th>
                        <th class="num">{{ __('admin.ai.calls') }}</th>
                        <th class="num">{{ __('admin.ai.cache_read') }}</th>
                        <th class="num">{{ __('admin.ai.cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byJob as $row)
                        <tr>
                            <td>{{ $row->job }}</td>
                            <td>{{ $row->model }}</td>
                            <td class="num">{{ number_format($row->calls) }}</td>
                            <td class="num">{{ number_format($row->cache_read) }}</td>
                            <td class="num">${{ number_format((float) $row->cost, 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layout.staff>
