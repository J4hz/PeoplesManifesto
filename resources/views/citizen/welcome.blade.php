<x-layout.citizen :title="__('citizen.welcome.title')">
    <section class="card stack">
        <h1>{{ __('citizen.welcome.title') }}</h1>
        <p>{{ __('citizen.welcome.intro') }}</p>
        @include('citizen.partials.phone-form')
        @if (config('services.africastalking.short_code'))
            <p class="muted small">{{ __('citizen.welcome.sms_option', ['code' => config('services.africastalking.short_code')]) }}</p>
        @endif
    </section>

    <section>
        <div class="section-title">
            <h2>{{ __('citizen.welcome.wards_heading') }}</h2>
        </div>
        @foreach ($constituencies as $constituency)
            <div class="card card-tight">
                <h3>{{ __('citizen.welcome.constituency', ['constituency' => $constituency->name, 'county' => $constituency->county->name]) }}</h3>
                <ul class="chips" aria-label="{{ __('citizen.welcome.wards_in', ['constituency' => $constituency->name]) }}">
                    @foreach ($constituency->wards as $ward)
                        <li><a class="chip-link" href="{{ route('wards.show', $ward) }}">{{ $ward->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </section>

    <section class="card">
        <h2>{{ __('citizen.welcome.how_heading') }}</h2>
        <ol class="prose">
            <li>{{ __('citizen.welcome.how_1') }}</li>
            <li>{{ __('citizen.welcome.how_2') }}</li>
            <li>{{ __('citizen.welcome.how_3') }}</li>
            <li>{{ __('citizen.welcome.how_4') }}</li>
        </ol>
    </section>
</x-layout.citizen>
