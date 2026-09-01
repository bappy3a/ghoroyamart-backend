<?php

declare(strict_types=1);

/**
 * Generate the Postman collection for every route loaded by routes/api.php.
 *
 * Run: php scripts/generate-postman-collection.php
 */

$jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

/** @param array<string, mixed> $options */
function requestItem(string $name, string $method, string $path, array $options = []): array
{
    $headers = $options['headers'] ?? [];
    $request = [
        'method' => $method,
        'header' => array_values($headers),
        'url' => ['raw' => '{{base_url}}'.$path, 'host' => ['{{base_url}}'], 'path' => array_values(array_filter(explode('/', $path))),],
        'description' => $options['description'] ?? '',
    ];

    if (! empty($options['auth'])) {
        $request['auth'] = [
            'type' => 'bearer',
            'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']],
        ];
    } else {
        $request['auth'] = ['type' => 'noauth'];
    }

    if (isset($options['query'])) {
        $query = [];
        $active = [];
        foreach ($options['query'] as $row) {
            [$key, $value, $description, $disabled] = array_pad($row, 4, false);
            $queryRow = ['key' => $key, 'value' => $value, 'description' => $description];
            if ($disabled) {
                $queryRow['disabled'] = true;
            } else {
                $active[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
            }
            $query[] = $queryRow;
        }
        $request['url']['query'] = $query;
        if ($active !== []) {
            $request['url']['raw'] .= '?'.implode('&', $active);
        }
    }

    if (array_key_exists('body', $options)) {
        $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'];
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($options['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'options' => ['raw' => ['language' => 'json']],
        ];
    }

    $item = ['name' => $name, 'request' => $request, 'response' => []];
    if (isset($options['test'])) {
        $item['event'] = [[
            'listen' => 'test',
            'script' => ['type' => 'text/javascript', 'exec' => explode("\n", trim($options['test']))],
        ]];
    }

    return $item;
}

/** @param array<int, array<string, mixed>> $items */
function folder(string $name, array $items, string $description = ''): array
{
    return ['name' => $name, 'description' => $description, 'item' => $items];
}

$addressBody = [
    'name' => 'Test Customer',
    'email' => 'customer@example.com',
    'phone' => '{{phone}}',
    'delivery_area_id' => '{{delivery_area_id}}',
    'address' => 'House 10, Road 5, Dhaka',
    'address_type' => 'home',
    'is_default' => true,
    'postal_code' => '1207',
];

$cartItems = [[
    'product_id' => '{{product_id}}',
    'product_variant_id' => '{{variant_id}}',
    'variant_name' => 'Default',
    'quantity' => 1,
]];

$items = [
    folder('00 - Setup', [
        requestItem('Get CSRF Cookie (required for Promo Checkout)', 'GET', '/sanctum/csrf-cookie', [
            'description' => 'Utility route (outside routes/api.php). Run once before promo checkout requests. Postman keeps the session and XSRF cookies automatically.',
        ]),
    ]),

    folder('01 - Authentication', [
        requestItem('Send OTP', 'POST', '/api/auth/send-otp', [
            'body' => ['phone' => '{{phone}}'],
            'description' => 'Public. Bangladesh mobile format: 01XXXXXXXXX. Throttled to 5 requests/minute.',
        ]),
        requestItem('Resend OTP', 'POST', '/api/auth/resend-otp', [
            'body' => ['phone' => '{{phone}}'],
            'description' => 'Public. Call after Send OTP and observe the resend cooldown.',
        ]),
        requestItem('Verify OTP', 'POST', '/api/auth/verify-otp', [
            'body' => ['phone' => '{{phone}}', 'otp' => '{{otp}}'],
            'description' => 'Public. Stores the returned Sanctum token in the collection token variable.',
            'test' => <<<'JS'
if (pm.response.code >= 200 && pm.response.code < 300) {
    const json = pm.response.json();
    const token = json?.data?.token ?? json?.token;
    if (token) pm.collectionVariables.set('token', token);
}
JS,
        ]),
        requestItem('Current User', 'GET', '/api/auth/me', ['auth' => true]),
        requestItem('Update Profile', 'PUT', '/api/auth/profile', [
            'auth' => true,
            'body' => [
                'name' => 'Test Customer',
                'email' => 'customer@example.com',
                'gender' => 'other',
                'date_of_birth' => '1995-01-01',
                'delivery_area_id' => '{{delivery_area_id}}',
                'address' => 'House 10, Road 5, Dhaka',
            ],
        ]),
        requestItem('Logout', 'POST', '/api/auth/logout', ['auth' => true]),
    ], 'OTP authentication. Run Send OTP, Verify OTP, then authenticated requests.'),

    folder('02 - Shipping Addresses', [
        requestItem('List Shipping Addresses', 'GET', '/api/auth/shipping-addresses', ['auth' => true]),
        requestItem('Create Shipping Address', 'POST', '/api/auth/shipping-addresses', [
            'auth' => true,
            'body' => $addressBody,
            'test' => <<<'JS'
if (pm.response.code >= 200 && pm.response.code < 300) {
    const json = pm.response.json();
    const id = json?.data?.id;
    if (id) pm.collectionVariables.set('shipping_address_id', id);
}
JS,
        ]),
        requestItem('Get Shipping Address', 'GET', '/api/auth/shipping-addresses/{{shipping_address_id}}', ['auth' => true]),
        requestItem('Update Shipping Address', 'PUT', '/api/auth/shipping-addresses/{{shipping_address_id}}', ['auth' => true, 'body' => $addressBody]),
        requestItem('Set Default Shipping Address', 'POST', '/api/auth/shipping-addresses/{{shipping_address_id}}/default', ['auth' => true]),
        requestItem('Delete Shipping Address', 'DELETE', '/api/auth/shipping-addresses/{{shipping_address_id}}', ['auth' => true]),
    ]),

    folder('03 - Products & Categories', [
        requestItem('List Products', 'GET', '/api/products', [
            'query' => [
                ['q', 'serum', 'Search name/description', true],
                ['category', 'skincare', 'Category id/slug/name', true],
                ['sub', '', 'Subcategory id/slug/name', true],
                ['vendor', '', 'Brand/vendor id/slug/name', true],
                ['sort', 'popular', 'popular, new, price-asc, price-desc, rating, discount', false],
                ['rating', '4', 'Minimum rating', true],
                ['min', '100', 'Minimum price', true],
                ['max', '5000', 'Maximum price', true],
                ['page', '1', 'Page number', false],
                ['perPage', '24', '12-48 items', false],
            ],
        ]),
        requestItem('Get Product (ID or Slug)', 'GET', '/api/products/{{product_id}}'),
        requestItem('List Categories', 'GET', '/api/categories', [
            'query' => [
                ['featured', '1', 'Only featured categories', true],
                ['popular', '1', 'Only popular categories', true],
                ['flat', '1', 'Return a flat list', true],
                ['parent_id', '{{category_id}}', 'Used with flat=1', true],
            ],
        ]),
        requestItem('Get Category (ID or Slug)', 'GET', '/api/categories/{{category_id}}'),
    ]),

    folder('04 - Storefront Content', [
        requestItem('Home Page', 'GET', '/api/home'),
        requestItem('Flash Sale Page', 'GET', '/api/home/flash-sale'),
        requestItem('Get Flash Sale (ID or Slug)', 'GET', '/api/home/flash-sale/{{flash_sale_id}}'),
        requestItem('Sliders', 'GET', '/api/sliders'),
        requestItem('Settings', 'GET', '/api/settings'),
        requestItem('Delivery Areas', 'GET', '/api/delivery-areas', [
            'query' => [['q', 'Dhaka', 'Search name, district, or tags', true]],
        ]),
        requestItem('List Blogs', 'GET', '/api/blogs', [
            'query' => [
                ['q', 'skin', 'Search title/description', true],
                ['category', 'beauty', 'Blog category id/slug/name', true],
                ['page', '1', 'Page number', false],
                ['perPage', '24', '6-48 items', false],
            ],
        ]),
        requestItem('Get Blog (Slug or ID)', 'GET', '/api/blogs/{{blog_slug}}'),
        requestItem('List Custom Pages', 'GET', '/api/custom-pages'),
        requestItem('Get Custom Page', 'GET', '/api/custom-pages/{{custom_page_slug}}'),
    ]),

    folder('05 - Checkout', [
        requestItem('Delivery Charges', 'GET', '/api/checkout/delivery-charges', ['auth' => true]),
        requestItem('Preview Checkout', 'POST', '/api/checkout/preview', [
            'auth' => true,
            'body' => ['shipping_address_id' => '{{shipping_address_id}}', 'coupon_code' => '{{coupon_code}}', 'items' => $cartItems],
        ]),
        requestItem('Apply Coupon', 'POST', '/api/checkout/coupon/apply', [
            'auth' => true,
            'body' => ['code' => '{{coupon_code}}', 'shipping_address_id' => '{{shipping_address_id}}', 'items' => $cartItems],
        ]),
        requestItem('Remove Coupon', 'POST', '/api/checkout/coupon/remove', [
            'auth' => true,
            'body' => ['shipping_address_id' => '{{shipping_address_id}}', 'items' => $cartItems],
        ]),
        requestItem('Place Authenticated Order', 'POST', '/api/checkout', [
            'auth' => true,
            'body' => [
                'shipping_address_id' => '{{shipping_address_id}}',
                'items' => $cartItems,
                'payment_method' => 'cash_on_delivery',
                'order_notes' => 'Postman test order',
                'coupon_code' => null,
            ],
            'test' => <<<'JS'
if (pm.response.code >= 200 && pm.response.code < 300) {
    const json = pm.response.json();
    const orderNumber = json?.data?.order_number;
    if (orderNumber) pm.collectionVariables.set('order_number', orderNumber);
}
JS,
        ]),
    ]),

    folder('06 - Orders', [
        requestItem('Track Order (Public)', 'POST', '/api/orders/track', [
            'body' => ['order_number' => '{{order_number}}', 'phone' => '{{phone}}'],
        ]),
        requestItem('List My Orders', 'GET', '/api/orders', [
            'auth' => true,
            'query' => [['per_page', '20', '1-50 items', false]],
        ]),
        requestItem('Get My Order', 'GET', '/api/orders/{{order_number}}', ['auth' => true]),
        requestItem('Get Order Invoice (HTML)', 'GET', '/api/orders/{{order_number}}/invoice', ['auth' => true]),
    ]),

    folder('07 - Wishlist', [
        requestItem('List Wishlist', 'GET', '/api/wishlist', ['auth' => true]),
        requestItem('Add to Wishlist', 'POST', '/api/wishlist', ['auth' => true, 'body' => ['product_id' => '{{product_id}}']]),
        requestItem('Toggle Wishlist', 'POST', '/api/wishlist/toggle', ['auth' => true, 'body' => ['product_id' => '{{product_id}}']]),
        requestItem('Remove from Wishlist', 'DELETE', '/api/wishlist/{{product_id}}', ['auth' => true]),
    ]),

    folder('08 - Promo Checkout', [
        requestItem('Get Promotion Page', 'GET', '/api/promo/{{promo_slug}}'),
        requestItem('Get Promo Product Variants', 'GET', '/api/promo/product/{{product_id}}/variants'),
        requestItem('Get Product by Variant', 'POST', '/api/promo/product/{{product_id}}/by-variant', [
            'body' => ['variant_id' => '{{variant_id}}'],
        ]),
        requestItem('Get Promo Delivery Areas', 'GET', '/api/promo/divisions/all'),
        requestItem('Send Promo Checkout OTP', 'POST', '/api/promo/checkout/send-otp', [
            'body' => ['phone' => '{{phone}}'],
            'description' => 'Session-based. Run Setup/Get CSRF Cookie first and keep Postman cookies enabled.',
        ]),
        requestItem('Verify Promo Checkout OTP', 'POST', '/api/promo/checkout/verify-otp', [
            'body' => ['phone' => '{{phone}}', 'otp_code' => '{{promo_otp}}'],
            'description' => 'Must use the same Postman cookie jar/session as Send Promo Checkout OTP.',
        ]),
        requestItem('Place Promo Guest Order', 'POST', '/api/promo/checkout/place-order', [
            'body' => [
                'product_id' => '{{product_id}}',
                'variant_id' => '{{variant_id}}',
                'quantity' => 1,
                'price' => 1000,
                'customer_name' => 'Promo Customer',
                'customer_email' => 'promo@example.com',
                'customer_phone' => '{{phone}}',
                'shipping_address' => 'House 10, Road 5, Dhaka',
                'shipping_delivery_area_id' => '{{delivery_area_id}}',
                'shipping_postal_code' => '1207',
                'shipping_address_type' => 'home',
                'payment_method' => 'cash_on_delivery',
                'shipping_method' => 'flat_rate',
                'shipping_cost' => 80,
                'delivery_area' => 'inside_dhaka',
                'tax' => 0,
                'discount' => 0,
                'order_notes' => 'Promo Postman test order',
            ],
            'description' => 'Requires a verified promo OTP in the same session.',
        ]),
    ]),

    folder('09 - AI', [
        requestItem('Image Search Instructions', 'GET', '/api/ai/image-search'),
        requestItem('Analyze Product Image', 'POST', '/api/ai/image-search', [
            'body' => ['image' => 'data:image/jpeg;base64,PASTE_BASE64_IMAGE_HERE'],
        ]),
        requestItem('Transcription Instructions', 'GET', '/api/ai/transcribe'),
        requestItem('Transcribe Audio', 'POST', '/api/ai/transcribe', [
            'body' => ['audio' => 'data:audio/webm;base64,PASTE_BASE64_AUDIO_HERE', 'mime' => 'audio/webm', 'lang' => 'en'],
        ]),
        requestItem('Load AI Conversation', 'GET', '/api/ai/chat', [
            'query' => [
                ['channel', 'website', 'facebook, instagram, whatsapp, telegram, website', false],
                ['external_sender_id', '{{external_sender_id}}', 'Conversation identity', false],
            ],
        ]),
        requestItem('Send AI Conversation Message', 'POST', '/api/ai/chat', [
            'body' => [
                'channel' => 'website',
                'external_sender_id' => '{{external_sender_id}}',
                'messages' => ['role' => 'user', 'message' => 'What products do you recommend?'],
            ],
        ]),
    ]),

    folder('10 - Contact & Chat', [
        requestItem('Send Contact Message', 'POST', '/api/contact', [
            'body' => [
                'name' => 'Test Customer',
                'email' => 'customer@example.com',
                'phone' => '{{phone}}',
                'subject' => 'Product question',
                'message' => 'Please tell me more about this product.',
            ],
        ]),
        requestItem('Send Simple Chat Message', 'POST', '/api/chat', [
            'body' => [
                'message' => 'আমার অর্ডারটি কোথায়?',
                'history' => [['role' => 'user', 'message' => 'Hello']],
            ],
        ]),
    ]),

    folder('11 - Webhooks', [
        requestItem('Steadfast Webhook', 'POST', '/api/steadfast/webhook', [
            'headers' => [['key' => 'X-Steadfast-Webhook-Secret', 'value' => '{{steadfast_webhook_secret}}', 'type' => 'text']],
            'body' => [
                'notification_type' => 'delivery_status',
                'consignment_id' => 123456,
                'invoice' => '{{order_number}}',
                'tracking_message' => 'Parcel delivered',
                'updated_at' => '2026-08-29 12:00:00',
                'cod_amount' => 1000,
                'status' => 'delivered',
                'delivery_charge' => 80,
            ],
            'description' => 'Status: pending, delivered, partial_delivered, cancelled, or unknown. Secret header is only required when configured server-side.',
        ]),
    ]),
];

$collection = [
    'info' => [
        '_postman_id' => '7e6813ae-ff14-4d96-a042-a60b170a2026',
        'name' => 'Agonito API - Complete',
        'description' => "Complete collection generated from routes/api.php and all included API route files. Contains 55 requests covering every allowed API method, plus one CSRF setup utility request.\n\nQuick start: set base_url, run Authentication/Send OTP and Verify OTP, then use protected requests. For Promo Checkout, run Setup/Get CSRF Cookie first and preserve cookies.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'auth' => ['type' => 'noauth'],
    'event' => [[
        'listen' => 'prerequest',
        'script' => [
            'type' => 'text/javascript',
            'exec' => [
                "pm.request.headers.upsert({ key: 'Accept', value: 'application/json' });",
                "const xsrf = pm.cookies.get('XSRF-TOKEN');",
                "if (xsrf) pm.request.headers.upsert({ key: 'X-XSRF-TOKEN', value: decodeURIComponent(xsrf) });",
            ],
        ],
    ]],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://agonito.test', 'type' => 'string'],
        ['key' => 'token', 'value' => '', 'type' => 'string'],
        ['key' => 'phone', 'value' => '01700000000', 'type' => 'string'],
        ['key' => 'otp', 'value' => '000000', 'type' => 'string'],
        ['key' => 'promo_otp', 'value' => '000000', 'type' => 'string'],
        ['key' => 'product_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'variant_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'category_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'delivery_area_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'shipping_address_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'order_number', 'value' => 'AG-EXAMPLE', 'type' => 'string'],
        ['key' => 'coupon_code', 'value' => '', 'type' => 'string'],
        ['key' => 'blog_slug', 'value' => 'example-blog', 'type' => 'string'],
        ['key' => 'custom_page_slug', 'value' => 'about-us', 'type' => 'string'],
        ['key' => 'flash_sale_id', 'value' => '1', 'type' => 'string'],
        ['key' => 'promo_slug', 'value' => 'example-promo', 'type' => 'string'],
        ['key' => 'external_sender_id', 'value' => 'postman-test-user', 'type' => 'string'],
        ['key' => 'steadfast_webhook_secret', 'value' => '', 'type' => 'string'],
    ],
    'item' => $items,
];

$target = dirname(__DIR__).'/postman/Agonito_API.postman_collection.json';
$encoded = json_encode($collection, $jsonFlags | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents($target, $encoded);

echo "Generated {$target}\n";
