# Perfect SEO

Reusable Laravel 13 SEO support package for Blade applications with a Blade-first API centered around:

```blade
<x-seo::meta />
```

The package is designed for real production usage: safe defaults, per-page overrides, JSON-LD schema support, automatic `sitemap.xml`, automatic `robots.txt`, and developer-friendly preset builders.

## Architecture

`Perfect SEO` is split into small responsibilities:

- `SeoManager` stores request-scoped SEO state and exposes the public API.
- `MetaRenderer` turns current SEO state into deduplicated Blade-safe tags.
- `Schema/*` contains JSON-LD schema builders with validation and omission of invalid fields.
- `Sitemap/*` handles source registration, chunking, and XML generation.
- `Robots/RobotsRenderer` generates plain text `robots.txt`.
- `Components/Meta` is the Blade entry point for `<x-seo::meta />`.

## Folder Structure

```text
packages/seo-library-laravel/
├── composer.json
├── config/seo.php
├── resources/views/
│   ├── components/meta.blade.php
│   └── sitemap/
├── routes/seo.php
├── src/
│   ├── Components/
│   ├── Contracts/
│   ├── DTOs/
│   ├── Facades/
│   ├── Http/Controllers/
│   ├── Renderers/
│   ├── Robots/
│   ├── Schema/
│   ├── Sitemap/
│   ├── Support/
│   ├── SeoManager.php
│   └── SeoServiceProvider.php
└── tests/
```

## Public API

```php
use JasanyaTech\SEO\Facades\SEO;

SEO::title('Home');
SEO::description('Welcome to our website');
SEO::canonical(url()->current());
SEO::robots('index,follow');
SEO::image(asset('images/og/default.jpg'), 'Default social image');
SEO::website();
SEO::organization();
SEO::breadcrumbs([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Blog', 'url' => route('blog.index')],
]);

SEO::article([
    'headline' => $post->title,
    'description' => $post->excerpt,
    'image' => $post->cover_url,
    'datePublished' => $post->published_at,
    'dateModified' => $post->updated_at,
    'author' => $post->author_name,
    'mainEntityOfPage' => route('blog.show', $post->slug),
]);

SEO::forBlogPost($post);
SEO::forProduct($product);
SEO::forService($service);

SEO::sitemap()->register('posts', fn () => Post::query()
    ->published()
    ->get(['slug', 'updated_at'])
    ->map(fn (Post $post) => [
        'url' => route('blog.show', $post->slug),
        'lastmod' => $post->updated_at,
    ]));
```

## Installation

If you extract this into its own repository:

```bash
composer require jasanya/seo-library-laravel
```

For a local path package in another Laravel app:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/seo-library-laravel"
        }
    ]
}
```

Then publish the config:

```bash
php artisan vendor:publish --tag=seo-config
```

## Configuration

Default config lives in `config/seo.php` and includes:

- site defaults
- canonical query ignore rules
- default robots
- default Open Graph image
- locale and alternate locales
- organization and website schema data
- sitemap settings
- robots settings
- environment-aware safety rules

Example:

```php
return [
    'site' => [
        'name' => env('SEO_SITE_NAME', config('app.name')),
        'url' => env('APP_URL'),
        'title_separator' => '|',
        'default_title' => null,
        'default_description' => null,
        'default_locale' => 'id_ID',
        'alternate_locales' => [],
    ],
];
```

## Blade Usage

Place the component inside `<head>`:

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo::meta />
</head>
```

You can also pass a DTO directly:

```blade
<x-seo::meta :seo="$seo" />
```

## Controller Usage

```php
use App\Models\Post;
use JasanyaTech\SEO\Facades\SEO;

public function show(Post $post)
{
    SEO::forBlogPost($post, [
        'breadcrumbs' => [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
        ],
    ]);

    return view('blog.show', compact('post'));
}
```

## Presets

### Homepage

```php
SEO::title('Home')
    ->description('Welcome to our website')
    ->canonical(route('home'))
    ->website()
    ->organization();
```

### Blog Index

```php
SEO::forBlogListing(
    title: 'Blog',
    description: 'Latest articles and updates',
    breadcrumbs: [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Blog', 'url' => route('blog.index')],
    ],
    canonical: route('blog.index'),
);
```

### Blog Detail with Article Schema

```php
SEO::forBlogPost($post, [
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Blog', 'url' => route('blog.index')],
        ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
    ],
]);
```

### Product Index

```php
SEO::forProductListing(
    title: 'Products',
    description: 'Browse our product catalog',
    canonical: route('products.index'),
);
```

### Product Detail

```php
SEO::forProduct($product, [
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Products', 'url' => route('products.index')],
        ['name' => $product->name, 'url' => route('products.show', $product->slug)],
    ],
]);
```

### Service Index

```php
SEO::forServiceListing(
    title: 'Services',
    description: 'Professional service catalog',
    canonical: route('services.index'),
);
```

### Service Detail

```php
SEO::forService($service, [
    'breadcrumbs' => [
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Services', 'url' => route('services.index')],
        ['name' => $service->name, 'url' => route('services.show', $service->slug)],
    ],
]);
```

## Breadcrumb Integration

```php
SEO::breadcrumbs([
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Blog', 'url' => route('blog.index')],
    ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
]);
```

This automatically prepares `BreadcrumbList` JSON-LD and stores the cleaned breadcrumb data for the current request.

## Schema Usage

Supported out of the box:

- `WebSite`
- `Organization`
- `BreadcrumbList`
- `Article`
- `Product`
- `Service`

Only valid schema fields are emitted. Missing or misleading fields are omitted instead of guessed.

## Sitemap Registration

Built-in routes:

- `/sitemap.xml`
- `/sitemaps/{source}.xml`
- `/sitemaps/{source}-{page}.xml`

Register sitemap sources anywhere during bootstrapping, for example in `AppServiceProvider`:

```php
use App\Models\Post;
use JasanyaTech\SEO\Facades\SEO;

public function boot(): void
{
    SEO::sitemap()->register('posts', fn () => Post::query()
        ->whereNotNull('published_at')
        ->get()
        ->map(fn (Post $post) => [
            'url' => route('blog.show', $post->slug),
            'lastmod' => $post->updated_at,
            'changefreq' => 'weekly',
            'priority' => 0.7,
        ]));
}
```

The package:

- normalizes URLs
- skips non-indexable entries
- chunks large sources
- adds the homepage automatically if configured

## robots.txt

Built-in route:

- `/robots.txt`

Default output example:

```text
User-agent: *
Allow: /

Sitemap: https://example.com/sitemap.xml
```

On non-production environments, the package can disallow all crawling automatically if `robots.disallow_non_production` is enabled.

## Troubleshooting

- If your Blade output does not change, clear cached views and config.
- If Vite assets are missing in the UI, run `npm run dev` or `npm run build`.
- If your sitemap is empty, verify that your registered source returns absolute public URLs.
- If JSON-LD is missing, check that the required source fields actually exist.

## Testing

Package tests cover:

- title rendering
- description rendering
- canonical normalization
- robots meta rendering
- Open Graph and Twitter tags
- JSON-LD schema output
- blog/product/service presets
- sitemap index and child sitemap responses
- robots.txt output

Run the Laravel test suite:

```bash
php artisan test --compact
```
