@php
    $socialNetworks = [
        'instagram' => ['label' => 'Instagram', 'path' => 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.5-3a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Z'],
        'tiktok' => ['label' => 'TikTok', 'path' => 'M16 2h3c.2 2.1 1.3 3.8 3 4.8v3.1a9 9 0 0 1-6-2.1v7.5a6.3 6.3 0 1 1-6.3-6.3c.5 0 1 0 1.5.2v3.3a3.2 3.2 0 1 0 1.8 2.9V2h3Z'],
        'facebook' => ['label' => 'Facebook', 'path' => 'M13.5 22v-8h2.8l.4-3h-3.2V9.1c0-.9.3-1.5 1.6-1.5h1.7V4.9c-.3 0-1.4-.1-2.7-.1-2.7 0-4.5 1.6-4.5 4.6V11H6.8v3h2.8v8h3.9Z'],
        'youtube' => ['label' => 'YouTube', 'path' => 'M23 7.1a3 3 0 0 0-2.1-2.1C19 4.5 12 4.5 12 4.5s-7 0-8.9.5A3 3 0 0 0 1 7.1 31 31 0 0 0 .5 12s0 3.3.5 4.9a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.5-1.6.5-4.9.5-4.9s0-3.3-.5-4.9ZM9.8 15.5v-7l6 3.5-6 3.5Z'],
    ];
@endphp
<div class="rks-social-links">
    @foreach ($socialNetworks as $socialKey => $socialNetwork)
        @php($socialUrl = filter_var((string) config('rotikita-contact.'.$socialKey), FILTER_VALIDATE_URL) ? parse_url((string) config('rotikita-contact.'.$socialKey)) : false)
        @php($allowedHosts = ['instagram' => ['instagram.com', 'www.instagram.com'], 'tiktok' => ['tiktok.com', 'www.tiktok.com'], 'facebook' => ['facebook.com', 'www.facebook.com'], 'youtube' => ['youtube.com', 'www.youtube.com', 'youtu.be']])
        @if (is_array($socialUrl) && ($socialUrl['scheme'] ?? null) === 'https' && in_array($socialUrl['host'] ?? '', $allowedHosts[$socialKey] ?? [], true))
            <a href="{{ config('rotikita-contact.'.$socialKey) }}" target="_blank" rel="noopener noreferrer" aria-label="Ikuti RotiKita di {{ $socialNetwork['label'] }}">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="{{ $socialNetwork['path'] }}"/></svg><span>{{ $socialNetwork['label'] }}</span><span aria-hidden="true">↗</span>
            </a>
        @endif
    @endforeach
</div>
