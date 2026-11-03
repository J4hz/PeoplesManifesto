<x-layout.citizen :title="__('citizen.auth.sign_in_title')">
    <section class="card stack">
        <h1>{{ __('citizen.auth.sign_in_title') }}</h1>
        <p>{{ __('citizen.auth.sign_in_intro') }}</p>
        @include('citizen.partials.phone-form')
    </section>
</x-layout.citizen>
