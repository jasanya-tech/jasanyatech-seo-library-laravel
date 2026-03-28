<?php

namespace JasanyaTech\SEO;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JasanyaTech\SEO\Contracts\SchemaContract;
use JasanyaTech\SEO\DTOs\SeoData;
use JasanyaTech\SEO\Schema\ArticleSchema;
use JasanyaTech\SEO\Schema\BreadcrumbListSchema;
use JasanyaTech\SEO\Schema\OrganizationSchema;
use JasanyaTech\SEO\Schema\ProductSchema;
use JasanyaTech\SEO\Schema\ServiceSchema;
use JasanyaTech\SEO\Schema\WebSiteSchema;
use JasanyaTech\SEO\Sitemap\SitemapManager;
use JasanyaTech\SEO\Support\DataResolver;
use JasanyaTech\SEO\Support\RobotsDirectives;
use JasanyaTech\SEO\Support\TextSanitizer;
use JasanyaTech\SEO\Support\UrlNormalizer;

class SeoManager
{
    private SeoData $data;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private array $config,
        private SitemapManager $sitemapManager,
        private UrlNormalizer $urlNormalizer,
        private TextSanitizer $textSanitizer,
    ) {
        $this->reset();
    }

    public function reset(): self
    {
        $this->data = SeoData::fromConfig($this->config);

        return $this;
    }

    public function set(SeoData $seo): self
    {
        $this->data = clone $seo;

        return $this;
    }

    public function get(): SeoData
    {
        return clone $this->data;
    }

    public function title(?string $title): self
    {
        $this->data->title = $this->textSanitizer->safeString($title);

        return $this;
    }

    public function description(?string $description): self
    {
        $this->data->description = $this->textSanitizer->plainText(
            $description,
            (int) data_get($this->config, 'defaults.description_limit', 160),
        );

        return $this;
    }

    public function canonical(?string $url): self
    {
        $this->data->canonical = $this->urlNormalizer->normalize($url, $this->ignoredQueryParameters());

        return $this;
    }

    public function robots(string|array|null $robots): self
    {
        $this->data->robots = RobotsDirectives::normalize(
            $robots,
            (string) data_get($this->config, 'defaults.robots', 'index,follow'),
            $this->shouldForceNoindex(),
        );

        return $this;
    }

    public function image(?string $url, ?string $alt = null): self
    {
        $this->data->image = $this->urlNormalizer->normalize($url);
        $this->data->imageAlt = $this->textSanitizer->safeString($alt);

        return $this;
    }

    public function locale(?string $locale): self
    {
        $this->data->locale = $this->textSanitizer->safeString($locale);

        return $this;
    }

    /**
     * @param  array<int|string, mixed>  $hreflang
     */
    public function hreflang(array $hreflang): self
    {
        $normalized = [];

        foreach ($hreflang as $locale => $value) {
            if (is_array($value)) {
                $resolvedLocale = $value['locale'] ?? $locale;
                $url = $this->urlNormalizer->normalize($value['url'] ?? null);
            } else {
                $resolvedLocale = $locale;
                $url = $this->urlNormalizer->normalize((string) $value);
            }

            $resolvedLocale = $this->textSanitizer->safeString(is_string($resolvedLocale) ? $resolvedLocale : null);

            if ($resolvedLocale && $url) {
                $normalized[] = [
                    'name' => $resolvedLocale,
                    'url' => $url,
                ];
            }
        }

        $this->data->hreflang = $normalized;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function website(array $overrides = []): self
    {
        $schema = new WebSiteSchema($this->config, $overrides);

        return $this->pushSchema($schema);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function organization(array $overrides = []): self
    {
        $schema = new OrganizationSchema($this->config, $overrides);

        return $this->pushSchema($schema);
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public function breadcrumbs(array $items): self
    {
        $normalized = [];

        foreach ($items as $item) {
            $name = $this->textSanitizer->safeString($item['name'] ?? null);
            $url = $this->urlNormalizer->normalize($item['url'] ?? null);

            if ($name && $url) {
                $normalized[] = [
                    'name' => $name,
                    'url' => $url,
                ];
            }
        }

        $this->data->breadcrumbs = $normalized;

        $schema = new BreadcrumbListSchema($normalized);

        return $this->pushSchema($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function article(array $data): self
    {
        $this->data->ogType = 'article';

        if (! filled($this->data->title ?? null) && filled($data['headline'] ?? null)) {
            $this->title((string) $data['headline']);
        }

        if (! filled($this->data->description ?? null) && filled($data['description'] ?? null)) {
            $this->description((string) $data['description']);
        }

        if (! filled($this->data->image ?? null) && filled($data['image'] ?? null)) {
            $image = is_array($data['image']) ? Arr::first($data['image']) : $data['image'];
            $this->image(is_string($image) ? $image : null);
        }

        $schema = new ArticleSchema($data, $this->config);

        return $this->pushSchema($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function product(array $data): self
    {
        $this->data->ogType = 'product';

        $schema = new ProductSchema($data);

        return $this->pushSchema($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function service(array $data): self
    {
        $schema = new ServiceSchema($data);

        return $this->pushSchema($schema);
    }

    public function forBlogListing(?string $title = null, ?string $description = null, array $breadcrumbs = [], ?string $canonical = null): self
    {
        $pageNumber = max(1, (int) request()->integer('page', 1));
        $resolvedTitle = $this->applyPaginationTitle($title ?: 'Blog', $pageNumber);

        $this->title($resolvedTitle)
            ->description($description ?: (data_get($this->config, 'site.default_description')))
            ->canonical($canonical ?: request()->fullUrl())
            ->website();

        $this->maybeApplyListingBreadcrumbs($breadcrumbs, $resolvedTitle);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function forBlogPost(mixed $post, array $overrides = []): self
    {
        $title = DataResolver::string($post, ['seo_title', 'meta_title', 'title', 'name']);
        $description = DataResolver::string($post, ['seo_description', 'meta_description', 'excerpt', 'summary', 'description', 'content']);
        $canonical = $this->urlNormalizer->normalize(DataResolver::string($post, ['canonical_url', 'canonical', 'url', 'permalink']) ?? request()->fullUrl(), $this->ignoredQueryParameters());
        $image = DataResolver::string($post, ['seo_image', 'og_image', 'cover_url', 'cover', 'image', 'thumbnail_url']);
        $author = DataResolver::string($post, ['author_name', 'author.name', 'user.name']);
        $publishedAt = DataResolver::first($post, ['published_at', 'date_published', 'created_at']);
        $updatedAt = DataResolver::first($post, ['updated_at', 'date_modified', 'published_at']);

        $this->title($overrides['title'] ?? $title)
            ->description($overrides['description'] ?? $description)
            ->canonical($overrides['canonical'] ?? $canonical)
            ->image($overrides['image'] ?? $image)
            ->organization();

        $this->article([
            'headline' => $overrides['headline'] ?? $title,
            'description' => $overrides['description'] ?? $description,
            'image' => $overrides['schema_image'] ?? $image,
            'datePublished' => $overrides['datePublished'] ?? $publishedAt,
            'dateModified' => $overrides['dateModified'] ?? $updatedAt,
            'author' => $overrides['author'] ?? $author,
            'mainEntityOfPage' => $overrides['mainEntityOfPage'] ?? $canonical,
        ]);

        $this->maybeApplyDetailBreadcrumbs($overrides['breadcrumbs'] ?? [], $title ?: 'Article', $canonical);

        return $this;
    }

    public function forProductListing(?string $title = null, ?string $description = null, array $breadcrumbs = [], ?string $canonical = null): self
    {
        $pageNumber = max(1, (int) request()->integer('page', 1));
        $resolvedTitle = $this->applyPaginationTitle($title ?: 'Products', $pageNumber);

        $this->title($resolvedTitle)
            ->description($description ?: data_get($this->config, 'site.default_description'))
            ->canonical($canonical ?: request()->fullUrl());

        $this->maybeApplyListingBreadcrumbs($breadcrumbs, $resolvedTitle);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function forProduct(mixed $product, array $overrides = []): self
    {
        $title = DataResolver::string($product, ['seo_title', 'meta_title', 'name', 'title']);
        $description = DataResolver::string($product, ['seo_description', 'meta_description', 'summary', 'excerpt', 'description']);
        $canonical = $this->urlNormalizer->normalize(DataResolver::string($product, ['canonical_url', 'canonical', 'url', 'permalink']) ?? request()->fullUrl(), $this->ignoredQueryParameters());
        $image = DataResolver::string($product, ['seo_image', 'og_image', 'cover_url', 'image', 'thumbnail_url']);

        $this->title($overrides['title'] ?? $title)
            ->description($overrides['description'] ?? $description)
            ->canonical($overrides['canonical'] ?? $canonical)
            ->image($overrides['image'] ?? $image);

        $this->product([
            'name' => $overrides['name'] ?? $title,
            'description' => $overrides['schema_description'] ?? $description,
            'image' => $overrides['schema_image'] ?? $image,
            'sku' => $overrides['sku'] ?? DataResolver::string($product, ['sku']),
            'brand' => $overrides['brand'] ?? DataResolver::string($product, ['brand', 'brand.name']),
            'price' => $overrides['price'] ?? DataResolver::first($product, ['price']),
            'priceCurrency' => $overrides['priceCurrency'] ?? DataResolver::string($product, ['price_currency', 'currency']),
            'availability' => $overrides['availability'] ?? DataResolver::string($product, ['availability']),
            'url' => $overrides['url'] ?? $canonical,
        ]);

        $this->maybeApplyDetailBreadcrumbs($overrides['breadcrumbs'] ?? [], $title ?: 'Product', $canonical);

        return $this;
    }

    public function forServiceListing(?string $title = null, ?string $description = null, array $breadcrumbs = [], ?string $canonical = null): self
    {
        $pageNumber = max(1, (int) request()->integer('page', 1));
        $resolvedTitle = $this->applyPaginationTitle($title ?: 'Services', $pageNumber);

        $this->title($resolvedTitle)
            ->description($description ?: data_get($this->config, 'site.default_description'))
            ->canonical($canonical ?: request()->fullUrl());

        $this->maybeApplyListingBreadcrumbs($breadcrumbs, $resolvedTitle);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function forService(mixed $service, array $overrides = []): self
    {
        $title = DataResolver::string($service, ['seo_title', 'meta_title', 'name', 'title']);
        $description = DataResolver::string($service, ['seo_description', 'meta_description', 'summary', 'excerpt', 'description']);
        $canonical = $this->urlNormalizer->normalize(DataResolver::string($service, ['canonical_url', 'canonical', 'url', 'permalink']) ?? request()->fullUrl(), $this->ignoredQueryParameters());
        $image = DataResolver::string($service, ['seo_image', 'og_image', 'cover_url', 'image', 'thumbnail_url']);

        $this->title($overrides['title'] ?? $title)
            ->description($overrides['description'] ?? $description)
            ->canonical($overrides['canonical'] ?? $canonical)
            ->image($overrides['image'] ?? $image);

        $this->service([
            'name' => $overrides['name'] ?? $title,
            'description' => $overrides['schema_description'] ?? $description,
            'provider' => $overrides['provider'] ?? DataResolver::string($service, ['provider', 'provider.name', 'company.name']),
            'areaServed' => $overrides['areaServed'] ?? DataResolver::string($service, ['area_served', 'coverage_area']),
            'url' => $overrides['url'] ?? $canonical,
        ]);

        $this->maybeApplyDetailBreadcrumbs($overrides['breadcrumbs'] ?? [], $title ?: 'Service', $canonical);

        return $this;
    }

    public function sitemap(): SitemapManager
    {
        return $this->sitemapManager;
    }

    private function applyPaginationTitle(string $title, int $pageNumber): string
    {
        return $pageNumber > 1
            ? sprintf('%s %s Page %d', $title, Str::of(data_get($this->config, 'site.title_separator', '|'))->trim(), $pageNumber)
            : $title;
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     */
    private function maybeApplyListingBreadcrumbs(array $breadcrumbs, string $title): void
    {
        if ($breadcrumbs !== []) {
            $this->breadcrumbs($breadcrumbs);

            return;
        }

        $homeUrl = $this->urlNormalizer->normalize((string) data_get($this->config, 'site.url'));
        $currentUrl = $this->urlNormalizer->normalize(request()->fullUrl(), $this->ignoredQueryParameters());

        if ($homeUrl && $currentUrl) {
            $this->breadcrumbs([
                ['name' => 'Home', 'url' => $homeUrl],
                ['name' => $title, 'url' => $currentUrl],
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     */
    private function maybeApplyDetailBreadcrumbs(array $breadcrumbs, string $title, ?string $canonical): void
    {
        if ($breadcrumbs !== []) {
            $this->breadcrumbs($breadcrumbs);

            return;
        }

        $homeUrl = $this->urlNormalizer->normalize((string) data_get($this->config, 'site.url'));

        if ($homeUrl && $canonical) {
            $this->breadcrumbs([
                ['name' => 'Home', 'url' => $homeUrl],
                ['name' => $title, 'url' => $canonical],
            ]);
        }
    }

    /**
     * @param  SchemaContract  $schema
     */
    private function pushSchema(object $schema): self
    {
        if (method_exists($schema, 'isValid') && $schema->isValid()) {
            $this->data->addSchema($schema->toArray());

            return $this;
        }

        $this->logWarning(sprintf('Skipped invalid schema: %s', $schema::class));

        return $this;
    }

    /**
     * @return array<int, string>
     */
    private function ignoredQueryParameters(): array
    {
        return array_values((array) data_get($this->config, 'defaults.canonical_ignore_query', []));
    }

    private function shouldForceNoindex(): bool
    {
        if (app()->environment('local') && (bool) data_get($this->config, 'environment.noindex_on_local', true)) {
            return true;
        }

        return app()->environment('staging') && (bool) data_get($this->config, 'environment.noindex_on_staging', true);
    }

    private function logWarning(string $message): void
    {
        if ((bool) data_get($this->config, 'debug.log_warnings', false)) {
            logger()->warning($message);
        }
    }
}
