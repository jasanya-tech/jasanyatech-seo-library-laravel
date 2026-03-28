<?php

use JasanyaTech\SEO\Facades\SEO;

beforeEach(function (): void {
    config()->set('app.url', 'https://example.com');
    config()->set('seo.site.url', 'https://example.com');
    config()->set('seo.sitemap.cache', false);
    config()->set('seo.sitemap.chunk_size', 1);
    config()->set('seo.robots.disallow_non_production', true);

    SEO::sitemap()->clear();
});

it('renders a sitemap index and chunked child sitemaps', function (): void {
    SEO::sitemap()->register('posts', fn (): array => [
        ['url' => 'https://example.com/blog/first-post', 'lastmod' => '2026-03-01 08:00:00'],
        ['url' => 'https://example.com/blog/second-post', 'lastmod' => '2026-03-02 08:00:00'],
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $response->assertSee('<sitemapindex', false);
    $response->assertSee('<loc>https://example.com/sitemaps/posts-1.xml</loc>', false);
    $response->assertSee('<loc>https://example.com/sitemaps/posts-2.xml</loc>', false);

    $childResponse = $this->get('/sitemaps/posts-1.xml');

    $childResponse->assertOk();
    $childResponse->assertSee('<urlset', false);
    $childResponse->assertSee('<loc>https://example.com/blog/first-post</loc>', false);
});

it('renders robots txt with sitemap url automatically', function (): void {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSeeText('User-agent: *');
    $response->assertSeeText('Disallow: /');
    $response->assertSeeText('Sitemap: https://example.com/sitemap.xml');
});
