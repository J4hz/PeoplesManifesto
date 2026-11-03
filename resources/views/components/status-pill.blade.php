@props(['status'])
<span {{ $attributes->merge(['class' => 'pill pill-'.$status]) }}>{{ __('civic.status.'.$status) }}</span>
