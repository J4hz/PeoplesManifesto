<x-layout.staff :title="__('leader.audit.title')" active="audit">
    <div class="page-head">
        <h1>{{ __('leader.audit.title') }}</h1>
        <p>{{ __('leader.audit.intro') }}</p>
    </div>
    @include('partials.audit-table', ['logs' => $logs, 'actors' => $actors])
</x-layout.staff>
