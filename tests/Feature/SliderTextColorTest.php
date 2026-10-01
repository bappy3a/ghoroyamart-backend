<?php

namespace Tests\Feature;

use App\Models\Slider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SliderTextColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_slider_api_includes_the_configured_text_color(): void
    {
        Cache::flush();

        Slider::query()->create([
            'title' => 'A colorful slide',
            'image' => 'uploads/sliders/colorful.webp',
            'text_color' => '#12Ab34',
            'is_active' => true,
        ]);

        $this->getJson(route('api.sliders.index'))
            ->assertOk()
            ->assertJsonPath('data.0.text_color', '#12Ab34');
    }
}
