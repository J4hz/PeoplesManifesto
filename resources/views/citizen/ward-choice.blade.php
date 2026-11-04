<x-layout.citizen :title="__('citizen.ward.choose_title')">
    <section class="card stack">
        <h1>{{ __('citizen.ward.choose_title') }}</h1>
        <p>{{ __('citizen.ward.choose_intro') }}</p>
        @if ($constituencies->isEmpty())
            <div class="notice notice-warn" role="status">{{ __('citizen.ward.none_open') }}</div>
        @else
        <form method="POST" action="{{ route('citizen.ward.choose') }}" class="stack">
            @csrf
            @foreach ($constituencies as $constituency)
                <fieldset>
                    <legend>{{ __('citizen.welcome.constituency', ['constituency' => $constituency->name, 'county' => $constituency->county->name]) }}</legend>
                    <div class="chips">
                        @foreach ($constituency->wards as $ward)
                            <label class="chip">
                                <input type="radio" name="ward_id" value="{{ $ward->id }}" @checked((int) old('ward_id', $current) === $ward->id) required>
                                <span>{{ $ward->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <x-field-error name="ward_id" />
            <p class="muted small">{{ __('citizen.ward.choose_hint') }}</p>
            <button type="submit" class="btn btn-block">{{ __('citizen.ward.choose_submit') }}</button>
        </form>
        @endif
    </section>
</x-layout.citizen>
