<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHighlightsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_settings_saves_and_clears_highlights(): void
    {
        $this->withoutMiddleware();

        $this->put(route('home-page-settings.update'), [
            'home_promise_items_submitted' => '1',
            'home_promise_items' => [
                ['icon' => 'sparkles', 'title' => ' Fast delivery ', 'subtitle' => 'Every day'],
            ],
            'home_top_bar_items_submitted' => '1',
            'home_top_bar_items' => [
                ['icon' => 'sparkles', 'title' => 'Welcome'],
            ],
        ])->assertRedirect(route('home-page-settings.index'));

        $this->assertSame('Fast delivery', json_decode(Setting::get('home_promise_items'), true)[0]['title']);
        $this->assertSame('Welcome', json_decode(Setting::get('home_top_bar_items'), true)[0]['title']);

        $this->put(route('home-page-settings.update'), [
            'home_promise_items_submitted' => '1',
        ])->assertRedirect(route('home-page-settings.index'));

        $this->assertSame('[]', Setting::get('home_promise_items'));
        $this->assertSame('Welcome', json_decode(Setting::get('home_top_bar_items'), true)[0]['title']);
    }

    public function test_home_page_settings_rejects_invalid_highlights(): void
    {
        $this->withoutMiddleware()->put(route('home-page-settings.update'), [
            'home_promise_items_submitted' => '1',
            'home_promise_items' => [['title' => str_repeat('x', 81)]],
        ])->assertSessionHasErrors('home_promise_items.0.title');

        $this->assertDatabaseMissing('settings', ['key' => 'home_promise_items']);
    }
}
