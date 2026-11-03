@props(['severity'])
<span {{ $attributes->merge(['class' => 'tag tag-'.$severity]) }}>
    @if ($severity === 'urgent')<x-icon name="alert" />@endif
    {{ __('civic.severity.'.$severity) }}
</span>
