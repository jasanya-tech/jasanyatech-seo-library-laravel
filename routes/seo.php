<?php

use Illuminate\Support\Facades\Route;
use JasanyaTech\SEO\Http\Controllers\RobotsController;
use JasanyaTech\SEO\Http\Controllers\SitemapController;

$sitemapPath = trim((string) config('seo.routes.sitemap_path', 'sitemap.xml'), '/');
$robotsPath = trim((string) config('seo.routes.robots_path', 'robots.txt'), '/');
$sitemapsPrefix = trim((string) config('seo.routes.sitemaps_prefix', 'sitemaps'), '/');

if ((bool) config('seo.routes.sitemap', true)) {
    Route::get($sitemapPath, [SitemapController::class, 'index'])->name('seo.sitemap.index');
    Route::get($sitemapsPrefix.'/{source}-{page}.xml', [SitemapController::class, 'show'])
        ->whereNumber('page')
        ->name('seo.sitemap.chunk');
    Route::get($sitemapsPrefix.'/{source}.xml', [SitemapController::class, 'show'])->name('seo.sitemap.source');
}

if ((bool) config('seo.routes.robots', true)) {
    Route::get($robotsPath, RobotsController::class)->name('seo.robots');
}
