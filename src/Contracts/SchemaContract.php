<?php

namespace JasanyaTech\SEO\Contracts;

interface SchemaContract
{
    public function isValid(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
