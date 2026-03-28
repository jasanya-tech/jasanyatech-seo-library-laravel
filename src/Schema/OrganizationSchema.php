<?php

namespace JasanyaTech\SEO\Schema;

final class OrganizationSchema extends AbstractSchema
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
        $reference = $this->reference();

        return filled($reference['name'] ?? null) && filled($reference['url'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function reference(): array
    {
        $organization = $this->config['organization'] ?? [];

        return $this->cleanOrganization([
            '@type' => 'Organization',
            'name' => $this->overrides['name'] ?? ($organization['name'] ?? null) ?? ($this->config['site']['name'] ?? null),
            'url' => $this->overrides['url'] ?? ($organization['url'] ?? null) ?? ($this->config['site']['url'] ?? null),
            'logo' => filled($this->overrides['logo'] ?? ($organization['logo'] ?? null))
                ? [
                    '@type' => 'ImageObject',
                    'url' => $this->overrides['logo'] ?? $organization['logo'],
                ]
                : null,
            'sameAs' => $this->overrides['same_as'] ?? ($organization['same_as'] ?? []),
            'email' => $this->overrides['email'] ?? ($organization['email'] ?? null),
            'telephone' => $this->overrides['telephone'] ?? ($organization['telephone'] ?? null),
        ]);
    }

    protected function payload(): array
    {
        return [
            '@context' => 'https://schema.org',
            ...$this->reference(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function cleanOrganization(array $payload): array
    {
        return array_filter($payload, function (mixed $value): bool {
            if ($value === null) {
                return false;
            }

            if (is_string($value)) {
                return trim($value) !== '';
            }

            return ! (is_array($value) && $value === []);
        });
    }
}
