<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionLandingPageRequest;
use App\Models\Product;
use App\Models\PromotionLandingPage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PromotionLandingPageController extends Controller
{
    public function index(): View
    {
        $landingPages = PromotionLandingPage::with('products')
            ->latest()
            ->paginate(15);

        return view('admin.promotion-landing-pages.index', compact('landingPages'));
    }

    public function create(): View
    {
        return view('admin.promotion-landing-pages.create', [
            'landingPage' => new PromotionLandingPage(),
            'products' => $this->publishedProducts(),
        ]);
    }

    public function store(PromotionLandingPageRequest $request): RedirectResponse
    {
        $landingPage = PromotionLandingPage::create($this->payloadFrom($request));
        $landingPage->products()->sync($request->input('product_ids', []));
        $this->syncGalleryImages($request, $landingPage);

        flash_message('Promotion landing page created successfully!');

        return redirect()->route('promotion-landing-pages.index');
    }

    public function edit(PromotionLandingPage $promotion_landing_page): View
    {
        $promotion_landing_page->load(['products', 'adminGalleryImages']);

        return view('admin.promotion-landing-pages.edit', [
            'landingPage' => $promotion_landing_page,
            'products' => $this->publishedProducts(),
        ]);
    }

    public function update(PromotionLandingPageRequest $request, PromotionLandingPage $promotion_landing_page): RedirectResponse
    {
        $promotion_landing_page->update($this->payloadFrom($request));
        $promotion_landing_page->products()->sync($request->input('product_ids', []));
        $this->syncGalleryImages($request, $promotion_landing_page);

        flash_message('Promotion landing page updated successfully!');

        return redirect()->route('promotion-landing-pages.index');
    }

    public function destroy(PromotionLandingPage $promotion_landing_page): RedirectResponse
    {
        $promotion_landing_page->load('adminGalleryImages');

        foreach ($promotion_landing_page->adminGalleryImages as $galleryImage) {
            $this->deleteGalleryFile($galleryImage->image_path);
        }

        $promotion_landing_page->delete();

        flash_message('Promotion landing page deleted successfully!');

        return redirect()->route('promotion-landing-pages.index');
    }

    protected function payloadFrom(PromotionLandingPageRequest $request): array
    {
        $data = $request->validated();
        unset(
            $data['product_ids'],
            $data['gallery_images'],
            $data['gallery_alt_text'],
            $data['existing_gallery_ids'],
            $data['existing_gallery_alt_text'],
            $data['delete_gallery_ids']
        );

        $data['slug'] = Str::slug((string) $data['slug']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function publishedProducts()
    {
        return Product::where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'thumbnail_image', 'price']);
    }

    protected function syncGalleryImages(PromotionLandingPageRequest $request, PromotionLandingPage $landingPage): void
    {
        $sortOrder = 0;
        $deleteIds = collect($request->input('delete_gallery_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($deleteIds->isNotEmpty()) {
            $landingPage->adminGalleryImages()
                ->whereIn('id', $deleteIds)
                ->get()
                ->each(function ($galleryImage) {
                    $this->deleteGalleryFile($galleryImage->image_path);
                    $galleryImage->delete();
                });
        }

        $existingIds = $request->input('existing_gallery_ids', []);
        $existingAltText = $request->input('existing_gallery_alt_text', []);

        foreach ($existingIds as $index => $id) {
            if ($deleteIds->contains((int) $id)) {
                continue;
            }

            $landingPage->adminGalleryImages()
                ->whereKey($id)
                ->update([
                    'alt_text' => $existingAltText[$index] ?? null,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]);
        }

        if ($request->hasFile('gallery_images')) {
            $newAltText = $request->input('gallery_alt_text', []);

            foreach ($request->file('gallery_images') as $index => $image) {
                if (! $image) {
                    continue;
                }

                $landingPage->adminGalleryImages()->create([
                    'image_path' => upload_webp_image($image, 'uploads/promotion-gallery', 80),
                    'alt_text' => $newAltText[$index] ?? null,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function deleteGalleryFile(?string $imagePath): void
    {
        delete_uploaded_file($imagePath);
    }
}
