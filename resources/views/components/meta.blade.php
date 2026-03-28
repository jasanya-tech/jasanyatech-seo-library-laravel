@php($renderedSeo = app(\JasanyaTech\SEO\Renderers\MetaRenderer::class)->render($seo ?? null))

@if ($renderedSeo['title'])
    <title>{{ $renderedSeo['title'] }}</title>
@endif

@foreach ($renderedSeo['metas'] as $meta)
    <meta {{ $meta['attribute'] }}="{{ $meta['key'] }}" content="{{ $meta['content'] }}">
@endforeach

@foreach ($renderedSeo['links'] as $link)
    <link
        rel="{{ $link['rel'] }}"
        href="{{ $link['href'] }}"
        @if (isset($link['hreflang']))
            hreflang="{{ $link['hreflang'] }}"
        @endif
    >
@endforeach

@foreach ($renderedSeo['json_ld'] as $schema)
    <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)</script>
@endforeach
