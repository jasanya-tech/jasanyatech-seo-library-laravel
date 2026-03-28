<?php

namespace JasanyaTech\SEO\Support;

final class ArrayCleaner
{
    /**
     * @param  array<string|int, mixed>  $payload
     * @return array<string|int, mixed>
     */
    public static function clean(array $payload): array
    {
        $cleaned = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);
            }

            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            if (is_array($value) && $value === []) {
                continue;
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }
}
