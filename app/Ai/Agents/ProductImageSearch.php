<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ProductImageSearch implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  list<array{name: string, slug: string}>  $categories
     */
    public function __construct(public array $categories = []) {}

    public function instructions(): Stringable|string
    {
        $categoryHint = collect($this->categories)
            ->map(fn (array $c) => "{$c['name']} ({$c['slug']})")
            ->take(40)
            ->implode(', ');

        if ($categoryHint === '') {
            $categoryHint = 'electronics, fashion, home, beauty, sports, grocery';
        }

        return <<<PROMPT
You identify shopping products in photos for Agonito, an online store in Bangladesh.
Reply only via the structured schema.
- query: a short 2-5 word storefront search term shoppers would type
- category: one allowed category slug, or empty string if unsure
- label: a short human-readable description of the product

Allowed category slugs (prefer slug, not display name): {$categoryHint}
Prefer a specific product name over a vague category word.
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required(),
            'category' => $schema->string()->required(),
            'label' => $schema->string()->required(),
        ];
    }
}
