<?php

namespace JasanyaTech\SEO\Schema;

final class WebSiteSchema extends AbstractSchema
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $overrides
     */
    public function __construct(
        private array $config,
        private array $overrides = [],
    ) {}

    public function isValid(): bool
    {
        $payload = $this->toArray();

        return filled($payload['name'] ?? null) && filled($payload['url'] ?? null);
    }

    protected function payload(): array
    {
        $site = $this->config['site'] ?? [];
        $website = $this->config['website'] ?? [];
        $name = $this->overrides['name'] ?? ($site['name'] ?? null);
        $url = $this->overrides['url'] ?? ($site['url'] ?? null);
        $searchUrl = $this->overrides['search_url'] ?? ($website['search_url'] ?? null);
        $enableSearchAction = (bool) ($this->overrides['enable_search_action'] ?? ($website['enable_search_action'] ?? false));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $name,
            'url' => $url,
            'potentialAction' => $enableSearchAction && filled($searchUrl)
                ? [
                    '@type' => 'SearchAction',
                    'target' => $searchUrl,
                    'query-input' => 'required name=search_term_string',
                ]
                : null,
        ];
    }
}
