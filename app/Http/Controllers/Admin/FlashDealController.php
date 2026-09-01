<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Controller;
use App\Http\Requests\FlashDealRequest;
use App\Models\FlashDeal;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlashDealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $flashDeals = FlashDeal::orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.flash-deals.index', compact('flashDeals'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $products = Product::where('status', 'published')
            ->orderBy('name')
            ->get();

        return view('admin.flash-deals.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FlashDealRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = upload_webp_image(
                $request->file('banner_image'),
                'uploads/flash-deals',
                80
            );
        }

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Ensure slug is unique
        $originalSlug = $data['slug'];
        $counter = 1;
        while (FlashDeal::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        FlashDeal::create($data);
        HomeController::clearCache();

        flash_message('Flash deal created successfully!');

        return redirect()->route('flash-deals.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FlashDeal $flashDeal): View
    {
        $products = Product::where('status', 'published')
            ->orderBy('name')
            ->get();

        return view('admin.flash-deals.edit', compact('flashDeal', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FlashDealRequest $request, FlashDeal $flashDeal): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner_image')) {
            $newBannerImage = upload_webp_image(
                $request->file('banner_image'),
                'uploads/flash-deals',
                80
            );
            delete_uploaded_file($flashDeal->banner_image);
            $data['banner_image'] = $newBannerImage;
        }

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Ensure slug is unique (excluding current record)
        $originalSlug = $data['slug'];
        $counter = 1;
        while (FlashDeal::where('slug', $data['slug'])->where('id', '!=', $flashDeal->id)->exists()) {
            $data['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        $flashDeal->update($data);
        HomeController::clearCache();

        flash_message('Flash deal updated successfully!');

        return redirect()->route('flash-deals.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FlashDeal $flashDeal): RedirectResponse
    {
        delete_uploaded_file($flashDeal->banner_image);

        $flashDeal->delete();
        HomeController::clearCache();

        flash_message('Flash deal deleted successfully!');

        return redirect()->route('flash-deals.index');
    }

    /**
     * Search products for flash deal selection
     */
    public function searchProducts(Request $request)
    {
        $search = $request->input('search', '');
        $excludeIdsInput = $request->input('exclude_ids', []);

        // Handle exclude_ids - can be JSON string or array
        $excludeIds = [];
        if (! empty($excludeIdsInput)) {
            if (is_string($excludeIdsInput)) {
                $excludeIds = json_decode($excludeIdsInput, true) ?? [];
            } else {
                $excludeIds = is_array($excludeIdsInput) ? $excludeIdsInput : [$excludeIdsInput];
            }
        }

        $products = Product::where('status', 'published')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->when(! empty($excludeIds), function ($query) use ($excludeIds) {
                $query->whereNotIn('id', $excludeIds);
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'sku', 'price', 'thumbnail_image']);

        return response()->json([
            'success' => true,
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => number_format($product->price, 2),
                    'image' => $product->thumbnail_image ?: asset('build/images/products/img-1.png'),
                ];
            }),
        ]);
    }
}
