<x-layout.staff :title="__('admin.intake.title')" active="intake">
    <div class="page-head">
        <h1>{{ __('admin.intake.title') }}</h1>
        <p>{{ __('admin.intake.intro') }}</p>
    </div>

    <section class="card">
        @if ($submissions->isEmpty())
            <p class="muted">{{ __('admin.intake.empty') }}</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="num">{{ __('admin.intake.id') }}</th>
                            <th>{{ __('admin.intake.ward') }}</th>
                            <th>{{ __('admin.intake.received') }}</th>
                            <th class="num">{{ __('admin.intake.attempts') }}</th>
                            <th>{{ __('admin.intake.error') }}</th>
                            <th><span class="visually-hidden">{{ __('admin.intake.action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td class="num">{{ $submission->id }}</td>
                                <td>{{ $submission->ward->name }}</td>
                                <td>{{ $submission->created_at->translatedFormat('j M Y, H:i') }}</td>
                                <td class="num">{{ $submission->processing_attempts }}</td>
                                <td class="small">{{ $submission->processing_error ?? __('admin.intake.waiting') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.intake.retry', $submission) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-quiet btn-small">{{ __('admin.intake.retry') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $submissions->links('partials.pagination') }}
        @endif
    </section>
</x-layout.staff>
