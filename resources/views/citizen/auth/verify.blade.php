<x-layout.citizen :title="__('citizen.auth.code_title')">
    <section class="card stack">
        <h1>{{ __('citizen.auth.code_title') }}</h1>
        <p>{{ __('citizen.auth.code_intro', ['phone' => $maskedPhone, 'minutes' => config('civic.otp.ttl_minutes')]) }}</p>
        <form method="POST" action="{{ route('citizen.sign-in.verify') }}" class="stack" novalidate>
            @csrf
            <div class="field">
                <label for="code">{{ __('citizen.auth.code_label') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                       maxlength="{{ config('civic.otp.length') }}" required
                       @class(['input-error' => $errors->has('code')])
                       aria-describedby="@error('code') code-error @enderror">
                <x-field-error name="code" />
            </div>
            <button type="submit" class="btn btn-block">{{ __('citizen.auth.verify') }}</button>
        </form>
        <p class="small"><a href="{{ route('citizen.sign-in') }}">{{ __('citizen.auth.wrong_number') }}</a></p>
    </section>
</x-layout.citizen>
