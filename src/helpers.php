<?php

use JasanyaTech\SEO\SeoManager;

if (! function_exists('seo')) {
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}
