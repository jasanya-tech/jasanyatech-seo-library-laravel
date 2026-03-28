<?php

namespace JasanyaTech\SEO\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use JasanyaTech\SEO\Sitemap\SitemapBuilder;

class SitemapController extends Controller
{
    public function index(SitemapBuilder $builder): Response
    {
        $payload = $this->remember('index', fn (): array => $builder->buildIndex());

        if ($payload['type'] === 'index') {
            return response()
                ->view('seo::sitemap.index', ['sitemaps' => $payload['sitemaps']], 200, [
                    'Content-Type' => 'application/xml; charset=UTF-8',
                ]);
        }

        return response()
            ->view('seo::sitemap.urlset', ['urls' => $payload['urls']], 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
            ]);
    }

    public function show(string $source, int $page, SitemapBuilder $builder): Response
    {
        $payload = $this->remember(
            sprintf('source:%s:%d', $source, $page),
            fn (): ?array => $builder->buildSource($source, $page),
        );

        abort_if($payload === null, 404);

        return response()
            ->view('seo::sitemap.urlset', ['urls' => $payload], 200, [
                'Content-Type' => 'application/xml; charset=UTF-8',
            ]);
    }

    private function remember(string $suffix, callable $resolver): mixed
    {
        if (! (bool) config('seo.sitemap.cache', true)) {
            return value($resolver);
        }

        return Cache::remember(
            'perfect-seo:sitemap:'.$suffix,
            (int) config('seo.sitemap.cache_ttl', 3600),
            fn (): mixed => value($resolver),
        );
    }
}
