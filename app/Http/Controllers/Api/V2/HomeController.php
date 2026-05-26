<?php

namespace App\Http\Controllers\Api\V2;

use App\Helper\V1\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V2\HomeResource;
use App\Services\HomeArticleFeedService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected HomeArticleFeedService $homeArticleFeedService,
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $page = (int) $request->query('page', 1);
        $articles = $this->homeArticleFeedService->getV2Articles($perPage, $page);

        return ApiResponse::paginated(HomeResource::collection($articles), 'Articles retrieved successfully.');
    }
}
