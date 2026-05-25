<?php

namespace App\Http\Controllers\Api\V1;

use App\Helper\V1\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(Request $request): JsonResponse
    {
        $stats = $this->dashboardService->getStats();
        $topTags = $this->dashboardService->getMostUsedTags();

        return ApiResponse::success(new DashboardResource([
            'stats' => $stats,
            'top_tags' => $topTags,
        ]));
    }
}
