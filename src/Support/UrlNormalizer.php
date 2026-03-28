<?php

namespace JasanyaTech\SEO\Support;

final class UrlNormalizer
{
    /**
     * @param  array<int, string>  $ignoreQuery
     */
    public function normalize(?string $url, array $ignoreQuery = []): ?string
    {
        if ($url === null) {
            return null;
        }

        $candidate = trim($url);

        if ($candidate === '') {
            return null;
        }

        if (! str_contains($candidate, '://')) {
            $candidate = str_starts_with($candidate, '/')
                ? url($candidate)
                : url('/'.ltrim($candidate, '/'));
        }

        if (! filter_var($candidate, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($candidate);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            foreach ($ignoreQuery as $key) {
                unset($query[$key]);
            }

            if (($query['page'] ?? null) === '1' || ($query['page'] ?? null) === 1) {
                unset($query['page']);
            }

            ksort($query);
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $normalized = sprintf('%s://%s%s%s', strtolower($parts['scheme']), strtolower($parts['host']), $port, $path);

        if ($query !== []) {
            $normalized .= '?'.http_build_query($query);
        }

        return rtrim($normalized, '/') === strtolower($parts['scheme']).'://'.strtolower($parts['host']).$port
            ? strtolower($parts['scheme']).'://'.strtolower($parts['host']).$port.'/'
            : $normalized;
    }
}
