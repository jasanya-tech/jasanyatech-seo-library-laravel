<?php

namespace JasanyaTech\SEO\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use JasanyaTech\SEO\Support\UrlNormalizer;

final class SitemapUrl
{
    public function __construct(
        public string $url,
        public ?string $lastmod = null,
        public ?string $changefreq = null,
        public ?float $priority = null,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     */
    public static function from(mixed $item, array $defaults, UrlNormalizer $urlNormalizer): ?self
    {
        if (is_string($item)) {
            $item = ['url' => $item];
        }

        if (! is_array($item) && ! is_object($item)) {
            return null;
        }

        if ((bool) data_get($item, 'indexable', true) === false) {
            return null;
        }

        $url = $urlNormalizer->normalize((string) (data_get($item, 'canonical_url') ?? data_get($item, 'canonical') ?? data_get($item, 'url') ?? data_get($item, 'loc') ?? ''));

        if ($url === null) {
            return null;
        }

        $priority = data_get($item, 'priority', $defaults['default_priority'] ?? null);
        $priority = is_numeric($priority) ? (float) $priority : null;

        return new self(
            url: $url,
            lastmod: self::normalizeDate(data_get($item, 'lastmod') ?? data_get($item, 'updated_at') ?? data_get($item, 'date_modified')),
            changefreq: self::normalizeChangefreq((string) (data_get($item, 'changefreq') ?? $defaults['default_changefreq'] ?? '')),
            priority: $priority,
        );
    }

    /**
     * @return array{url: string, lastmod: string|null, changefreq: string|null, priority: float|null}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'lastmod' => $this->lastmod,
            'changefreq' => $this->changefreq,
            'priority' => $this->priority,
        ];
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toAtomString();
        }

        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toAtomString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function normalizeChangefreq(string $value): ?string
    {
        $changefreq = strtolower(trim($value));
        $allowed = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

        return in_array($changefreq, $allowed, true) ? $changefreq : null;
    }
}
