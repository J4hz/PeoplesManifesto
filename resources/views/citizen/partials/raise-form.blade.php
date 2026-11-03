<form method="POST" action="{{ route('citizen.submissions.store') }}" enctype="multipart/form-data" class="stack" novalidate>
    @csrf
    <div class="field" x-data="charCount">
        <label for="body">{{ __('citizen.raise.body_label') }}</label>
        <span class="hint" id="body-hint">{{ __('citizen.raise.body_hint') }}</span>
        <textarea id="body" name="body" required maxlength="2000" x-on:input="update"
                  aria-describedby="body-hint @error('body') body-error @enderror"
                  @class(['input-error' => $errors->has('body')])>{{ old('body') }}</textarea>
        <x-field-error name="body" />
    </div>

    <fieldset>
        <legend>{{ __('citizen.raise.category_legend') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></legend>
        <div class="chips">
            @foreach (config('civic.categories') as $category)
                <label class="chip">
                    <input type="radio" name="category" value="{{ $category }}" @checked(old('category') === $category)>
                    <span>{{ __('civic.category.'.$category) }}</span>
                </label>
            @endforeach
        </div>
        <x-field-error name="category" />
    </fieldset>

    <fieldset>
        <legend>{{ __('citizen.raise.severity_legend') }}</legend>
        <div class="choice-row">
            @foreach (array_keys(config('civic.severities')) as $severity)
                <label class="chip">
                    <input type="radio" name="severity" value="{{ $severity }}" @checked(old('severity') === $severity)>
                    <span>{{ __('civic.severity.'.$severity) }}</span>
                </label>
            @endforeach
        </div>
        <p class="hint">{{ __('citizen.raise.severity_hint') }}</p>
        <x-field-error name="severity" />
    </fieldset>

    <div class="field" x-data="photoPicker">
        <label for="photo"><x-icon name="camera" /> {{ __('citizen.raise.photo_label') }} <span class="hint">{{ __('citizen.raise.optional') }}</span></label>
        <span class="hint" id="photo-hint">{{ __('citizen.raise.photo_hint') }}</span>
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" x-on:change="pick" aria-describedby="photo-hint">
        <x-field-error name="photo" />
    </div>

    <div class="privacy-note">
        <x-icon name="shield" />
        <span>{{ __('citizen.raise.privacy_note') }} <a href="{{ route('privacy') }}">{{ __('civic.footer.privacy') }}</a></span>
    </div>

    <x-turnstile />
    <x-field-error name="cf-turnstile-response" />

    <button type="submit" class="btn btn-block">{{ __('citizen.raise.submit') }}</button>
</form>
