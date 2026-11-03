<x-layout.staff :title="__('leader.auth.two_factor_title')">
    <section class="card stack">
        <h1>{{ __('leader.auth.two_factor_title') }}</h1>
        <p>{{ __('leader.auth.two_factor_intro') }}</p>
        <form method="POST" action="{{ route('two-factor.login') }}" class="stack" novalidate>
            @csrf
            <div class="field">
                <label for="code">{{ __('leader.auth.app_code') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" @class(['input-error' => $errors->has('code')])>
                <x-field-error name="code" />
            </div>
            <button type="submit" class="btn btn-block">{{ __('leader.auth.verify') }}</button>
        </form>
        <details>
            <summary>{{ __('leader.auth.use_recovery') }}</summary>
            <form method="POST" action="{{ route('two-factor.login') }}" class="stack" novalidate>
                @csrf
                <div class="field">
                    <label for="recovery_code">{{ __('leader.auth.recovery_code') }}</label>
                    <input id="recovery_code" name="recovery_code" type="text" autocomplete="off">
                    <x-field-error name="recovery_code" />
                </div>
                <button type="submit" class="btn btn-quiet btn-block">{{ __('leader.auth.verify') }}</button>
            </form>
        </details>
    </section>
</x-layout.staff>
