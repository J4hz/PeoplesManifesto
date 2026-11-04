@include('errors.layout', [
    'title' => __('errors.419_title'),
    'message' => __('errors.419_message'),
    'homeUrl' => request()->is('staff*', 'dashboard*', 'admin*') ? route('login') : url('/'),
    'homeLabel' => request()->is('staff*', 'dashboard*', 'admin*') ? __('errors.staff_sign_in') : __('errors.home'),
])
