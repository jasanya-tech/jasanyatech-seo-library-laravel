<?php

namespace JasanyaTech\SEO\Sitemap;

class SitemapBuilder
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private array $config,
        private SitemapManager $sitemapManager,
    ) {}

    /**
     * @return array{
     *     type: 'urlset'|'index',
     *     urls?: array<int, array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}>,
     *     sitemaps?: array<int, array{loc: string, lastmod: string|null}>
     * }
     */
    public function buildIndex(): array
    {
        $sources = $this->resolveSources();
        $chunkSize = max(1, (int) data_get($this->config, 'sitemap.chunk_size', 1000));
        $sitemaps = [];

        foreach ($sources as $name => $urls) {
            $chunks = array_chunk($urls, $chunkSize);

            foreach ($chunks as $index => $chunk) {
                $page = $index + 1;
                $lastmod = collect($chunk)
                    ->pluck('lastmod')
                    ->filter()
                    ->sortDesc()
                    ->first();

                $sitemaps[] = [
                    'loc' => count($chunks) === 1
                        ? $this->buildSitemapLocation($name)
                        : $this->buildSitemapLocation($name, $page),
                    'lastmod' => $lastmod,
                    'source' => $name,
                    'page' => $page,
                    'urls' => $chunk,
                ];
            }
        }

        if ($sitemaps === []) {
            return [
                'type' => 'urlset',
                'urls' => [],
            ];
        }

        if (count($sitemaps) === 1) {
            return [
                'type' => 'urlset',
                'urls' => $sitemaps[0]['urls'],
            ];
        }

        return [
            'type' => 'index',
            'sitemaps' => array_map(
                fn (array $sitemap): array => [
                    'loc' => $sitemap['loc'],
                    'lastmod' => $sitemap['lastmod'],
                ],
                $sitemaps,
            ),
        ];
    }

    /**
     * @return array<int, array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}>|null
     */
    public function buildSource(string $source, int $page = 1): ?array
    {
        $sources = $this->resolveSources();

        if (! array_key_exists($source, $sources)) {
            return null;
        }

        $chunkSize = max(1, (int) data_get($this->config, 'sitemap.chunk_size', 1000));
        $chunks = array_chunk($sources[$source], $chunkSize);

        return $chunks[$page - 1] ?? null;
    }

    /**
     * @return array<string, array<int, array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}>>
     */
    private function resolveSources(): array
    {
        $sources = $this->sitemapManager->resolveAll();
        $home = $this->sitemapManager->defaultHome();

        if ($home !== null) {
            $pages = $sources['pages'] ?? [];
            $alreadyHasHome = collect($pages)->contains(fn (array $entry): bool => $entry['url'] === $home['url']);

            if (! $alreadyHasHome) {
                array_unshift($pages, $home);
            }

            $sources['pages'] = $pages;
        }

        ksort($sources);

        return $sources;
    }

    private function buildSitemapLocation(string $source, ?int $page = null): string
    {
        $baseUrl = rtrim((string) (data_get($this->config, 'site.url') ?: config('app.url')), '/');
        $prefix = trim((string) data_get($this->config, 'routes.sitemaps_prefix', 'sitemaps'), '/');
        $filename = $page === null ? sprintf('%s.xml', $source) : sprintf('%s-%d.xml', $source, $page);

        return $baseUrl.'/'.$prefix.'/'.$filename;
    }
}
