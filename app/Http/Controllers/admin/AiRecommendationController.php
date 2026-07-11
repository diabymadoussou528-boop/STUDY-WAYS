<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiRecommendationService;
use Illuminate\View\View;

class AiRecommendationController extends Controller
{
    public function index(AiRecommendationService $service): View
    {
        $recommendations = $service->recommendations();
        $charts = $service->dashboardCharts();

        return view('admin.ai.index', compact('recommendations', 'charts'));
    }
}
