<?php

namespace JasanyaTech\SEO\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use JasanyaTech\SEO\Robots\RobotsRenderer;

class RobotsController extends Controller
{
    public function __invoke(RobotsRenderer $renderer): Response
    {
        return response($renderer->render(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
