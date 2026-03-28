<?php

namespace JasanyaTech\SEO\Schema;

final class ProductSchema extends AbstractSchema
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
        $price = $this->data['price'] ?? null;
        $currency = $this->data['priceCurrency'] ?? null;
        $availability = $this->data['availability'] ?? null;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->data['name'] ?? null,
            'description' => $this->data['description'] ?? null,
            'image' => filled($this->data['image'] ?? null) ? (array) $this->data['image'] : null,
            'sku' => $this->data['sku'] ?? null,
            'brand' => filled($this->data['brand'] ?? null)
                ? [
                    '@type' => 'Brand',
                    'name' => $this->data['brand'],
                ]
                : null,
            'offers' => filled($price) && filled($currency)
                ? [
                    '@type' => 'Offer',
                    'price' => $price,
                    'priceCurrency' => $currency,
                    'availability' => filled($availability) ? $availability : null,
                    'url' => $this->data['url'] ?? null,
                ]
                : null,
        ];
    }
}
