<?php

namespace JasanyaTech\SEO\Robots;

class RobotsRenderer
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private array $config) {}

    public function render(): string
    {
        $lines = [];
        $rules = $this->shouldDisallowAll()
            ? [['user_agent' => '*', 'allow' => [], 'disallow' => ['/']]]
            : (array) data_get($this->config, 'robots.rules', []);

        foreach ($rules as $rule) {
            $userAgent = trim((string) ($rule['user_agent'] ?? '*'));

            if ($userAgent === '') {
                continue;
            }

            $lines[] = 'User-agent: '.$userAgent;

            foreach ((array) ($rule['allow'] ?? []) as $allow) {
                $allow = trim((string) $allow);

                if ($allow !== '') {
                    $lines[] = 'Allow: '.$allow;
                }
            }

            foreach ((array) ($rule['disallow'] ?? []) as $disallow) {
                $disallow = trim((string) $disallow);

                if ($disallow !== '') {
                    $lines[] = 'Disallow: '.$disallow;
                }
            }

            $lines[] = '';
        }

        foreach ((array) data_get($this->config, 'robots.additional', []) as $line) {
            $line = trim((string) $line);

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        $sitemap = $this->resolveSitemapUrl();

        if ($sitemap) {
            $lines[] = 'Sitemap: '.$sitemap;
        }

        return rtrim(implode(PHP_EOL, $lines)).PHP_EOL;
    }

    private function resolveSitemapUrl(): ?string
    {
        $siteUrl = rtrim((string) (data_get($this->config, 'site.url') ?: config('app.url')), '/');
        $path = trim((string) data_get($this->config, 'routes.sitemap_path', 'sitemap.xml'), '/');

        return $siteUrl !== '' ? $siteUrl.'/'.$path : null;
    }

    private function shouldDisallowAll(): bool
    {
        return ! app()->isProduction()
            && (bool) data_get($this->config, 'robots.disallow_non_production', true);
    }
}
