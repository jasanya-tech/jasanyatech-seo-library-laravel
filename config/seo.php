<?php

return [
    'site' => [
        'name' => env('SEO_SITE_NAME', config('app.name')),
        'url' => env('SEO_SITE_URL', env('APP_URL')),
        'title_separator' => '|',
        'default_title' => null,
        'default_description' => null,
        'default_locale' => 'id_ID',
        'alternate_locales' => [],
    ],

    'defaults' => [
        'robots' => 'index,follow',
        'image' => null,
        'image_alt' => null,
        'twitter_card' => 'summary_large_image',
        'description_limit' => 160,
        'canonical_ignore_query' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'],
    ],

    'organization' => [
        'name' => null,
        'url' => null,
        'logo' => null,
        'same_as' => [],
        'email' => null,
        'telephone' => null,
    ],

    'website' => [
        'enable_search_action' => false,
        'search_url' => null,
    ],

    'social' => [
        'twitter_site' => null,
    ],

    'routes' => [
        'sitemap' => true,
        'robots' => true,
        'sitemap_path' => 'sitemap.xml',
        'robots_path' => 'robots.txt',
        'sitemaps_prefix' => 'sitemaps',
    ],

    'sitemap' => [
        'cache' => true,
        'cache_ttl' => 3600,
        'chunk_size' => 1000,
        'use_index' => true,
        'include_home' => true,
        'default_changefreq' => 'weekly',
        'default_priority' => 0.8,
    ],

    'robots' => [
        'disallow_non_production' => true,
        'rules' => [
            [
                'user_agent' => '*',
                'allow' => ['/'],
                'disallow' => [],
            ],
        ],
        'additional' => [],
    ],

    'environment' => [
        'noindex_on_local' => true,
        'noindex_on_staging' => true,
    ],

    'debug' => [
        'log_warnings' => env('SEO_LOG_WARNINGS', false),
    ],
];
