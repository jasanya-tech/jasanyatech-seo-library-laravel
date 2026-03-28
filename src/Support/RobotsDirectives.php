<?php

namespace JasanyaTech\SEO\Support;

final class RobotsDirectives
{
    public static function normalize(string|array|null $value, string $default = 'index,follow', bool $forceNoindex = false): string
    {
        $tokens = is_array($value) ? $value : explode(',', (string) ($value ?: $default));
        $directives = [];

        foreach ($tokens as $token) {
            $directive = strtolower(trim((string) $token));

            if ($directive === '') {
                continue;
            }

            $directives[] = $directive;
        }

        if ($forceNoindex) {
            $directives[] = 'noindex';
            $directives[] = 'nofollow';
        }

        $resolved = [];

        self::resolvePair($resolved, $directives, 'index', 'noindex', true);
        self::resolvePair($resolved, $directives, 'follow', 'nofollow', true);
        self::resolvePair($resolved, $directives, 'archive', 'noarchive');
        self::resolvePair($resolved, $directives, 'snippet', 'nosnippet');

        foreach ($directives as $directive) {
            if (str_starts_with($directive, 'max-snippet:') || str_starts_with($directive, 'max-image-preview:') || str_starts_with($directive, 'max-video-preview:')) {
                [$key] = explode(':', $directive, 2);
                $resolved[$key] = $directive;
            }
        }

        return implode(',', array_values($resolved ?: ['index', 'follow']));
    }

    /**
     * @param  array<string, string>  $resolved
     * @param  array<int, string>  $directives
     */
    private static function resolvePair(array &$resolved, array $directives, string $positive, string $negative, bool $preferNegative = false): void
    {
        $hasPositive = in_array($positive, $directives, true);
        $hasNegative = in_array($negative, $directives, true);

        if (! $hasPositive && ! $hasNegative) {
            return;
        }

        $resolved[$positive] = ($preferNegative && $hasNegative) || (! $preferNegative && ! $hasPositive && $hasNegative)
            ? $negative
            : $positive;
    }
}
