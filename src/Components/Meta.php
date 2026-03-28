<?php

namespace JasanyaTech\SEO\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use JasanyaTech\SEO\DTOs\SeoData;

class Meta extends Component
{
    public function __construct(
        public ?SeoData $seo = null,
    ) {}

    public function render(): View
    {
        return view('seo::components.meta');
    }
}
