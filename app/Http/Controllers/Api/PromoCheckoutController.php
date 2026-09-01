<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromotionLandingPage;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Services\OrderStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PromoCheckoutController extends Controller
{
    public function __construct(private readonly OrderStockService $orderStockService) {}

    private const PROMO_CHECKOUT_OTP_SESSION_KEY = 'promo_checkout_otp';

    /**
     * Get promotion landing page with product and variants
     */
    public function getPromoPage(string $slug)
    {
        $landingPage = PromotionLandingPage::with('galleryImages')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $product = $landingPage->products()
            ->with([
                'category',
                'brand',
                'variants' => function ($query) {
                    $query->where('is_active', true)
                        ->with(['values.attribute', 'values.value']);
                },
            ])
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'No product found for this promotion.',
            ], 404);
        }

        $variants = $product->variants
            ->map(fn (ProductVariant $variant) => $this->variantForApi($variant))
            ->values()
            ->all();
        $galleryImages = $landingPage->galleryImages
            ->map(function ($image) {
                $payload = $image->toArray();
                $payload['image_path'] = $image->image_path ? api_asset($image->image_path) : null;

                return $payload;
            })
            ->values()
            ->all();
        $productPayload = $product->toArray();
        $productPayload['thumbnail_image'] = $product->thumbnail_image
            ? api_asset($product->thumbnail_image)
            : null;
        $productPayload['images'] = collect($product->images ?? [])
            ->filter()
            ->map(fn ($image) => api_asset($image))
            ->values()
            ->all();
        $productPayload['meta_image'] = $product->meta_image ? api_asset($product->meta_image) : null;
        $productPayload['video_media'] = $product->video_media ? api_asset($product->video_media) : null;
        $productPayload['short_description'] = rewrite_api_assets_in_html($product->short_description);
        $productPayload['description'] = rewrite_api_assets_in_html($product->description);
        $productPayload['how_to_use'] = rewrite_api_assets_in_html($product->how_to_use);
        $productPayload['good_to_know'] = rewrite_api_assets_in_html($product->good_to_know);
        $productPayload['warranty'] = rewrite_api_assets_in_html($product->warranty);
        $productPayload['variants'] = $variants;
        $promotionPayload = $landingPage->toArray();
        $promotionPayload['content'] = rewrite_api_assets_in_html($landingPage->content);
        $promotionPayload['gallery_images'] = $galleryImages;

        return response()->json([
            'success' => true,
            'data' => [
                'promotion' => $promotionPayload,
                'product' => $productPayload,
                'variants' => $variants,
                'galleryImages' => $galleryImages,
            ],
        ]);
    }

    /**
     * Get product variants with attribute values
     */
    public function getProductVariants($productId)
    {
        $product = Product::with([
            'variants' => function ($query) {
                $query->where('is_active', true)
                    ->with(['values.attribute', 'values.value']);
            },
        ])->findOrFail($productId);

        return response()->json([
            'success' => true,
            'data' => $product->variants
                ->map(fn (ProductVariant $variant) => $this->variantForApi($variant))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Get product by variant selection
     */
    public function getProductByVariant(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid variant selected.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $variant = ProductVariant::with('product')
            ->where('product_id', $productId)
            ->find($request->variant_id);

        if (! $variant) {
            return response()->json([
                'success' => false,
                'message' => 'Variant not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->variantForApi($variant),
        ]);
    }

    private function variantForApi(ProductVariant $variant): array
    {
        $payload = $variant->toArray();
        $payload['image'] = $variant->image ? api_asset($variant->image) : null;

        return $payload;
    }

    /**
     * Send OTP for guest checkout
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Valid phone number is required.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = trim((string) $request->input('phone'));
        $normalizedPhone = ltrim($phone, '0');
        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);

        if (config('app.env') === 'local') {
            Log::info("Promo Checkout OTP for {$phone}: {$code}");
        }

        try {
            $message = "Your promotion checkout OTP is: {$code}. It will expire in 5 minutes.";
            smsSend($message, $normalizedPhone);
        } catch (\Exception $e) {
            Log::error('Promo Checkout OTP send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again.',
            ], 500);
        }

        $otpVerificationId = DB::table('order_otp_verifications')->insertGetId([
            'user_id' => null,
            'phone' => $phone,
            'otp_code' => $code,
            'expires_at' => $expiresAt,
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->put(self::PROMO_CHECKOUT_OTP_SESSION_KEY, [
            'id' => $otpVerificationId,
            'phone' => $phone,
            'code' => $code,
            'verified' => false,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
        ]);
    }

    /**
     * Verify OTP for guest checkout
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'otp_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide valid OTP details.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sessionOtp = session()->get(self::PROMO_CHECKOUT_OTP_SESSION_KEY);
        if (! $sessionOtp || empty($sessionOtp['code']) || empty($sessionOtp['expires_at']) || empty($sessionOtp['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'No OTP request found. Please resend OTP.',
            ], 400);
        }

        if (trim((string) $request->input('phone')) !== (string) $sessionOtp['phone']) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number changed. Please resend OTP.',
            ], 400);
        }

        if (now()->gt($sessionOtp['expires_at'])) {
            session()->forget(self::PROMO_CHECKOUT_OTP_SESSION_KEY);

            return response()->json([
                'success' => false,
                'message' => 'OTP expired. Please resend OTP.',
            ], 400);
        }

        if ((string) $request->input('otp_code') !== (string) $sessionOtp['code']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP code.',
            ], 422);
        }

        $sessionOtp['verified'] = true;
        session()->put(self::PROMO_CHECKOUT_OTP_SESSION_KEY, $sessionOtp);

        if (! empty($sessionOtp['id'])) {
            DB::table('order_otp_verifications')
                ->where('id', $sessionOtp['id'])
                ->update([
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mobile number verified.',
        ]);
    }

    /**
     * Place order from promo checkout (guest checkout)
     */
    public function placeOrder(Request $request)
    {
        $verifiedOtp = $this->getVerifiedPromoCheckoutOtpRecord();
        if (! $verifiedOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your mobile number with OTP before placing the order.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required_without:items|integer|min:1',
            'price' => 'required_without:items|numeric|min:0',
            'items' => 'nullable|array|min:1',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'shipping_delivery_area_id' => 'required|integer|exists:delivery_areas,id',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_address_type' => 'required|in:home,office,hometown',
            'payment_method' => 'required|in:cash_on_delivery,bkash,nagad,rocket,ssl_commerce',
            'shipping_method' => 'required|in:flat_rate,distance_based',
            'shipping_cost' => 'required|numeric|min:0',
            'delivery_area' => 'required|in:inside_dhaka,outside_dhaka',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deliveryArea = DeliveryArea::query()->active()->find($request->shipping_delivery_area_id);
        if (! $deliveryArea) {
            return response()->json([
                'success' => false,
                'message' => 'The selected delivery area is invalid.',
            ], 422);
        }

        $deliveryZone = strcasecmp(trim($deliveryArea->district_name), 'Dhaka') === 0
            ? 'inside_dhaka'
            : 'outside_dhaka';
        $shippingCost = (float) Setting::get(
            'delivery_charge_'.$deliveryZone,
            $deliveryZone === 'inside_dhaka' ? 80 : 150
        );

        try {
            DB::beginTransaction();

            // Create shipping address for guest
            $shippingAddress = ShippingAddress::create([
                'user_id' => null,
                'name' => $request->customer_name,
                'email' => $request->customer_email,
                'phone' => $request->customer_phone,
                'delivery_area_id' => $deliveryArea->id,
                'address' => $request->shipping_address,
                'address_type' => $request->shipping_address_type,
                'postal_code' => $request->shipping_postal_code,
                'is_default' => false,
            ]);

            // Get product and selected variants
            $product = Product::findOrFail($request->product_id);
            $requestedItems = collect($request->input('items', []));

            if ($requestedItems->isEmpty()) {
                $requestedItems = collect([[
                    'variant_id' => $request->variant_id,
                    'quantity' => $request->quantity,
                    'price' => $request->price,
                ]]);
            }

            $productHasVariants = $product->variants()->where('is_active', true)->exists();

            if ($productHasVariants && $requestedItems->contains(fn ($item) => empty($item['variant_id']))) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Please select a product variant.',
                ], 422);
            }

            $variantIds = $requestedItems->pluck('variant_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $variants = ProductVariant::with(['values.value'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $variantIds->count()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid variant selected.',
                ], 422);
            }

            $orderItems = $requestedItems->map(function ($item) use ($variants, $product) {
                $variant = ! empty($item['variant_id'])
                    ? $variants->get((int) $item['variant_id'])
                    : null;
                $quantity = (int) $item['quantity'];
                $price = (float) ($variant?->selling_price ?: $product->price);

                return [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $price * $quantity,
                ];
            });

            // Calculate totals
            $subtotal = $orderItems->sum('subtotal');
            $tax = (float) $request->input('tax', 0);
            $discount = (float) $request->input('discount', 0);
            $total = $subtotal + $tax - $discount + $shippingCost;
            $steadfastCodCharge = Order::steadfastCodChargeFor($request->payment_method, $total);

            // Generate order number
            $orderNumber = Order::generateOrderNumber();

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => null,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address_id' => $shippingAddress->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'steadfast_cod_charger' => $steadfastCodCharge,
                'coupon_id' => null,
                'coupon_code' => null,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'shipping_method' => $request->shipping_method,
                'order_notes' => $request->input('order_notes'),
            ]);

            $totalQuantity = 0;

            foreach ($orderItems as $item) {
                $variant = $item['variant'];
                $itemQuantity = $item['quantity'];
                $itemPrice = $item['price'];
                $itemSubtotal = $item['subtotal'];
                $totalQuantity += $itemQuantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'variant_name' => $variant ? $this->getVariantName($variant) : null,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'product_sku' => $product->sku,
                    'product_image' => $variant?->image ?: $product->getRawOriginal('thumbnail_image'),
                    'price' => $itemPrice,
                    'regular_price' => $product->regular_price,
                    'purchase_price' => $variant?->purchase_price ?? $product->purchase_price,
                    'quantity' => $itemQuantity,
                    'subtotal' => $itemSubtotal,
                ]);

                $this->orderStockService->deduct(
                    $product->id,
                    $variant?->id,
                    $itemQuantity
                );
            }

            $order->update(['stock_deducted_at' => now()]);

            // Update product sales and quantity
            $product->increment('num_of_sale', $totalQuantity);

            // Update OTP verification with order
            DB::table('order_otp_verifications')
                ->where('id', $verifiedOtp->id)
                ->update([
                    'order_id' => $order->id,
                    'updated_at' => now(),
                ]);

            DB::commit();

            // Clear OTP session
            session()->forget(self::PROMO_CHECKOUT_OTP_SESSION_KEY);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'data' => [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'total' => $order->total,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Promo Checkout Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
            ], 500);
        }
    }

    /**
     * Get verified OTP record
     */
    private function getVerifiedPromoCheckoutOtpRecord(): ?object
    {
        $sessionOtp = session()->get(self::PROMO_CHECKOUT_OTP_SESSION_KEY);
        if (! $sessionOtp || empty($sessionOtp['id']) || empty($sessionOtp['verified']) || empty($sessionOtp['expires_at'])) {
            return null;
        }

        if (now()->gt($sessionOtp['expires_at'])) {
            session()->forget(self::PROMO_CHECKOUT_OTP_SESSION_KEY);

            return null;
        }

        return DB::table('order_otp_verifications')
            ->where('id', $sessionOtp['id'])
            ->first();
    }

    /**
     * Get variant name from variant attribute values
     */
    private function getVariantName($variant): string
    {
        if (! $variant->values) {
            return 'Variant '.$variant->id;
        }

        $names = $variant->values
            ->map(fn ($value) => $value->value?->value)
            ->filter()
            ->toArray();

        return implode(', ', $names) ?: 'Variant '.$variant->id;
    }

    /**
     * Get delivery areas for shipping form, grouped by district.
     */
    public function getDivisions()
    {
        $groups = DeliveryArea::query()
            ->active()
            ->orderBy('district_name')
            ->orderBy('name')
            ->get(['id', 'name', 'district_id', 'district_name', 'post_code'])
            ->groupBy('district_name')
            ->map(function ($areas, $districtName) {
                return [
                    'district_id' => $areas->first()?->district_id,
                    'district_name' => $districtName,
                    'areas' => $areas->map(fn (DeliveryArea $area) => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'post_code' => $area->post_code,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }
}
