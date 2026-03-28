<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use JasanyaTech\SEO\DTOs\SeoData;
use JasanyaTech\SEO\Facades\SEO;

beforeEach(function (): void {
    config()->set('app.url', 'https://example.com');
    config()->set('seo.site.name', 'Perfect SEO');
    config()->set('seo.site.url', 'https://example.com');
    config()->set('seo.site.default_description', 'Default website description');
    config()->set('seo.defaults.image', 'https://example.com/images/default.jpg');
    config()->set('seo.defaults.image_alt', 'Default image alt');
    config()->set('seo.social.twitter_site', '@perfectseo');

    SEO::reset();
});

it('renders title meta canonical og and twitter tags', function (): void {
    Route::get('/seo-test/meta', function (): string {
        SEO::title('Homepage')
            ->description('<p>Welcome to <strong>our</strong> website.</p>')
            ->canonical('https://example.com/seo-test/meta?utm_source=newsletter&page=2')
            ->robots('index,follow')
            ->image('https://example.com/images/homepage.jpg', 'Homepage hero image');

        return Blade::render('<head><x-seo::meta /></head>');
    });

    $response = $this->get('/seo-test/meta');

    $response->assertOk();
    $response->assertSee('<title>Homepage | Perfect SEO</title>', false);
    $response->assertSee('name="description" content="Welcome to our website."', false);
    $response->assertSee('name="robots" content="index,follow"', false);
    $response->assertSee('rel="canonical"', false);
    $response->assertSee('href="https://example.com/seo-test/meta?page=2"', false);
    $response->assertSee('property="og:title" content="Homepage | Perfect SEO"', false);
    $response->assertSee('property="og:image" content="https://example.com/images/homepage.jpg"', false);
    $response->assertSee('name="twitter:card" content="summary_large_image"', false);
    $response->assertSee('name="twitter:site" content="@perfectseo"', false);
});

it('renders json ld schemas from manager state', function (): void {
    Route::get('/seo-test/schema', function (): string {
        SEO::title('Article page')
            ->canonical('https://example.com/blog/seo-article')
            ->website()
            ->organization()
            ->breadcrumbs([
                ['name' => 'Home', 'url' => 'https://example.com/'],
                ['name' => 'Article page', 'url' => 'https://example.com/blog/seo-article'],
            ])
            ->article([
                'headline' => 'Article page',
                'description' => 'Structured data article example.',
                'image' => 'https://example.com/images/article.jpg',
                'author' => 'Sahrul',
                'datePublished' => '2026-03-20 10:00:00',
                'dateModified' => '2026-03-21 09:00:00',
                'mainEntityOfPage' => 'https://example.com/blog/seo-article',
            ]);

        return Blade::render('<head><x-seo::meta /></head>');
    });

    $response = $this->get('/seo-test/schema');

    $response->assertOk();
    $response->assertSee('"@type": "WebSite"', false);
    $response->assertSee('"@type": "Organization"', false);
    $response->assertSee('"@type": "BreadcrumbList"', false);
    $response->assertSee('"@type": "Article"', false);
});

it('supports passing a dto directly into the blade component', function (): void {
    Route::get('/seo-test/dto', function (): string {
        $seo = new SeoData(
            title: 'DTO Page',
            description: 'SEO data passed directly from a DTO.',
            canonical: 'https://example.com/dto-page',
            robots: 'noindex,nofollow',
            image: 'https://example.com/images/dto.jpg',
        );

        return Blade::render('<head><x-seo::meta :seo="$seo" /></head>', ['seo' => $seo]);
    });

    $response = $this->get('/seo-test/dto');

    $response->assertOk();
    $response->assertSee('<title>DTO Page | Perfect SEO</title>', false);
    $response->assertSee('name="robots" content="noindex,nofollow"', false);
    $response->assertSee('property="og:url" content="https://example.com/dto-page"', false);
});
