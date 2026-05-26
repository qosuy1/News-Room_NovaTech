<?php
namespace App\Http\Controllers\Api\V1;

use App\Helper\V1\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\HomeResource;
use App\Interfaces\ArticleRepositoryInterface;

class HomeController extends Controller
{

    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }
    public function index()
    {
        $articles = $this->articleRepository->getAllPublishedWithRelations(15, ['user']);

        return ApiResponse::paginated(HomeResource::collection($articles), 'Articles retrieved successfully.');
    }
}
