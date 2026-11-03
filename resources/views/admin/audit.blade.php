<x-layout.staff :title="__('admin.nav.audit')" active="audit-all">
    <div class="page-head">
        <h1>{{ __('admin.nav.audit') }}</h1>
        <p>{{ __('admin.audit.intro') }}</p>
    </div>
    @include('partials.audit-table', ['logs' => $logs, 'actors' => $actors])
</x-layout.staff>
