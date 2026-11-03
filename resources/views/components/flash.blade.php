@if (session('status'))
    <div class="notice" role="status">{{ session('status') }}</div>
@endif
@if (session('warning'))
    <div class="notice notice-warn" role="status">{{ session('warning') }}</div>
@endif
