<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(SeoService $seo): Response
    {
        $entries = $seo->sitemapEntries();
        $xml = view('seo.sitemap', compact('entries'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
