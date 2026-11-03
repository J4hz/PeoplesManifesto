<x-layout.staff :title="__('leader.auth.reset_title')">
    <section class="card stack">
        <h1>{{ __('leader.auth.reset_title') }}</h1>
        <form method="POST" action="{{ route('password.update') }}" class="stack" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div class="field">
                <label for="email">{{ __('leader.auth.email') }}</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email', $request->email) }}" required>
                <x-field-error name="email" />
            </div>
            <div class="field">
                <label for="password">{{ __('leader.auth.new_password') }}</label>
                <span class="hint">{{ __('leader.auth.password_hint') }}</span>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <x-field-error name="password" />
            </div>
            <div class="field">
                <label for="password_confirmation">{{ __('leader.auth.confirm_new_password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-block">{{ __('leader.auth.reset_submit') }}</button>
        </form>
    </section>
</x-layout.staff>
