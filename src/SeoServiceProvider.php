<?php

namespace JasanyaTech\SEO;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use JasanyaTech\SEO\Renderers\MetaRenderer;
use JasanyaTech\SEO\Robots\RobotsRenderer;
use JasanyaTech\SEO\Sitemap\SitemapBuilder;
use JasanyaTech\SEO\Sitemap\SitemapManager;
use JasanyaTech\SEO\Support\TextSanitizer;
use JasanyaTech\SEO\Support\UrlNormalizer;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo.php', 'seo');

        $this->app->singleton(UrlNormalizer::class, fn (): UrlNormalizer => new UrlNormalizer);
        $this->app->singleton(TextSanitizer::class, fn (): TextSanitizer => new TextSanitizer);

        $this->app->singleton(SitemapManager::class, function (Application $app): SitemapManager {
            return new SitemapManager(
                config('seo.sitemap', []),
                config('seo.site', []),
                $app->make(UrlNormalizer::class),
            );
        });

        $this->app->scoped(SeoManager::class, function (Application $app): SeoManager {
            return new SeoManager(
                config('seo', []),
                $app->make(SitemapManager::class),
                $app->make(UrlNormalizer::class),
                $app->make(TextSanitizer::class),
            );
        });

        $this->app->bind(MetaRenderer::class, function (Application $app): MetaRenderer {
            return new MetaRenderer(
                config('seo', []),
                $app->make(SeoManager::class),
                $app->make(UrlNormalizer::class),
                $app->make(TextSanitizer::class),
            );
        });

        $this->app->bind(RobotsRenderer::class, fn (): RobotsRenderer => new RobotsRenderer(config('seo', [])));
        $this->app->bind(SitemapBuilder::class, function (Application $app): SitemapBuilder {
            return new SitemapBuilder(
                config('seo', []),
                $app->make(SitemapManager::class),
            );
        });

        $this->app->bind('perfect-seo', fn (Application $app): SeoManager => $app->make(SeoManager::class));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'seo');

        if ((bool) config('seo.routes.sitemap', true) || (bool) config('seo.routes.robots', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/seo.php');
        }

        Blade::componentNamespace('JasanyaTech\\SEO\\Components', 'seo');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/seo.php' => config_path('seo.php'),
            ], 'seo-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/seo'),
            ], 'seo-views');
        }
    }
}
