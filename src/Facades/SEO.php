<?php

namespace JasanyaTech\SEO\Facades;

use Illuminate\Support\Facades\Facade;
use JasanyaTech\SEO\SeoManager;

/**
 * @method static \JasanyaTech\SEO\SeoManager reset()
 * @method static \JasanyaTech\SEO\SeoManager set(\JasanyaTech\SEO\DTOs\SeoData $seo)
 * @method static \JasanyaTech\SEO\DTOs\SeoData get()
 * @method static \JasanyaTech\SEO\SeoManager title(?string $title)
 * @method static \JasanyaTech\SEO\SeoManager description(?string $description)
 * @method static \JasanyaTech\SEO\SeoManager canonical(?string $url)
 * @method static \JasanyaTech\SEO\SeoManager robots(string|array|null $robots)
 * @method static \JasanyaTech\SEO\SeoManager image(?string $url, ?string $alt = null)
 * @method static \JasanyaTech\SEO\SeoManager locale(?string $locale)
 * @method static \JasanyaTech\SEO\SeoManager hreflang(array $hreflang)
 * @method static \JasanyaTech\SEO\SeoManager website(array $overrides = [])
 * @method static \JasanyaTech\SEO\SeoManager organization(array $overrides = [])
 * @method static \JasanyaTech\SEO\SeoManager breadcrumbs(array $items)
 * @method static \JasanyaTech\SEO\SeoManager article(array $data)
 * @method static \JasanyaTech\SEO\SeoManager product(array $data)
 * @method static \JasanyaTech\SEO\SeoManager service(array $data)
 * @method static \JasanyaTech\SEO\SeoManager forBlogListing(?string $title = null, ?string $description = null, array $breadcrumbs = [], ?string $canonical = null)
 * @method static \JasanyaTech\SEO\SeoManager forBlogPost(mixed $post, array $overrides = [])
 * @method static \JasanyaTech\SEO\SeoManager forProductListing(?string $title = null, ?string $description = null, array $breadcrumbs = [], ?string $canonical = null)
 * @method static \JasanyaTech\SEO\SeoManager forProduct(mixed $product, array $overrides = [])
 * @method static \JasanyaTech\SEO\SeoManager forServiceListing(?string $title = null, ?string $description = null, array $breadcrumbs = [], ?string $canonical = null)
 * @method static \JasanyaTech\SEO\SeoManager forService(mixed $service, array $overrides = [])
 * @method static \JasanyaTech\SEO\Sitemap\SitemapManager sitemap()
 *
 * @see SeoManager
 */
class SEO extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'perfect-seo';
    }
}
