<?php

namespace App\Http\Controllers\Api\V1;

use App\Helper\V1\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tags\StoreTagRequest;
use App\Http\Requests\V1\Tags\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::withCount('articles')->get();

        return ApiResponse::success('Tags retrieved successfully', $tags);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::create($request->validated());

        return ApiResponse::success('Tag created successfully', $tag);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return ApiResponse::success('Tag deleted successfully');
    }
}
