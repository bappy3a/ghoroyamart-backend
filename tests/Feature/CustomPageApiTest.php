<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['url']->forceRootUrl('https://shop.example.com');
        $this->app['url']->forceScheme('https');
    }

    public function test_custom_pages_are_publicly_listed_for_storefront_navigation(): void
    {
        CustomPage::create([
            'name' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'en_content' => '<p>Terms content</p>',
        ]);
        CustomPage::create([
            'name' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'en_content' => '<p>Privacy content</p>',
        ]);

        $this->getJson('/api/custom-pages')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Custom pages fetched successfully')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Privacy Policy')
            ->assertJsonPath('data.0.url', 'https://shop.example.com/pages/privacy-policy')
            ->assertJsonPath('data.1.name', 'Terms of Service')
            ->assertJsonMissingPath('data.0.en_content')
            ->assertJsonMissingPath('data.0.bn_content');
    }

    public function test_a_custom_page_is_publicly_available_by_slug(): void
    {
        $page = CustomPage::create([
            'name' => 'Delivery Policy',
            'slug' => 'delivery-policy',
            'sub_title' => 'Everything you need to know about delivery.',
            'en_content' => '<h2>Delivery times</h2><p>Orders arrive within three days.</p>',
            'bn_content' => '<h2>ডেলিভারির সময়</h2><p>অর্ডার তিন দিনের মধ্যে পৌঁছায়।</p>',
        ]);

        $this->getJson('/api/custom-pages/delivery-policy')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Custom page fetched successfully')
            ->assertJsonPath('data.id', $page->id)
            ->assertJsonPath('data.name', 'Delivery Policy')
            ->assertJsonPath('data.slug', 'delivery-policy')
            ->assertJsonPath('data.sub_title', 'Everything you need to know about delivery.')
            ->assertJsonPath('data.url', 'https://shop.example.com/pages/delivery-policy')
            ->assertJsonPath('data.en_content', '<h2>Delivery times</h2><p>Orders arrive within three days.</p>')
            ->assertJsonPath('data.bn_content', '<h2>ডেলিভারির সময়</h2><p>অর্ডার তিন দিনের মধ্যে পৌঁছায়।</p>')
            ->assertJsonStructure([
                'data' => ['created_at', 'updated_at'],
            ]);
    }

    public function test_an_unknown_custom_page_returns_a_json_404(): void
    {
        $this->getJson('/api/custom-pages/missing-page')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Custom page not found.',
                'data' => null,
                'metadata' => null,
            ]);
    }
}
