<x-layout.citizen :title="__('citizen.account.title')" active="account">
    <h1>{{ __('citizen.account.title') }}</h1>

    <section class="card stack">
        <dl>
            <dt class="muted small">{{ __('citizen.account.phone') }}</dt>
            <dd>{{ $maskedPhone }}</dd>
            <dt class="muted small">{{ __('citizen.account.ward') }}</dt>
            <dd>{{ $citizen->ward->name }} &middot; <a href="{{ route('citizen.ward.choose') }}">{{ __('citizen.account.change_ward') }}</a></dd>
        </dl>

        <form method="POST" action="{{ route('citizen.account.update') }}" class="stack">
            @csrf
            @method('PUT')
            <fieldset>
                <legend>{{ __('citizen.account.language') }}</legend>
                <div class="chips">
                    <label class="chip"><input type="radio" name="locale" value="en" @checked($citizen->locale === 'en')><span lang="en">English</span></label>
                    <label class="chip"><input type="radio" name="locale" value="sw" @checked($citizen->locale === 'sw')><span lang="sw">Kiswahili</span></label>
                </div>
            </fieldset>
            <label class="checkbox">
                <input type="hidden" name="sms_notifications" value="0">
                <input type="checkbox" name="sms_notifications" value="1" @checked($citizen->sms_notifications)>
                <span>{{ __('citizen.account.sms_label') }}</span>
            </label>
            <button type="submit" class="btn">{{ __('citizen.account.save') }}</button>
        </form>
    </section>

    <section class="card stack">
        <h2>{{ __('citizen.account.delete_heading') }}</h2>
        <p>{{ __('citizen.account.delete_explainer') }}</p>
        <form method="POST" action="{{ route('citizen.account.destroy') }}" class="stack">
            @csrf
            @method('DELETE')
            <label class="checkbox">
                <input type="checkbox" name="confirm" value="1" required>
                <span>{{ __('citizen.account.delete_confirm') }}</span>
            </label>
            <x-field-error name="confirm" />
            <button type="submit" class="btn btn-quiet">{{ __('citizen.account.delete_submit') }}</button>
        </form>
    </section>
</x-layout.citizen>
