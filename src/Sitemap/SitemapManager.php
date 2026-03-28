<?php

namespace JasanyaTech\SEO\Sitemap;

use Illuminate\Support\Str;
use JasanyaTech\SEO\DTOs\SitemapUrl;
use JasanyaTech\SEO\Support\UrlNormalizer;

class SitemapManager
{
    /**
     * @var array<string, callable(): iterable<mixed>>
     */
    private array $sources = [];

    /**
     * @param  array<string, mixed>  $sitemapConfig
     * @param  array<string, mixed>  $siteConfig
     */
    public function __construct(
        private array $sitemapConfig,
        private array $siteConfig,
        private UrlNormalizer $urlNormalizer,
    ) {}

    public function register(string $name, callable $resolver): self
    {
        $this->sources[$this->normalizeName($name)] = $resolver;

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($this->normalizeName($name), $this->sources);
    }

    public function clear(): self
    {
        $this->sources = [];

        return $this;
    }

    /**
     * @return array<string, array<int, array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}>>
     */
    public function resolveAll(): array
    {
        $resolved = [];

        foreach (array_keys($this->sources) as $name) {
            $resolvedSource = $this->resolve($name);

            if ($resolvedSource !== []) {
                $resolved[$name] = $resolvedSource;
            }
        }

        return $resolved;
    }

    /**
     * @return array<int, array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}>
     */
    public function resolve(string $name): array
    {
        $normalizedName = $this->normalizeName($name);
        $resolver = $this->sources[$normalizedName] ?? null;

        if (! $resolver) {
            return [];
        }

        $entries = value($resolver);

        if ($entries instanceof \Traversable) {
            $entries = iterator_to_array($entries);
        }

        if (! is_array($entries)) {
            $entries = (array) $entries;
        }

        $resolved = [];

        foreach ($entries as $entry) {
            $sitemapUrl = SitemapUrl::from($entry, $this->sitemapConfig, $this->urlNormalizer);

            if ($sitemapUrl !== null) {
                $resolved[] = $sitemapUrl->toArray();
            }
        }

        return $resolved;
    }

    /**
     * @return array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}|null
     */
    public function defaultHome(): ?array
    {
        if (! (bool) ($this->sitemapConfig['include_home'] ?? true)) {
            return null;
        }

        $url = $this->urlNormalizer->normalize((string) ($this->siteConfig['url'] ?? config('app.url')));

        if (! $url) {
            return null;
        }

        return [
            'url' => $url,
            'lastmod' => null,
            'changefreq' => $this->sitemapConfig['default_changefreq'] ?? 'weekly',
            'priority' => is_numeric($this->sitemapConfig['default_priority'] ?? null) ? (float) $this->sitemapConfig['default_priority'] : null,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->sources);
    }

    private function normalizeName(string $name): string
    {
        return Str::slug($name);
    }
}
