@props(['value', 'label'])
<div {{ $attributes->merge(['class' => 'card card-tight stat']) }}>
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-label">{{ $label }}</div>
</div>
