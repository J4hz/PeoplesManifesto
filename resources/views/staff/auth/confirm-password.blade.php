<x-layout.staff :title="__('leader.auth.confirm_title')">
    <section class="card stack">
        <h1>{{ __('leader.auth.confirm_title') }}</h1>
        <p>{{ __('leader.auth.confirm_intro') }}</p>
        <form method="POST" action="{{ route('password.confirm.store') }}" class="stack" novalidate>
            @csrf
            <div class="field">
                <label for="password">{{ __('leader.auth.password') }}</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <x-field-error name="password" />
            </div>
            <button type="submit" class="btn btn-block">{{ __('leader.auth.confirm_submit') }}</button>
        </form>
    </section>
</x-layout.staff>
