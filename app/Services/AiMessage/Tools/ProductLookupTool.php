<?php

namespace App\Services\AiMessage\Tools;

use App\Services\AiMessage\Support\ProductLookupService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the AI agent fetch real product data instead of guessing.
 */
class ProductLookupTool implements Tool
{
    public function __construct(
        private readonly ProductLookupService $products = new ProductLookupService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Search the store catalogue by product name, keyword, brand, or SKU. Product names are stored '
            .'in Bangla script, so translate Banglish/English queries into Bangla before searching. Returns the '
            .'real, current price, image, stock status, and product page URL for each match. If more than 10 '
            .'products match, only the top 10 best-selling, in-stock ones are returned (not the full catalogue) — '
            .'if 10 or fewer match, every match is returned. Always call this before telling a customer a price, '
            .'stock level, or product detail — never guess.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) $request->string('query'));

        if ($query === '') {
            return json_encode([
                'results' => [],
                'message' => 'No search term was provided.',
            ]);
        }

        $results = $this->products->search($query);

        if ($results->isEmpty()) {
            return json_encode([
                'results' => [],
                'message' => "No product matched \"{$query}\". Tell the customer honestly that you couldn't ".
                    'find it and ask them to confirm the product name.',
            ], JSON_UNESCAPED_UNICODE);
        }

        return json_encode(['results' => $results->all()], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The product name, keyword, brand, or SKU the customer is asking about.')
                ->required(),
        ];
    }
}
