<?php

namespace App\Services\AiMessage\Tools;

use App\Services\AiMessage\Support\OrderLookupService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the AI agent fetch a real order's status and timeline instead of guessing.
 */
class OrderLookupTool implements Tool
{
    public function __construct(
        private readonly OrderLookupService $orders = new OrderLookupService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Look up a customer order by its order number (e.g. AGO-AB12CD) or numeric order ID, and '
            .'return its real status, payment info, and timeline/history from the database. Only call this '
            .'once the customer has actually given you an order number — never guess or invent one.';
    }

    public function handle(Request $request): Stringable|string
    {
        $orderId = trim((string) $request->string('order_id'));

        if ($orderId === '') {
            return json_encode([
                'found' => false,
                'message' => 'No order number was provided.',
            ]);
        }

        $order = $this->orders->find($orderId);

        if (! $order) {
            return json_encode([
                'found' => false,
                'message' => "No order was found for \"{$orderId}\". Tell the customer honestly and ask them ".
                    'to double check the order number.',
            ], JSON_UNESCAPED_UNICODE);
        }

        return json_encode(['found' => true, 'order' => $order], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->string()
                ->description('The order number (e.g. AGO-AB12CD) or numeric order ID the customer provided.')
                ->required(),
        ];
    }
}
