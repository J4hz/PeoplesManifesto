@props(['value'])
@php $rounded = (int) round((float) $value); @endphp
<span {{ $attributes->merge(['class' => 'star-display']) }} role="img" aria-label="{{ __('civic.stars_out_of_five', ['stars' => number_format((float) $value, 1)]) }}">
    @for ($i = 1; $i <= 5; $i++)
        <x-icon name="star" :class="'icon '.($i <= $rounded ? 'on' : 'off')" />
    @endfor
</span>
