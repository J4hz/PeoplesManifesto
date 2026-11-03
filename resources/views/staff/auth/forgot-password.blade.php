<x-layout.staff :title="__('leader.auth.forgot_title')">
    <section class="card stack">
        <h1>{{ __('leader.auth.forgot_title') }}</h1>
        <p>{{ __('leader.auth.forgot_intro') }}</p>
        <form method="POST" action="{{ route('password.email') }}" class="stack" novalidate>
            @csrf
            <div class="field">
                <label for="email">{{ __('leader.auth.email') }}</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required>
                <x-field-error name="email" />
            </div>
            <button type="submit" class="btn btn-block">{{ __('leader.auth.send_link') }}</button>
        </form>
        <p class="small"><a href="{{ route('login') }}">{{ __('leader.auth.back_to_login') }}</a></p>
    </section>
</x-layout.staff>
