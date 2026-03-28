<?php

namespace JasanyaTech\SEO\Support;

final class DataResolver
{
    public static function first(mixed $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public static function string(mixed $payload, array $keys): ?string
    {
        $value = self::first($payload, $keys);

        if (! is_scalar($value)) {
            return null;
        }

        $resolved = trim((string) $value);

        return $resolved !== '' ? $resolved : null;
    }
}
