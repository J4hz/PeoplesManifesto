<x-layout.staff :title="__('leader.security.title')" active="security">
    <h1>{{ __('leader.security.title') }}</h1>

    @if (! $user->hasConfirmedTwoFactor())
        <div class="notice notice-warn">{{ __('leader.security.required') }}</div>
    @endif

    <section class="card stack">
        <h2>{{ __('leader.security.app_heading') }}</h2>

        @if ($user->hasConfirmedTwoFactor())
            <p>{{ __('leader.security.enabled', ['date' => $user->two_factor_confirmed_at->translatedFormat('j M Y')]) }}</p>

            @if ($recoveryCodes)
                <div class="notice">{{ __('leader.security.save_codes') }}</div>
                <ol class="codes">
                    @foreach ($recoveryCodes as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ol>
            @endif

            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                @csrf
                <button type="submit" class="btn btn-quiet">{{ __('leader.security.new_codes') }}</button>
            </form>
        @elseif ($qr)
            <p>{{ __('leader.security.scan') }}</p>
            <div class="qr"><img src="{{ $qr }}" alt="{{ __('leader.security.qr_alt') }}" width="192" height="192"></div>
            <p class="small">{{ __('leader.security.setup_key') }} <code>{{ $setupKey }}</code></p>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="stack" novalidate>
                @csrf
                <div class="field">
                    <label for="code">{{ __('leader.auth.app_code') }}</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required>
                    @error('code', 'confirmTwoFactorAuthentication')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn">{{ __('leader.security.confirm') }}</button>
            </form>
        @else
            <p>{{ __('leader.security.explain') }}</p>
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <button type="submit" class="btn">{{ __('leader.security.start') }}</button>
            </form>
        @endif
    </section>

    @if ($user->hasConfirmedTwoFactor())
        <section class="card stack">
            <h2>{{ __('leader.security.password_heading') }}</h2>
            <form method="POST" action="{{ route('user-password.update') }}" class="stack" novalidate>
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="current_password">{{ __('leader.security.current_password') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password', 'updatePassword')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="new_password">{{ __('leader.auth.new_password') }}</label>
                    <span class="hint">{{ __('leader.auth.password_hint') }}</span>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" required>
                    @error('password', 'updatePassword')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="new_password_confirmation">{{ __('leader.auth.confirm_new_password') }}</label>
                    <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn">{{ __('leader.security.update_password') }}</button>
            </form>
        </section>
    @endif
</x-layout.staff>
