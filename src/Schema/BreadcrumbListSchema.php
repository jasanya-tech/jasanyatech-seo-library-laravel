<?php

namespace JasanyaTech\SEO\Schema;

final class BreadcrumbListSchema extends AbstractSchema
{
    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public function __construct(private array $items) {}

    public function isValid(): bool
    {
        return count($this->items) >= 1;
    }

    protected function payload(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ],
                $this->items,
                array_keys($this->items),
            ),
        ];
    }
}
