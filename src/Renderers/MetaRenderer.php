<?php

namespace JasanyaTech\SEO\Renderers;

use Illuminate\Support\Str;
use JasanyaTech\SEO\DTOs\SeoData;
use JasanyaTech\SEO\SeoManager;
use JasanyaTech\SEO\Support\RobotsDirectives;
use JasanyaTech\SEO\Support\TextSanitizer;
use JasanyaTech\SEO\Support\UrlNormalizer;

class MetaRenderer
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private array $config,
        private SeoManager $seoManager,
        private UrlNormalizer $urlNormalizer,
        private TextSanitizer $textSanitizer,
    ) {}

    /**
     * @return array{
     *     title: string|null,
     *     links: array<int, array<string, string>>,
     *     metas: array<int, array{attribute: string, key: string, content: string}>,
     *     json_ld: array<int, array<string, mixed>>
     * }
     */
    public function render(?SeoData $seo = null): array
    {
        $seo ??= $this->seoManager->get();

        $title = $this->resolveTitle($seo);
        $description = $this->resolveDescription($seo);
        $canonical = $this->resolveCanonical($seo);
        $robots = RobotsDirectives::normalize(
            $seo->robots,
            (string) data_get($this->config, 'defaults.robots', 'index,follow'),
            $this->shouldForceNoindex(),
        );
        $image = $this->urlNormalizer->normalize($seo->image ?: data_get($this->config, 'defaults.image'));
        $imageAlt = $this->textSanitizer->safeString($seo->imageAlt ?: data_get($this->config, 'defaults.image_alt'));
        $locale = $this->textSanitizer->safeString($seo->locale ?: data_get($this->config, 'site.default_locale'));
        $ogType = $this->textSanitizer->safeString($seo->ogType ?: 'website');
        $twitterCard = $this->textSanitizer->safeString($seo->twitterCard ?: data_get($this->config, 'defaults.twitter_card', 'summary_large_image'));
        $siteName = $this->textSanitizer->safeString((string) data_get($this->config, 'site.name'));
        $twitterSite = $this->textSanitizer->safeString(data_get($this->config, 'social.twitter_site'));

        $links = [];

        if ($canonical) {
            $links[] = [
                'rel' => 'canonical',
                'href' => $canonical,
            ];
        }

        foreach ($this->resolveHreflang($seo) as $alternate) {
            $links[] = [
                'rel' => 'alternate',
                'href' => $alternate['url'],
                'hreflang' => $alternate['name'],
            ];
        }

        $metas = [];

        $this->pushMeta($metas, 'name', 'description', $description);
        $this->pushMeta($metas, 'name', 'robots', $robots);
        $this->pushMeta($metas, 'property', 'og:title', $title);
        $this->pushMeta($metas, 'property', 'og:description', $description);
        $this->pushMeta($metas, 'property', 'og:type', $ogType);
        $this->pushMeta($metas, 'property', 'og:url', $canonical);
        $this->pushMeta($metas, 'property', 'og:image', $image);
        $this->pushMeta($metas, 'property', 'og:image:alt', $imageAlt);
        $this->pushMeta($metas, 'property', 'og:site_name', $siteName);
        $this->pushMeta($metas, 'property', 'og:locale', $locale);
        $this->pushMeta($metas, 'name', 'twitter:card', $twitterCard);
        $this->pushMeta($metas, 'name', 'twitter:title', $title);
        $this->pushMeta($metas, 'name', 'twitter:description', $description);
        $this->pushMeta($metas, 'name', 'twitter:image', $image);
        $this->pushMeta($metas, 'name', 'twitter:image:alt', $imageAlt);
        $this->pushMeta($metas, 'name', 'twitter:site', $twitterSite);

        return [
            'title' => $title,
            'links' => $links,
            'metas' => array_values($metas),
            'json_ld' => $this->resolveSchemas($seo),
        ];
    }

    private function resolveTitle(SeoData $seo): ?string
    {
        $siteName = $this->textSanitizer->safeString((string) data_get($this->config, 'site.name'));
        $defaultTitle = $this->textSanitizer->safeString((string) (data_get($this->config, 'site.default_title') ?: $siteName));
        $title = $this->textSanitizer->safeString($seo->title ?: $defaultTitle);
        $separator = trim((string) data_get($this->config, 'site.title_separator', '|'));

        if (! $title) {
            return null;
        }

        if (! $siteName || $title === $siteName || $title === $defaultTitle) {
            return $title;
        }

        $needle = sprintf('%s %s', $separator, $siteName);

        return Str::contains($title, $needle)
            ? $title
            : trim(sprintf('%s %s %s', $title, $separator, $siteName));
    }

    private function resolveDescription(SeoData $seo): ?string
    {
        return $this->textSanitizer->plainText(
            $seo->description ?: data_get($this->config, 'site.default_description'),
            (int) data_get($this->config, 'defaults.description_limit', 160),
        );
    }

    private function resolveCanonical(SeoData $seo): ?string
    {
        $canonical = $seo->canonical ?: request()->fullUrl();

        return $this->urlNormalizer->normalize(
            $canonical,
            (array) data_get($this->config, 'defaults.canonical_ignore_query', []),
        );
    }

    /**
     * @param  array<int, array{attribute: string, key: string, content: string}>  $metas
     */
    private function pushMeta(array &$metas, string $attribute, string $key, ?string $content): void
    {
        $resolvedContent = $this->textSanitizer->safeString($content);

        if (! $resolvedContent) {
            return;
        }

        $metas["{$attribute}:{$key}"] = [
            'attribute' => $attribute,
            'key' => $key,
            'content' => $resolvedContent,
        ];
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function resolveHreflang(SeoData $seo): array
    {
        $configured = $seo->hreflang;

        if ($configured === []) {
            foreach ((array) data_get($this->config, 'site.alternate_locales', []) as $locale => $item) {
                if (is_array($item)) {
                    $configured[] = [
                        'name' => $item['locale'] ?? (string) $locale,
                        'url' => $item['url'] ?? '',
                    ];
                } else {
                    $configured[] = [
                        'name' => (string) $locale,
                        'url' => (string) $item,
                    ];
                }
            }
        }

        $alternates = [];

        foreach ($configured as $alternate) {
            $locale = $this->textSanitizer->safeString($alternate['name'] ?? null);
            $url = $this->urlNormalizer->normalize($alternate['url'] ?? null);

            if ($locale && $url) {
                $alternates[] = [
                    'name' => $locale,
                    'url' => $url,
                ];
            }
        }

        return $alternates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveSchemas(SeoData $seo): array
    {
        $seen = [];
        $schemas = [];

        foreach ($seo->schemas as $schema) {
            if (! is_array($schema) || ! isset($schema['@context'], $schema['@type'])) {
                continue;
            }

            $fingerprint = json_encode($schema);

            if ($fingerprint === false || isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $schemas[] = $schema;
        }

        return $schemas;
    }

    private function shouldForceNoindex(): bool
    {
        if (app()->environment('local') && (bool) data_get($this->config, 'environment.noindex_on_local', true)) {
            return true;
        }

        return app()->environment('staging') && (bool) data_get($this->config, 'environment.noindex_on_staging', true);
    }
}
