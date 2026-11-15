@props(['label', 'value', 'meta' => null, 'icon' => 'R', 'tone' => 'terra'])
<article class="rk-rebuild-kpi rk-rebuild-kpi-{{ $tone }}">
    <span class="rk-rebuild-kpi-icon" aria-hidden="true">{{ $icon }}</span>
    <div class="rk-rebuild-kpi-copy"><span>{{ $label }}</span><strong>{{ $value }}</strong>@if($meta)<small>{{ $meta }}</small>@endif</div>
</article>
