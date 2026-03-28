<?php

namespace JasanyaTech\SEO\DTOs;

final class SeoData
{
    /**
     * @param  array<int, array{name: string, url: string}>  $hreflang
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     * @param  array<int, array<string, mixed>>  $schemas
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?string $ogType = null,
        public ?string $twitterCard = null,
        public ?string $locale = null,
        public array $hreflang = [],
        public array $breadcrumbs = [],
        public array $schemas = [],
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            title: data_get($config, 'site.default_title') ?: data_get($config, 'site.name'),
            description: data_get($config, 'site.default_description'),
            robots: data_get($config, 'defaults.robots', 'index,follow'),
            image: data_get($config, 'defaults.image'),
            imageAlt: data_get($config, 'defaults.image_alt'),
            ogType: 'website',
            twitterCard: data_get($config, 'defaults.twitter_card', 'summary_large_image'),
            locale: data_get($config, 'site.default_locale'),
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function addSchema(array $schema): self
    {
        if ($schema !== []) {
            $this->schemas[] = $schema;
        }

        return $this;
    }
}
