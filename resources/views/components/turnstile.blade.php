@if (config('services.turnstile.site_key'))
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer nonce="{{ Vite::cspNonce() }}"></script>
        @endpush
    @endonce
@endif
