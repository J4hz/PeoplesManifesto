<section class="card">
    @if ($logs->isEmpty())
        <p class="muted">{{ __('leader.audit.empty') }}</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('leader.audit.when') }}</th>
                        <th>{{ __('leader.audit.who') }}</th>
                        <th>{{ __('leader.audit.what') }}</th>
                        <th>{{ __('leader.audit.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->translatedFormat('j M Y, H:i') }}</td>
                            <td>{{ $log->actor_id ? ($actors[$log->actor_id] ?? __('leader.audit.removed_user')) : __('leader.audit.system') }}</td>
                            <td>{{ __('leader.audit.actions.'.str_replace('.', '_', $log->action)) }}</td>
                            <td class="small">
                                @if ($log->subject_type === 'cluster_update' && isset($log->after['cluster_id']))
                                    <a href="{{ route('dashboard.clusters.show', $log->after['cluster_id']) }}">{{ __('leader.audit.view_issue') }}</a>
                                @elseif ($log->subject_type === 'cluster_update' && isset($log->before['cluster_id']))
                                    <a href="{{ route('dashboard.clusters.show', $log->before['cluster_id']) }}">{{ __('leader.audit.view_issue') }}</a>
                                @endif
                                @if (isset($log->before['status']) || isset($log->after['status']))
                                    {{ __('leader.audit.status_change', [
                                        'from' => isset($log->before['status']) ? __('civic.status.'.$log->before['status']) : '-',
                                        'to' => isset($log->after['status']) ? __('civic.status.'.$log->after['status']) : '-',
                                    ]) }}
                                @endif
                                @if ($log->ip_address)
                                    <span class="muted">{{ $log->ip_address }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $logs->links('partials.pagination') }}
    @endif
</section>
