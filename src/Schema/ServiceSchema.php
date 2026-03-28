<?php

namespace JasanyaTech\SEO\Schema;

final class ServiceSchema extends AbstractSchema
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private array $data) {}

    public function isValid(): bool
    {
        return filled($this->data['name'] ?? null);
    }

    protected function payload(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $this->data['name'] ?? null,
            'description' => $this->data['description'] ?? null,
            'provider' => filled($this->data['provider'] ?? null)
                ? [
                    '@type' => 'Organization',
                    'name' => $this->data['provider'],
                ]
                : null,
            'areaServed' => filled($this->data['areaServed'] ?? null) ? $this->data['areaServed'] : null,
            'url' => $this->data['url'] ?? null,
        ];
    }
}
