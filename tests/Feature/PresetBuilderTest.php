<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use JasanyaTech\SEO\Facades\SEO;

beforeEach(function (): void {
    config()->set('app.url', 'https://example.com');
    config()->set('seo.site.name', 'Perfect SEO');
    config()->set('seo.site.url', 'https://example.com');
    config()->set('seo.site.default_description', 'Default website description');

    SEO::reset();
});

it('builds seo for a blog post preset', function (): void {
    Route::get('/seo-test/blog-post', function (): string {
        $post = (object) [
            'title' => 'Laravel SEO guide',
            'excerpt' => 'Practical metadata setup for a Laravel blog.',
            'cover_url' => 'https://example.com/images/blog-post.jpg',
            'published_at' => '2026-03-15 09:00:00',
            'updated_at' => '2026-03-16 09:00:00',
            'author_name' => 'Sahrul',
            'url' => 'https://example.com/blog/laravel-seo-guide',
        ];

        SEO::forBlogPost($post);

        return Blade::render('<head><x-seo::meta /></head>');
    });

    $response = $this->get('/seo-test/blog-post');

    $response->assertOk();
    $response->assertSee('<title>Laravel SEO guide | Perfect SEO</title>', false);
    $response->assertSee('property="og:type" content="article"', false);
    $response->assertSee('"@type": "Article"', false);
    $response->assertSee('"@type": "Organization"', false);
    $response->assertSee('"@type": "BreadcrumbList"', false);
});

it('builds seo for a product preset with real offer data only', function (): void {
    Route::get('/seo-test/product', function (): string {
        $product = [
            'name' => 'Smart Room Display',
            'summary' => 'Display panel for meeting room booking.',
            'image' => 'https://example.com/images/product.jpg',
            'sku' => 'SRD-01',
            'brand' => 'Sarpraspenji',
            'price' => '1499000',
            'price_currency' => 'IDR',
            'availability' => 'https://schema.org/InStock',
            'url' => 'https://example.com/products/smart-room-display',
        ];

        SEO::forProduct($product);

        return Blade::render('<head><x-seo::meta /></head>');
    });

    $response = $this->get('/seo-test/product');

    $response->assertOk();
    $response->assertSee('<title>Smart Room Display | Perfect SEO</title>', false);
    $response->assertSee('"@type": "Product"', false);
    $response->assertSee('"@type": "Offer"', false);
    $response->assertSee('"priceCurrency": "IDR"', false);
});

it('builds seo for a service preset', function (): void {
    Route::get('/seo-test/service', function (): string {
        $service = [
            'name' => 'Meeting Room Management',
            'summary' => 'Managed service for room scheduling and booking workflow.',
            'provider' => 'Sarpraspenji',
            'area_served' => 'Bandung',
            'url' => 'https://example.com/services/meeting-room-management',
        ];

        SEO::forService($service);

        return Blade::render('<head><x-seo::meta /></head>');
    });

    $response = $this->get('/seo-test/service');

    $response->assertOk();
    $response->assertSee('<title>Meeting Room Management | Perfect SEO</title>', false);
    $response->assertSee('"@type": "Service"', false);
    $response->assertSee('"areaServed": "Bandung"', false);
});
