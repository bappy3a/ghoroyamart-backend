<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Controller;
use App\Http\Requests\SliderRequest;
use App\Models\Slider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class SliderController extends Controller
{
    private const SLIDERS_CACHE_KEY = 'api.sliders';

    public function index(): View
    {
        $sliders = Slider::orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('admin.sliders.index', compact('sliders'));
    }

    public function create(): View
    {
        return view('admin.sliders.create', [
            'slider' => new Slider(),
        ]);
    }

    public function store(SliderRequest $request): RedirectResponse
    {
        Slider::create($this->payloadFrom($request));

        Cache::forget(self::SLIDERS_CACHE_KEY);
        HomeController::clearCache();
        flash_message('Slider created successfully!');

        return redirect()->route('sliders.index');
    }

    public function edit(Slider $slider): View
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    public function update(SliderRequest $request, Slider $slider): RedirectResponse
    {
        $slider->update($this->payloadFrom($request, $slider));

        Cache::forget(self::SLIDERS_CACHE_KEY);
        HomeController::clearCache();
        flash_message('Slider updated successfully!');

        return redirect()->route('sliders.index');
    }

    public function destroy(Slider $slider): RedirectResponse
    {
        $slider->delete();

        Cache::forget(self::SLIDERS_CACHE_KEY);
        HomeController::clearCache();
        flash_message('Slider deleted successfully!');

        return redirect()->route('sliders.index');
    }

    protected function payloadFrom(SliderRequest $request, ?Slider $slider = null): array
    {
        $data = $request->validated();

        // Keep legacy `text` in sync with title for older consumers.
        $data['text'] = $data['title'] ?? null;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status'] = ($data['is_active'] ?? true) ? 'published' : 'draft';

        if ($request->hasFile('image')) {
            $data['image'] = upload_webp_image($request->file('image'), 'uploads/sliders', 80);
        } else {
            unset($data['image']);
        }

        if ($slider === null) {
            $data['published_at'] = now();
            $data['created_by_id'] = $request->user()?->id;
        }

        $data['updated_by_id'] = $request->user()?->id;

        return $data;
    }
}
