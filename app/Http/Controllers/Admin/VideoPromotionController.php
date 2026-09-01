<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoPromotionRequest;
use App\Models\VideoPromotion;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VideoPromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $videoPromotions = VideoPromotion::with('product')
            ->orderBy('order_number')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.video-promotions.index', compact('videoPromotions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $products = Product::where('status', 'published')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.video-promotions.create', [
            'videoPromotion' => new VideoPromotion(),
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VideoPromotionRequest $request): RedirectResponse
    {
        VideoPromotion::create($this->payloadFrom($request));

        flash_message('Video promotion created successfully!');

        return redirect()->route('video-promotions.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VideoPromotion $videoPromotion): View
    {
        $products = Product::where('status', 'published')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.video-promotions.edit', [
            'videoPromotion' => $videoPromotion,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VideoPromotionRequest $request, VideoPromotion $videoPromotion): RedirectResponse
    {
        $videoPromotion->update($this->payloadFrom($request));

        flash_message('Video promotion updated successfully!');

        return redirect()->route('video-promotions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VideoPromotion $videoPromotion): RedirectResponse
    {
        $videoPromotion->delete();

        flash_message('Video promotion deleted successfully!');

        return redirect()->route('video-promotions.index');
    }

    protected function payloadFrom(VideoPromotionRequest $request): array
    {
        $data = $request->validated();

        $data['is_active'] = $request->boolean('is_active', true);
        $data['order_number'] = $request->input('order_number', 0);
        $data['product_id'] = $request->input('product_id') ?: null;

        return $data;
    }
}
