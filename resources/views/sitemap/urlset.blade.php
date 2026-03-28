{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['url'] }}</loc>
        @if ($url['lastmod'])
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        @if ($url['changefreq'])
            <changefreq>{{ $url['changefreq'] }}</changefreq>
        @endif
        @if ($url['priority'] !== null)
            <priority>{{ number_format($url['priority'], 1, '.', '') }}</priority>
        @endif
    </url>
@endforeach
</urlset>
