<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Slider\SliderCollection;
use App\Models\Slider;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;

class SliderController extends Controller
{
    use ApiResponse;

    private const CACHE_KEY = 'api.sliders';

    /**
     * Active homepage hero slides, ordered for the storefront.
     */
    public function index()
    {
        $sliders = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return Slider::query()
                ->active()
                ->ordered()
                ->get();
        });

        return $this->success(
            new SliderCollection($sliders),
            null,
            'Sliders fetched successfully'
        );
    }
}
