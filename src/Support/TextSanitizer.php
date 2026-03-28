<?php

namespace JasanyaTech\SEO\Support;

use Illuminate\Support\Str;

final class TextSanitizer
{
    public function plainText(?string $value, int $limit = 160): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');

        if ($sanitized === '') {
            return null;
        }

        return Str::limit($sanitized, $limit, '');
    }

    public function safeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = trim(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $sanitized !== '' ? $sanitized : null;
    }
}
