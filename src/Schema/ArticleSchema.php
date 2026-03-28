<?php

namespace JasanyaTech\SEO\Schema;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class ArticleSchema extends AbstractSchema
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private array $data,
        private array $config,
    ) {}

    public function isValid(): bool
    {
        $payload = $this->toArray();

        return filled($payload['headline'] ?? null) && filled($payload['mainEntityOfPage']['@id'] ?? null);
    }

    protected function payload(): array
    {
        $publisher = new OrganizationSchema($this->config);
        $image = $this->normalizeImages($this->data['image'] ?? null);
        $mainEntity = $this->data['mainEntityOfPage'] ?? $this->data['url'] ?? null;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->data['headline'] ?? null,
            'description' => $this->data['description'] ?? null,
            'image' => $image,
            'author' => filled($this->data['author'] ?? null)
                ? [
                    '@type' => 'Person',
                    'name' => $this->data['author'],
                ]
                : null,
            'publisher' => $publisher->isValid() ? $publisher->reference() : null,
            'datePublished' => $this->normalizeDate($this->data['datePublished'] ?? null),
            'dateModified' => $this->normalizeDate($this->data['dateModified'] ?? ($this->data['datePublished'] ?? null)),
            'mainEntityOfPage' => filled($mainEntity)
                ? [
                    '@type' => 'WebPage',
                    '@id' => $mainEntity,
                ]
                : null,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function normalizeImages(mixed $images): ?array
    {
        if ($images === null || $images === '') {
            return null;
        }

        $resolved = array_values(array_filter((array) $images, fn (mixed $image): bool => is_string($image) && trim($image) !== ''));

        return $resolved !== [] ? $resolved : null;
    }

    private function normalizeDate(mixed $value): ?string
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
}
