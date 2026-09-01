<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPagePublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_custom_page_uses_the_public_branded_layout(): void
    {
        Setting::create([
            'key' => 'site_name',
            'value' => 'Agonito',
        ]);

        $page = CustomPage::create([
            'name' => 'Delivery Policy',
            'slug' => 'delivery-policy',
            'sub_title' => 'Everything you need to know about delivery.',
            'en_content' => '<h2>Delivery times</h2><p>Orders arrive within three days.</p>',
            'bn_content' => '<h2>ডেলিভারির সময়</h2><p>অর্ডার তিন দিনের মধ্যে পৌঁছায়।</p>',
        ]);

        $this->get('/pages/delivery-policy')
            ->assertOk()
            ->assertViewIs('public.custom-pages.show')
            ->assertViewHas('page', fn (CustomPage $viewPage) => $viewPage->is($page))
            ->assertSee('<title>Delivery Policy | Agonito</title>', false)
            ->assertSee(asset('logo.png'), false)
            ->assertSee('Everything you need to know about delivery.')
            ->assertSee('<h2>Delivery times</h2>', false)
            ->assertSee('<h2>ডেলিভারির সময়</h2>', false)
            ->assertSee('data-language-button="en"', false)
            ->assertSee('data-language-button="bn"', false)
            ->assertSee('--brand: #146a67', false);
    }

    public function test_a_single_language_page_does_not_show_the_language_switcher(): void
    {
        CustomPage::create([
            'name' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'en_content' => '<p>Your privacy matters.</p>',
        ]);

        $this->get('/pages/privacy-policy')
            ->assertOk()
            ->assertSee('Your privacy matters.')
            ->assertDontSee('data-language-button=', false);
    }

    public function test_an_unknown_public_custom_page_returns_404(): void
    {
        $this->get('/pages/missing-page')->assertNotFound();
    }

    public function test_public_url_points_to_the_rendered_page(): void
    {
        $this->app['url']->forceRootUrl('https://shop.example.com');
        $this->app['url']->forceScheme('https');

        $page = CustomPage::create([
            'name' => 'Terms of Service',
            'slug' => 'terms-of-service',
        ]);

        $this->assertSame(
            'https://shop.example.com/pages/terms-of-service',
            $page->publicUrl()
        );
    }
}
