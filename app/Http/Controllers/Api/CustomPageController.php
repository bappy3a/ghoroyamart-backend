<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CustomPageController extends Controller
{
    use ApiResponse;

    /**
     * Return all custom pages available in the storefront.
     */
    public function index(): JsonResponse
    {
        $pages = CustomPage::query()
            ->orderBy('name')
            ->get()
            ->map(fn (CustomPage $page) => $this->transform($page))
            ->values()
            ->all();

        return $this->success($pages, null, 'Custom pages fetched successfully');
    }

    /**
     * Return a custom page by its public slug.
     */
    public function show(string $slug): JsonResponse
    {
        $page = CustomPage::query()
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            return $this->error('Custom page not found.', null, null, 404);
        }

        return $this->success(
            $this->transform($page, true),
            null,
            'Custom page fetched successfully'
        );
    }

    /**
     * Shape a custom page for public API consumers.
     */
    protected function transform(CustomPage $page, bool $withContent = false): array
    {
        $payload = [
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'sub_title' => $page->sub_title,
            'url' => $page->publicUrl(),
            'created_at' => $page->created_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];

        if ($withContent) {
            $payload['en_content'] = rewrite_api_assets_in_html($page->en_content);
            $payload['bn_content'] = rewrite_api_assets_in_html($page->bn_content);
        }

        return $payload;
    }
}
