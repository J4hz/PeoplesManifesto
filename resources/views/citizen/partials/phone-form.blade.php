<form method="POST" action="{{ route('citizen.sign-in') }}" class="stack" novalidate>
    @csrf
    <div class="field">
        <label for="phone">{{ __('citizen.auth.phone_label') }}</label>
        <span class="hint" id="phone-hint">{{ __('citizen.auth.phone_hint') }}</span>
        <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required
               value="{{ old('phone') }}" aria-describedby="phone-hint @error('phone') phone-error @enderror"
               @class(['input-error' => $errors->has('phone')])>
        <x-field-error name="phone" />
    </div>
    <x-turnstile />
    <x-field-error name="cf-turnstile-response" />
    <button type="submit" class="btn btn-block">{{ __('citizen.auth.send_code') }}</button>
    <p class="muted small">{{ __('citizen.auth.phone_privacy') }} <a href="{{ route('privacy') }}">{{ __('civic.footer.privacy') }}</a></p>
</form>
