<?php

namespace JasanyaTech\SEO\Schema;

use JasanyaTech\SEO\Contracts\SchemaContract;
use JasanyaTech\SEO\Support\ArrayCleaner;

abstract class AbstractSchema implements SchemaContract
{
    /**
     * @return array<string, mixed>
     */
    abstract protected function payload(): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ArrayCleaner::clean($this->payload());
    }
}
