<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CorsTest extends TestCase
{
    #[DataProvider('productionFrontendOrigins')]
    public function test_production_frontend_can_call_authenticated_api_routes(string $origin): void
    {
        $headers = [
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization',
        ];

        $this->withHeaders($headers)
            ->options('/api/auth/me')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origin)
            ->assertHeader('Access-Control-Allow-Methods');

        $this->withHeader('Origin', $origin)
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertHeader('Access-Control-Allow-Origin', $origin);
    }

    public static function productionFrontendOrigins(): array
    {
        return [
            'canonical domain' => ['https://www.agonito.com'],
            'apex domain' => ['https://agonito.com'],
            'store domain' => ['https://agonito.store'],
            'www store domain' => ['https://www.agonito.store'],
        ];
    }
}
