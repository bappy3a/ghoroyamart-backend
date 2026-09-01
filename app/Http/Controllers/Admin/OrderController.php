<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingAddress;
use App\Services\SteadfastCourier;
use App\Services\OrderStockService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly OrderStockService $orderStockService)
    {
    }

    public function index(Request $request): View
    {
        $query = $this->ordersQuery($request);

        $orders = $query->paginate(15)->withQueryString();
        $statusCounts = $this->statusCounts();
        $isModeratorOrderManagement = $request->routeIs('moderator-order-management.*');
        $pageTitle = $isModeratorOrderManagement ? 'Moderator Order List' : 'Order History';
        $formRoute = $isModeratorOrderManagement ? route('moderator-order-management.index') : route('orders.index');
        $createOrderRoute = $isModeratorOrderManagement ? route('moderator-order-management.create') : route('orders.create');
        $isCancelledList = false;
        $isStatusList = false;
        $currentStatus = $request->status && $request->status !== 'all' ? $request->status : 'all';

        return view('admin.orders.index', compact('orders', 'statusCounts', 'pageTitle', 'formRoute', 'createOrderRoute', 'isCancelledList', 'isStatusList', 'currentStatus', 'isModeratorOrderManagement'));
    }

    public function moderatorIndex(Request $request): View
    {
        $request['created_by_id'] = auth()->id();
        return $this->index($request);
    }

    public function create(): View
    {
        return view('admin.orders.create', $this->orderFormData(
            storeRoute: route('orders.store'),
            backRoute: route('orders.index')
        ));
    }

    public function moderatorCreate(): View
    {
        $view = $this->create();

        return $view->with([
            'storeRoute' => route('moderator-order-management.store'),
            'backRoute' => route('moderator-order-management.index'),
        ]);
    }

    public function moderatorStore(Request $request): RedirectResponse
    {
        return $this->store($request);
    }

    public function edit(Order $order): View|RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        if ($order->order_status === 'cancelled') {
            return redirect()
                ->route('orders.view', $order->order_number)
                ->with('error', 'Cancelled orders cannot be edited. Use restock or cancellation actions instead.');
        }

        $order->load([
            'items.productVariant.values.attribute',
            'items.productVariant.values.value',
            'shippingAddress',
        ]);

        return view('admin.orders.create', $this->orderFormData(
            order: $order,
            storeRoute: route('orders.update', $order->order_number),
            backRoute: route('orders.view', $order->order_number)
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrderPayload($request);

        $deliveryArea = $this->validatedDeliveryArea($validated);

        try {
            $order = DB::transaction(function () use ($validated, $deliveryArea) {
                $shippingAddress = ShippingAddress::create([
                    'user_id' => null,
                    'name' => $validated['customer_name'],
                    'email' => $validated['customer_email'] ?? null,
                    'phone' => $validated['customer_phone'],
                    'delivery_area_id' => $deliveryArea->id,
                    'address' => $validated['shipping_address'],
                    'postal_code' => $validated['shipping_postal_code'] ?? null,
                    'address_type' => $validated['shipping_address_type'],
                    'is_default' => false,
                ]);

                $items = collect($validated['items'])
                    ->filter(fn ($item) => ! empty($item['product_id']))
                    ->values();

                $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
                $variantIds = $items->pluck('product_variant_id')->filter()->map(fn ($id) => (int) $id)->unique();
                $products = Product::with('variants')->whereIn('id', $productIds)->get()->keyBy('id');
                $variants = ProductVariant::with(['values.value'])
                    ->whereIn('id', $variantIds)
                    ->get()
                    ->keyBy('id');

                $orderItems = $items->map(function ($item) use ($products, $variants) {
                    $product = $products->get((int) $item['product_id']);

                    if (! $product) {
                        throw new \RuntimeException('One of the selected products could not be found.');
                    }

                    $variant = null;
                    if (! empty($item['product_variant_id'])) {
                        $variant = $variants->get((int) $item['product_variant_id']);

                        if (! $variant || (int) $variant->product_id !== (int) $product->id || ! $variant->is_active) {
                            throw new \RuntimeException('Please select a valid variant for '.$product->name.'.');
                        }
                    } elseif ($product->variants->where('is_active', true)->isNotEmpty()) {
                        throw new \RuntimeException('Please select a variant for '.$product->name.'.');
                    }

                    $quantity = (int) $item['quantity'];
                    $price = (float) ($variant?->selling_price ?: $product->price);

                    return [
                        'product' => $product,
                        'variant' => $variant,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $price * $quantity,
                    ];
                });

                $deliveryCharges = [
                    'inside_dhaka' => (float) Setting::get('delivery_charge_inside_dhaka', 80),
                    'outside_dhaka' => (float) Setting::get('delivery_charge_outside_dhaka', 150),
                ];

                $subtotal = (float) $orderItems->sum('subtotal');
                $tax = 0;
                $discount = (float) ($validated['discount'] ?? 0);
                $shippingCost = $deliveryCharges[$validated['shipping_method']];
                $total = max(0, $subtotal + $tax - $discount + $shippingCost);
                $steadfastCodCharge = Order::steadfastCodChargeFor($validated['payment_method'], $total);

                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => null,
                    'created_by_id' => auth()->id(),
                    'order_source' => $validated['order_source'],
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'] ?? '',
                    'customer_phone' => $validated['customer_phone'],
                    'shipping_address_id' => $shippingAddress->id,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => $discount,
                    'shipping_cost' => $shippingCost,
                    'total' => $total,
                    'steadfast_cod_charger' => $steadfastCodCharge,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'order_status' => 'pending',
                    'shipping_method' => $validated['shipping_method'],
                    'order_notes' => $validated['order_notes'] ?? null,
                ]);

                $totalQuantityByProduct = [];

                foreach ($orderItems as $item) {
                    $product = $item['product'];
                    $variant = $item['variant'];

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'variant_name' => $variant?->name,
                        'product_name' => $product->name,
                        'product_slug' => $product->slug,
                        'product_sku' => $variant?->sku ?: $product->sku,
                        'product_image' => $variant?->image ?: $product->getRawOriginal('thumbnail_image'),
                        'price' => $item['price'],
                        'regular_price' => $product->regular_price,
                        'purchase_price' => $variant?->purchase_price ?? $product->purchase_price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $this->orderStockService->deduct(
                        $product->id,
                        $variant?->id,
                        $item['quantity']
                    );

                    $totalQuantityByProduct[$product->id] = ($totalQuantityByProduct[$product->id] ?? 0) + $item['quantity'];
                }

                $order->update(['stock_deducted_at' => now()]);

                foreach ($totalQuantityByProduct as $productId => $quantity) {
                    Product::whereKey($productId)->increment('num_of_sale', $quantity);
                }

                OrderTimeline::create([
                    'order_id' => $order->id,
                    'updated_by' => auth()->id(),
                    'description' => 'Order created from backend.',
                    'status' => 'Order Pending',
                    'date' => now(),
                ]);

                return $order;
            });

            $redirectRoute = $request->routeIs('moderator-order-management.store')
                ? 'moderator-order-management.index'
                : 'orders.view';

            $redirect = $request->routeIs('moderator-order-management.store')
                ? redirect()->route($redirectRoute)
                : redirect()->route($redirectRoute, $order->order_number);

            return $redirect->with('success', 'Order created successfully.');
        } catch (Throwable $exception) {
            Log::error('Backend order creation failed.', [
                'error' => $exception->getMessage(),
                'request' => $request->except(['_token']),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Order creation failed: '.$exception->getMessage());
        }
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        if ($order->order_status === 'cancelled') {
            return redirect()
                ->route('orders.view', $order->order_number)
                ->with('error', 'Cancelled orders cannot be edited. Use restock or cancellation actions instead.');
        }

        $validated = $this->validateOrderPayload($request, requiresOrderSource: false);
        $deliveryArea = $this->validatedDeliveryArea($validated);

        try {
            DB::transaction(function () use ($order, $validated, $deliveryArea) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->order_status === 'cancelled') {
                    throw new \RuntimeException('Cancelled orders cannot be edited.');
                }

                $preparedItems = $this->prepareOrderItems($validated['items'], allowInactiveVariants: true);

                $shippingAddress = $lockedOrder->shippingAddress ?: new ShippingAddress([
                    'user_id' => $lockedOrder->user_id,
                    'is_default' => false,
                ]);

                $shippingAddress->fill([
                    'name' => $validated['customer_name'],
                    'email' => $validated['customer_email'] ?? null,
                    'phone' => $validated['customer_phone'],
                    'delivery_area_id' => $deliveryArea->id,
                    'address' => $validated['shipping_address'],
                    'postal_code' => $validated['shipping_postal_code'] ?? null,
                    'address_type' => $validated['shipping_address_type'],
                ])->save();

                $lockedOrder->update([
                    'order_source' => $validated['order_source'],
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'] ?? '',
                    'customer_phone' => $validated['customer_phone'],
                    'shipping_address_id' => $shippingAddress->id,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => $validated['payment_status'],
                    'shipping_method' => $validated['shipping_method'],
                    'order_notes' => $validated['order_notes'] ?? null,
                ]);

                $this->syncOrderItems($lockedOrder, $preparedItems);
                $this->updateOrderTotalsAfterEdit($lockedOrder, $validated);

                OrderTimeline::create([
                    'order_id' => $lockedOrder->id,
                    'updated_by' => auth()->id(),
                    'description' => 'Order products, customer, shipping, or payment details were edited from backend.',
                    'status' => 'Order Edited',
                    'date' => now(),
                ]);
            });

            return redirect()
                ->route('orders.view', $order->order_number)
                ->with('success', 'Order updated successfully.');
        } catch (Throwable $exception) {
            Log::error('Backend order update failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
                'request' => $request->except(['_token', '_method']),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Order update failed: '.$exception->getMessage());
        }
    }

    private function validateOrderPayload(Request $request, bool $requiresOrderSource = true): array
    {
        $validated = $request->validate([
            'order_source' => [$requiresOrderSource ? 'required' : 'nullable', Rule::in(array_keys($this->sourceOptions()))],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
            'shipping_delivery_area_id' => ['required', 'integer', 'exists:delivery_areas,id'],
            'shipping_address' => ['required', 'string'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address_type' => ['required', 'in:home,office,hometown'],
            'shipping_method' => ['required', 'in:inside_dhaka,outside_dhaka'],
            'payment_method' => ['required', 'in:cash_on_delivery,bkash,nagad,rocket,ssl_commerce'],
            'payment_status' => ['required', 'in:pending,paid,failed,refunded'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'order_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'distinct', 'exists:order_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'customer_phone.regex' => 'Please enter a valid 11 digit Bangladesh mobile number.',
        ]);

        $validated['order_source'] = $validated['order_source'] ?? 'website';

        return $validated;
    }

    private function validatedDeliveryArea(array $validated): DeliveryArea
    {
        return DeliveryArea::findOrFail($validated['shipping_delivery_area_id']);
    }

    private function prepareOrderItems(array $items, bool $allowInactiveVariants = false)
    {
        $items = collect($items)
            ->filter(fn ($item) => ! empty($item['product_id']))
            ->values();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Please add at least one product to the order.');
        }

        $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $variantIds = $items->pluck('product_variant_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $products = Product::with('variants')->whereIn('id', $productIds)->get()->keyBy('id');
        $variants = ProductVariant::with(['values.value'])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        return $items->map(function ($item) use ($products, $variants, $allowInactiveVariants) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                throw new \RuntimeException('One of the selected products could not be found.');
            }

            $variant = null;
            if (! empty($item['product_variant_id'])) {
                $variant = $variants->get((int) $item['product_variant_id']);

                if (! $variant || (int) $variant->product_id !== (int) $product->id || (! $allowInactiveVariants && ! $variant->is_active)) {
                    throw new \RuntimeException('Please select a valid variant for '.$product->name.'.');
                }
            } elseif ($product->variants->where('is_active', true)->isNotEmpty()) {
                throw new \RuntimeException('Please select a variant for '.$product->name.'.');
            }

            $quantity = (int) $item['quantity'];
            $price = (float) ($variant?->selling_price ?: $product->price);

            return [
                'id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => round($price * $quantity, 2),
            ];
        });
    }

    private function syncOrderItems(Order $order, $preparedItems): void
    {
        $existingItems = OrderItem::where('order_id', $order->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $submittedIds = $preparedItems->pluck('id')->filter()->unique();
        $invalidSubmittedIds = $submittedIds->diff($existingItems->keys());

        if ($invalidSubmittedIds->isNotEmpty()) {
            throw new \RuntimeException('One or more order items do not belong to this order.');
        }

        $removedItems = $existingItems->reject(fn (OrderItem $item) => $submittedIds->contains($item->id));

        foreach ($removedItems as $item) {
            if ($item->hasCancelledQuantity()) {
                throw new \RuntimeException('Items with cancelled quantities cannot be removed from the edit form.');
            }

            $activeQuantity = $item->activeQuantity();

            if ($order->stock_deducted_at) {
                $this->restockOrderItemQuantity($item, $activeQuantity);
            }

            $this->adjustProductSaleCount($item->product_id, -$activeQuantity);
            $item->delete();
        }

        foreach ($preparedItems as $preparedItem) {
            $product = $preparedItem['product'];
            $variant = $preparedItem['variant'];

            if ($preparedItem['id']) {
                $item = $existingItems->get($preparedItem['id']);
                $cancelledQuantity = $item->cancelledQuantity();

                if ($preparedItem['quantity'] < $cancelledQuantity) {
                    throw new \RuntimeException('Item quantity cannot be less than the already cancelled quantity.');
                }

                $isChangingCatalogItem = (int) $item->product_id !== (int) $product->id
                    || (int) ($item->product_variant_id ?? 0) !== (int) ($variant?->id ?? 0);

                if ($item->hasCancelledQuantity() && $isChangingCatalogItem) {
                    throw new \RuntimeException('Items with cancelled quantities cannot be changed to a different product or variant.');
                }

                $oldActiveQuantity = $item->activeQuantity();
                $newActiveQuantity = max($preparedItem['quantity'] - $cancelledQuantity, 0);

                $this->adjustInventoryForItemChange(
                    $order,
                    $item,
                    $oldActiveQuantity,
                    $product->id,
                    $variant?->id,
                    $newActiveQuantity
                );
                $this->adjustSalesForItemChange($item->product_id, $oldActiveQuantity, $product->id, $newActiveQuantity);

                $item->update(array_merge(
                    $this->orderItemAttributes($preparedItem),
                    [
                        'item_status' => $this->itemStatusForQuantity($preparedItem['quantity'], $cancelledQuantity),
                    ]
                ));

                continue;
            }

            if ($order->stock_deducted_at) {
                $this->orderStockService->deduct($product->id, $variant?->id, $preparedItem['quantity']);
            }

            $this->adjustProductSaleCount($product->id, $preparedItem['quantity']);

            OrderItem::create(array_merge(
                ['order_id' => $order->id],
                $this->orderItemAttributes($preparedItem),
                [
                    'cancelled_quantity' => 0,
                    'restocked_quantity' => 0,
                    'item_status' => 'active',
                ]
            ));
        }
    }

    private function orderItemAttributes(array $preparedItem): array
    {
        /** @var Product $product */
        $product = $preparedItem['product'];
        /** @var ProductVariant|null $variant */
        $variant = $preparedItem['variant'];

        return [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'variant_name' => $variant?->name,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_sku' => $variant?->sku ?: $product->sku,
            'product_image' => $variant?->image ?: $product->getRawOriginal('thumbnail_image'),
            'price' => $preparedItem['price'],
            'regular_price' => $product->regular_price,
            'purchase_price' => $variant?->purchase_price ?? $product->purchase_price,
            'quantity' => $preparedItem['quantity'],
            'subtotal' => $preparedItem['subtotal'],
        ];
    }

    private function adjustInventoryForItemChange(
        Order $order,
        OrderItem $item,
        int $oldActiveQuantity,
        int $newProductId,
        ?int $newVariantId,
        int $newActiveQuantity
    ): void {
        if (! $order->stock_deducted_at) {
            return;
        }

        $sameInventoryItem = (int) $item->product_id === $newProductId
            && (int) ($item->product_variant_id ?? 0) === (int) ($newVariantId ?? 0);

        if ($sameInventoryItem) {
            $delta = $newActiveQuantity - $oldActiveQuantity;

            if ($delta > 0) {
                $this->orderStockService->deduct($newProductId, $newVariantId, $delta);
            } elseif ($delta < 0) {
                $this->restockOrderItemQuantity($item, abs($delta));
            }

            return;
        }

        $this->restockOrderItemQuantity($item, $oldActiveQuantity);
        $this->orderStockService->deduct($newProductId, $newVariantId, $newActiveQuantity);
    }

    private function adjustSalesForItemChange(int $oldProductId, int $oldActiveQuantity, int $newProductId, int $newActiveQuantity): void
    {
        if ($oldProductId === $newProductId) {
            $this->adjustProductSaleCount($newProductId, $newActiveQuantity - $oldActiveQuantity);

            return;
        }

        $this->adjustProductSaleCount($oldProductId, -$oldActiveQuantity);
        $this->adjustProductSaleCount($newProductId, $newActiveQuantity);
    }

    private function adjustProductSaleCount(int $productId, int $quantityDelta): void
    {
        if ($quantityDelta === 0) {
            return;
        }

        $product = Product::whereKey($productId)->lockForUpdate()->first();

        if (! $product) {
            return;
        }

        $product->update([
            'num_of_sale' => max(0, (int) $product->num_of_sale + $quantityDelta),
        ]);
    }

    private function updateOrderTotalsAfterEdit(Order $order, array $validated): void
    {
        $order->unsetRelation('items');
        $order->load('items');

        $activeSubtotal = round($order->items->sum(fn (OrderItem $item) => $item->activeSubtotal()), 2);
        $tax = $activeSubtotal > 0 ? (float) $order->tax : 0;
        $discount = $activeSubtotal > 0 ? min((float) ($validated['discount'] ?? 0), $activeSubtotal + $tax) : 0;
        $shippingCost = $activeSubtotal > 0 ? $this->deliveryCharges()[$validated['shipping_method']] : 0;
        $total = max(0, $activeSubtotal + $tax - $discount + $shippingCost);

        $order->update([
            'subtotal' => $activeSubtotal,
            'tax' => $tax,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'steadfast_cod_charger' => Order::steadfastCodChargeFor($order->payment_method, $total),
        ]);
    }

    private function itemStatusForQuantity(int $quantity, int $cancelledQuantity): string
    {
        if ($cancelledQuantity <= 0) {
            return 'active';
        }

        return $cancelledQuantity >= $quantity ? 'cancelled' : 'partially_cancelled';
    }

    public function cancelled(Request $request): View
    {
        return $this->status($request, 'cancelled');
    }

    public function status(Request $request, string $status): View
    {
        $statusMap = $this->statusRouteMap();

        abort_unless(array_key_exists($status, $statusMap), 404);

        $orderStatus = $statusMap[$status]['value'];
        $requestedStatus = $request->input('status');
        $filterStatus = $orderStatus;

        if ($orderStatus === 'delivered' && $requestedStatus === 'delivered') {
            $filterStatus = 'delivered_only';
        } elseif ($orderStatus === 'delivered' && $requestedStatus === 'partial_delivered') {
            $filterStatus = 'partial_delivered';
        }
        $orders = $this->ordersQuery($request, $filterStatus)->paginate(15)->withQueryString();
        $statusCounts = $this->statusCounts();
        $pageTitle = $statusMap[$status]['title'];
        $formRoute = route($statusMap[$status]['route']);
        $isCancelledList = $orderStatus === 'cancelled';
        $isStatusList = true;
        $showStatusFilter = $orderStatus === 'delivered';
        $currentStatus = $filterStatus;
        if($pageTitle === 'Shipped Orders') {
            $pageTitle = 'In Courier';
        }
        return view('admin.orders.index', compact('orders', 'statusCounts', 'pageTitle', 'formRoute', 'isCancelledList', 'isStatusList', 'showStatusFilter', 'currentStatus'));
    }

    public function view(Request $request, Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load([
            'items.product',
            'items.productVariant.values.attribute',
            'items.productVariant.values.value',
            'items.cancellationActor',
            'user',
            'shippingAddress',
            'coupon',
            'creator',
            'timelines.updater',
        ]);

        $itemScope = $this->itemScopeFromRequest($request);

        return view('admin.orders.view', compact('order', 'itemScope'));
    }

    public function search(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['nullable', 'string', 'max:255'],
        ]);

        if (! filled($validated['order_number'] ?? null)) {
            return view('admin.orders.search');
        }

        $orderNumber = trim($validated['order_number']);
        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return view('admin.orders.search')->with([
                'searchedOrderNumber' => $orderNumber,
                'orderNotFound' => true,
            ]);
        }

        return redirect()->route('orders.search.details', $order);
    }

    public function searchDetails(Order $order): View
    {
        $order->load([
            'items.productVariant.values.attribute',
            'items.productVariant.values.value',
            'shippingAddress',
            'timelines.updater',
        ]);

        return view('admin.orders.search-details', compact('order'));
    }

    public function cancelFromSearch(Order $order): RedirectResponse
    {
        $cancelled = DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->order_status !== 'pending') {
                return false;
            }

            $lockedOrder->update([
                'order_status' => 'cancelled',
                'cancelled_by_type' => 'staff',
                'cancelled_by_id' => auth()->id(),
                'cancellation_reason' => 'Cancelled from backend order search.',
                'cancelled_at' => now(),
            ]);

            $this->markOrderItemsCancelled(
                $lockedOrder,
                'staff',
                auth()->id(),
                'Cancelled from backend order search.'
            );

            OrderTimeline::create([
                'order_id' => $lockedOrder->id,
                'updated_by' => auth()->id(),
                'description' => 'Order cancelled from backend order search.',
                'status' => 'Order Cancelled',
                'date' => now(),
            ]);

            return true;
        });

        return redirect()
            ->route('orders.search.details', $order)
            ->with($cancelled ? 'success' : 'error', $cancelled
                ? 'Order cancelled successfully.'
                : 'Only pending orders can be cancelled.');
    }

    public function cancelItem(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        abort_if((int) $item->order_id !== (int) $order->id, 404);

        $validated = $request->validate([
            'cancelled_quantity' => ['required', 'integer', 'min:1'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($order, $item, $validated) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->order_status === 'cancelled') {
                return [
                    'type' => 'error',
                    'message' => 'This order is already fully cancelled.',
                ];
            }

            $lockedItem = OrderItem::where('order_id', $lockedOrder->id)
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $validated['cancelled_quantity'];

            if ($quantity > $lockedItem->activeQuantity()) {
                return [
                    'type' => 'error',
                    'message' => 'Cancelled quantity cannot be more than the remaining delivered quantity.',
                ];
            }

            $reason = $validated['cancellation_reason'] ?? null;
            $cancelledQuantity = $lockedItem->cancelledQuantity() + $quantity;
            $cancelledAt = now();

            $lockedItem->update([
                'cancelled_quantity' => $cancelledQuantity,
                'item_status' => $this->itemStatusForCancelledQuantity($lockedItem, $cancelledQuantity),
                'cancelled_by_type' => 'staff',
                'cancelled_by_id' => auth()->id(),
                'cancellation_reason' => $reason,
                'cancelled_at' => $cancelledAt,
            ]);

            $lockedItem->refresh();

            if ($lockedOrder->stock_deducted_at) {
                $this->restockOrderItemQuantity($lockedItem, $quantity);
                $lockedItem->increment('restocked_quantity', $quantity);
                $this->adjustProductSaleCount($lockedItem->product_id, -$quantity);
            }

            $this->updateOrderTotalsForActiveItems($lockedOrder);

            $lockedOrder->unsetRelation('items');
            $lockedOrder->load('items');

            if ($lockedOrder->items->sum(fn (OrderItem $orderItem) => $orderItem->activeQuantity()) <= 0) {
                $lockedOrder->update([
                    'order_status' => 'cancelled',
                    'cancelled_by_type' => 'staff',
                    'cancelled_by_id' => auth()->id(),
                    'cancellation_reason' => $reason ?: 'All order items were cancelled.',
                    'cancelled_at' => $cancelledAt,
                ]);
            }

            OrderTimeline::create([
                'order_id' => $lockedOrder->id,
                'updated_by' => auth()->id(),
                'description' => trim($lockedItem->product_name.' quantity '.$quantity.' was cancelled/returned.'.($reason ? "\nReason: {$reason}" : '')),
                'status' => 'Order Item Cancelled',
                'date' => $cancelledAt,
            ]);

            return [
                'type' => 'success',
                'message' => 'Order item cancelled successfully.',
            ];
        });

        return redirect()->back()->with($result['type'], $result['message']);
    }

    public function updateStatus(Request $request, Order $order, SteadfastCourier $steadfastCourier)
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'order_status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'cancellation_reason' => 'required_if:order_status,cancelled|nullable|string|max:1000',
        ]);

        $targetStatus = $validated['order_status'];
        $isCancellingOrder = $targetStatus === 'cancelled';
        $cancellationReason = $isCancellingOrder ? trim($validated['cancellation_reason']) : null;

        if ($order->order_status === 'cancelled') {
            return $this->orderStatusResponse(
                $request,
                false,
                'Cancelled orders cannot be status updated.',
                $order,
                422
            );
        }

        $nextStatus = $this->nextOrderStatus($order->order_status);

        if ($isCancellingOrder && ! $this->canCancelOrderStatus($order->order_status)) {
            return $this->orderStatusResponse(
                $request,
                false,
                'This order cannot be cancelled from its current status.',
                $order,
                422
            );
        }

        if (! $isCancellingOrder && $targetStatus !== $nextStatus) {
            return $this->orderStatusResponse(
                $request,
                false,
                $this->invalidStatusTransitionMessage($order->order_status),
                $order,
                422
            );
        }

        $statusDetails = $this->orderStatusDetails($targetStatus);
        $steadfastResponse = null;

        if ($targetStatus === 'processing' && blank($order->steadfast_consignment_id)) {
            try {
                $steadfastResponse = $steadfastCourier->createOrder($order);
            } catch (Throwable $exception) {
                Log::error('Steadfast order creation failed.', [
                    'order_id' => $order->id,
                    'target_status' => $targetStatus,
                    'error' => $exception->getMessage(),
                ]);

                return $this->orderStatusResponse(
                    $request,
                    false,
                    'Steadfast order failed: '.$exception->getMessage(),
                    $order,
                    422
                );
            }
        }

        try {
            DB::transaction(function () use (&$order, $targetStatus, $statusDetails, $steadfastResponse, $isCancellingOrder, $cancellationReason) {
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($isCancellingOrder && ! $this->canCancelOrderStatus($order->order_status)) {
                    throw new \RuntimeException('This order cannot be cancelled from its current status.');
                }

                if (! $isCancellingOrder && $targetStatus !== $this->nextOrderStatus($order->order_status)) {
                    throw new \RuntimeException($this->invalidStatusTransitionMessage($order->order_status));
                }

                $updates = [
                    'order_status' => $targetStatus,
                ];

                if ($isCancellingOrder) {
                    $updates = array_merge($updates, [
                        'cancelled_by_type' => 'staff',
                        'cancelled_by_id' => auth()->id(),
                        'cancellation_reason' => $cancellationReason,
                        'cancelled_at' => now(),
                    ]);
                }

                if ($steadfastResponse) {
                    $updates = array_merge($updates, [
                        'steadfast_consignment_id' => data_get($steadfastResponse, 'consignment.consignment_id'),
                        'steadfast_tracking_code' => data_get($steadfastResponse, 'consignment.tracking_code'),
                        'steadfast_status' => data_get($steadfastResponse, 'consignment.status'),
                        'steadfast_response' => $steadfastResponse,
                        'steadfast_order_placed_at' => now(),
                    ]);
                }

                $order->update($updates);

                if ($isCancellingOrder) {
                    $this->markOrderItemsCancelled($order, 'staff', auth()->id(), $cancellationReason);
                }

                OrderTimeline::create([
                    'order_id' => $order->id,
                    'updated_by' => auth()->id(),
                    'description' => $statusDetails['description'],
                    'status' => $statusDetails['status'],
                    'date' => now(),
                ]);
            });

            $order->refresh();

            $message = $isCancellingOrder
                ? 'Order cancelled successfully.'
                : ($steadfastResponse
                    ? 'Order status updated and Steadfast consignment created successfully.'
                    : 'Order status updated successfully.');

            return $this->orderStatusResponse($request, true, $message, $order);
        } catch (Throwable $exception) {
            Log::error('Order status update failed.', [
                'order_id' => $order->id,
                'target_status' => $targetStatus,
                'error' => $exception->getMessage(),
            ]);

            return $this->orderStatusResponse(
                $request,
                false,
                'Order status update failed: '.$exception->getMessage(),
                $order,
                422
            );
        }
    }

    public function bulkConfirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'order_ids.*' => ['required', 'integer', 'distinct', 'exists:orders,id'],
        ], [
            'order_ids.required' => 'Please select at least one pending order.',
            'order_ids.min' => 'Please select at least one pending order.',
            'order_ids.max' => 'You can confirm up to 100 orders at a time.',
        ]);

        $orderIds = collect($validated['order_ids'])
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        try {
            $confirmedCount = DB::transaction(function () use ($orderIds) {
                $ordersQuery = Order::whereKey($orderIds);

                if ($this->shouldScopeToCreatedOrders()) {
                    $ordersQuery->where('created_by_id', auth()->id());
                }

                $orders = $ordersQuery
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($orders->count() !== $orderIds->count()) {
                    throw new \DomainException('One or more selected orders are unavailable. No orders were changed.');
                }

                foreach ($orders as $order) {
                    if ($order->order_status !== 'pending') {
                        throw new \DomainException(
                            "Order {$order->order_number} is no longer pending. No orders were changed."
                        );
                    }
                }

                $statusDetails = $this->orderStatusDetails('confirmed');

                foreach ($orders as $order) {
                    $order->update(['order_status' => 'confirmed']);

                    OrderTimeline::create([
                        'order_id' => $order->id,
                        'updated_by' => auth()->id(),
                        'description' => $statusDetails['description'],
                        'status' => $statusDetails['status'],
                        'date' => now(),
                    ]);
                }

                return $orders->count();
            });

            return redirect()
                ->route('orders.pending')
                ->with('success', "{$confirmedCount} order(s) moved to Confirmed successfully.");
        } catch (\DomainException $exception) {
            return redirect()
                ->route('orders.pending')
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Bulk order confirmation failed.', [
                'order_ids' => $orderIds->all(),
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('orders.pending')
                ->with('error', 'Bulk confirmation failed: '.$exception->getMessage());
        }
    }

    public function bulkMoveToPackaging(Request $request, SteadfastCourier $steadfastCourier): RedirectResponse
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:500'],
            'order_ids.*' => ['required', 'integer', 'distinct', 'exists:orders,id'],
        ], [
            'order_ids.required' => 'Please select at least one confirmed order.',
            'order_ids.min' => 'Please select at least one confirmed order.',
            'order_ids.max' => 'Steadfast allows up to 500 orders at a time.',
        ]);

        $orderIds = collect($validated['order_ids'])
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        try {
            $ordersQuery = Order::with([
                'items',
                'shippingAddress.deliveryArea',
            ])->whereKey($orderIds);

            if ($this->shouldScopeToCreatedOrders()) {
                $ordersQuery->where('created_by_id', auth()->id());
            }

            $orders = $ordersQuery->orderBy('id')->get();

            if ($orders->count() !== $orderIds->count()) {
                throw new \DomainException('One or more selected orders are unavailable. No orders were changed.');
            }

            foreach ($orders as $order) {
                if ($order->order_status !== 'confirmed') {
                    throw new \DomainException(
                        "Order {$order->order_number} is no longer confirmed. No orders were changed."
                    );
                }
            }

            $ordersNeedingConsignment = $orders
                ->filter(fn (Order $order) => blank($order->steadfast_consignment_id))
                ->values();
            $ordersAlreadyConsigned = $orders
                ->filter(fn (Order $order) => filled($order->steadfast_consignment_id))
                ->values();

            $steadfastResponsesByInvoice = collect();
            $failedInvoices = collect();

            if ($ordersNeedingConsignment->isNotEmpty()) {
                $steadfastResponses = $steadfastCourier->bulkCreateOrders($ordersNeedingConsignment);
                $steadfastResponsesByInvoice = collect($steadfastResponses)
                    ->filter(fn (array $item) => filled(data_get($item, 'invoice')))
                    ->keyBy(fn (array $item) => (string) data_get($item, 'invoice'));

                $failedInvoices = $ordersNeedingConsignment
                    ->filter(function (Order $order) use ($steadfastResponsesByInvoice) {
                        $response = $steadfastResponsesByInvoice->get($order->order_number);

                        return ! $this->isSuccessfulSteadfastBulkItem($response);
                    })
                    ->map(fn (Order $order) => $order->order_number)
                    ->values();
            }

            $successfulOrderIds = $ordersAlreadyConsigned
                ->pluck('id')
                ->merge(
                    $ordersNeedingConsignment
                        ->reject(fn (Order $order) => $failedInvoices->contains($order->order_number))
                        ->pluck('id')
                )
                ->values();

            if ($successfulOrderIds->isEmpty()) {
                return redirect()
                    ->route('orders.confirmed')
                    ->with('error', 'Steadfast bulk order failed for all selected orders.');
            }

            $movedCount = DB::transaction(function () use ($successfulOrderIds, $steadfastResponsesByInvoice) {
                $orders = Order::whereKey($successfulOrderIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($orders->count() !== $successfulOrderIds->count()) {
                    throw new \RuntimeException('One or more selected orders became unavailable.');
                }

                foreach ($orders as $order) {
                    if ($order->order_status !== 'confirmed') {
                        throw new \RuntimeException(
                            "Order {$order->order_number} is no longer confirmed. No orders were changed."
                        );
                    }
                }

                $statusDetails = $this->orderStatusDetails('processing');

                foreach ($orders as $order) {
                    $updates = [
                        'order_status' => 'processing',
                    ];

                    $steadfastResponse = $steadfastResponsesByInvoice->get($order->order_number);

                    if ($this->isSuccessfulSteadfastBulkItem($steadfastResponse)) {
                        $updates = array_merge($updates, [
                            'steadfast_consignment_id' => data_get($steadfastResponse, 'consignment_id'),
                            'steadfast_tracking_code' => data_get($steadfastResponse, 'tracking_code'),
                            'steadfast_response' => $steadfastResponse,
                            'steadfast_order_placed_at' => now(),
                        ]);
                    }

                    $order->update($updates);

                    OrderTimeline::create([
                        'order_id' => $order->id,
                        'updated_by' => auth()->id(),
                        'description' => $statusDetails['description'],
                        'status' => $statusDetails['status'],
                        'date' => now(),
                    ]);
                }

                return $orders->count();
            });

            $message = "{$movedCount} order(s) moved to Packaging successfully.";

            if ($failedInvoices->isNotEmpty()) {
                $message .= ' Steadfast failed for: '.$failedInvoices->implode(', ').'.';
            }

            return redirect()
                ->route('orders.confirmed')
                ->with($failedInvoices->isEmpty() ? 'success' : 'error', $message);
        } catch (\DomainException $exception) {
            return redirect()
                ->route('orders.confirmed')
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Bulk order packaging failed.', [
                'order_ids' => $orderIds->all(),
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('orders.confirmed')
                ->with('error', 'Bulk packaging failed: '.$exception->getMessage());
        }
    }

    public function restock(Order $order)
    {
        $this->authorizeOrderAccess($order);

        $result = DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->isStockRestockableStatus()) {
                return [
                    'type' => 'error',
                    'message' => 'Only cancelled or partial delivered orders can be restocked.',
                ];
            }

            if ($order->stock_restocked_at) {
                return [
                    'type' => 'error',
                    'message' => 'This order has already been restocked.',
                ];
            }

            if (!$order->stock_deducted_at) {
                return [
                    'type' => 'error',
                    'message' => 'Stock was not deducted for this order yet.',
                ];
            }

            $order->load(['items.product', 'items.productVariant']);

            foreach ($order->items as $item) {
                $quantityToRestock = $item->restockableQuantity();

                if ($quantityToRestock <= 0) {
                    continue;
                }

                $this->restockOrderItemQuantity($item, $quantityToRestock);
                $item->increment('restocked_quantity', $quantityToRestock);
                $this->markRestockedItemCancelled($item, $quantityToRestock);
                $this->adjustProductSaleCount($item->product_id, -$quantityToRestock);
            }

            $this->updateOrderTotalsForActiveItems($order);
            $order->update(['stock_restocked_at' => now()]);

            OrderTimeline::create([
                'order_id' => $order->id,
                'updated_by' => auth()->id(),
                'description' => $order->order_status === 'cancelled'
                    ? 'Cancelled order quantities were added back to inventory.'
                    : 'Partial delivered order quantities were added back to inventory.',
                'status' => 'Stock Restocked',
                'date' => now(),
            ]);

            return [
                'type' => 'success',
                'message' => 'Product quantity restocked successfully.',
            ];
        });

        return redirect()->back()->with($result['type'], $result['message']);
    }

    public function restockItem(Order $order, OrderItem $item): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        abort_if((int) $item->order_id !== (int) $order->id, 404);

        $result = DB::transaction(function () use ($order, $item) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $lockedOrder->isStockRestockableStatus()) {
                return [
                    'type' => 'error',
                    'message' => 'Only cancelled or partial delivered orders can be restocked.',
                ];
            }

            if ($lockedOrder->stock_restocked_at) {
                return [
                    'type' => 'error',
                    'message' => 'This order has already been restocked.',
                ];
            }

            if (! $lockedOrder->stock_deducted_at) {
                return [
                    'type' => 'error',
                    'message' => 'Stock was not deducted for this order yet.',
                ];
            }

            $lockedItem = OrderItem::where('order_id', $lockedOrder->id)
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantityToRestock = $lockedItem->restockableQuantity();

            if ($quantityToRestock <= 0) {
                return [
                    'type' => 'error',
                    'message' => 'This product quantity has already been restocked.',
                ];
            }

            $this->restockOrderItemQuantity($lockedItem, $quantityToRestock);
            $lockedItem->increment('restocked_quantity', $quantityToRestock);
            $this->markRestockedItemCancelled($lockedItem, $quantityToRestock);
            $this->adjustProductSaleCount($lockedItem->product_id, -$quantityToRestock);
            $this->updateOrderTotalsForActiveItems($lockedOrder);

            $remainingRestockableQuantity = OrderItem::where('order_id', $lockedOrder->id)
                ->get()
                ->sum(fn (OrderItem $orderItem) => $orderItem->restockableQuantity());

            if ($remainingRestockableQuantity <= 0) {
                $lockedOrder->update(['stock_restocked_at' => now()]);
            }

            OrderTimeline::create([
                'order_id' => $lockedOrder->id,
                'updated_by' => auth()->id(),
                'description' => $lockedItem->product_name.' quantity '.$quantityToRestock.' was added back to inventory.',
                'status' => 'Product Restocked',
                'date' => now(),
            ]);

            return [
                'type' => 'success',
                'message' => 'Product quantity restocked successfully.',
            ];
        });

        return redirect()->back()->with($result['type'], $result['message']);
    }

    public function deliveryReceiptPreview(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load([
            'items.product',
            'items.productVariant.values.attribute',
            'items.productVariant.values.value',
            'shippingAddress.deliveryArea',
            'coupon',
            'creator',
        ]);

        return view('admin.orders.delivery-receipt', [
            'order' => $order,
            'itemScope' => 'active',
            'backUrl' => auth()->user()?->can('orders.details')
                ? route('orders.view', $order->order_number)
                : route('orders.index'),
        ]);
    }

    public function thermalInvoicePreview(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load([
            'items.product',
            'items.productVariant.values.attribute',
            'items.productVariant.values.value',
            'shippingAddress.deliveryArea',
        ]);

        return view('admin.orders.thermal-invoice', [
            'order' => $order,
            'itemScope' => 'active',
            'backUrl' => auth()->user()?->can('orders.details')
                ? route('orders.view', $order->order_number)
                : route('orders.index'),
        ]);
    }

    private function ordersQuery(Request $request, ?string $forcedStatus = null)
    {
        $query = Order::with([
            'items.productVariant.values.attribute',
            'items.productVariant.values.value',
            'items.cancellationActor',
            'user',
            'creator',
            'cancellationActor',
            'timelines.updater',
        ])->orderBy('created_at', 'desc');

        if ($this->shouldScopeToCreatedOrders()) {
            $query->where('created_by_id', auth()->id());
        }
        if( $request->filled('created_by_id')) {
            $query->where('created_by_id', $request->created_by_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', "%{$search}%")
                            ->orWhere('product_sku', 'like', "%{$search}%")
                            ->orWhereHas('productVariant', function ($variantQuery) use ($search) {
                                $variantQuery->where('sku', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($forcedStatus) {
            $this->applyOrderStatusFilter($query, $forcedStatus);
        } elseif ($request->filled('status') && $request->status !== 'all') {
            $this->applyOrderStatusFilter($query, $request->status);
        }

        if ($request->filled('payment') && $request->payment !== 'all') {
            $query->where('payment_method', $request->payment);
        }

        if ($request->filled('date_range')) {
            $dates = collect(explode(' to ', $request->date_range))
                ->map(fn (string $date) => trim($date))
                ->filter();

            if ($dates->count() === 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates->first())->startOfDay(),
                    Carbon::parse($dates->last())->endOfDay(),
                ]);
            } elseif ($dates->count() === 1) {
                $query->whereDate('created_at', Carbon::parse($dates->first())->toDateString());
            }
        } else {
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        return $query;
    }

    private function statusCounts(): array
    {
        $query = Order::query();

        if ($this->shouldScopeToCreatedOrders()) {
            $query->where('created_by_id', auth()->id());
        }

        return [
            'all' => (clone $query)->count(),
            'pending' => (clone $query)->where('order_status', 'pending')->count(),
            'confirmed' => (clone $query)->where('order_status', 'confirmed')->count(),
            'processing' => (clone $query)->where('order_status', 'processing')->count(),
            'shipped' => (clone $query)->where('order_status', 'shipped')->count(),
            'delivered' => $this->applyOrderStatusFilter(clone $query, 'delivered')->count(),
            'delivered_only' => (clone $query)->where('order_status', 'delivered')->count(),
            'partial_delivered' => (clone $query)->where('order_status', 'partial_delivered')->count(),
            'cancelled' => $this->applyOrderStatusFilter(clone $query, 'cancelled')->count(),
        ];
    }

    private function applyOrderStatusFilter($query, string $status)
    {
        if ($status === 'cancelled') {
            return $query->where(function ($statusQuery) {
                $statusQuery->where('order_status', 'cancelled')
                    ->orWhereHas('items', fn ($itemsQuery) => $itemsQuery->where('cancelled_quantity', '>', 0));
            });
        }

        if ($status === 'delivered') {
            return $query->whereIn('order_status', ['delivered', 'partial_delivered'])
                ->whereHas('items', fn ($itemsQuery) => $itemsQuery->whereColumn('cancelled_quantity', '<', 'quantity'));
        }

        if ($status === 'delivered_only') {
            return $query->where('order_status', 'delivered');
        }

        return $query->where('order_status', $status);
    }

    private function itemScopeFromRequest(Request $request): ?string
    {
        $scope = $request->query('item_scope');

        return in_array($scope, ['active', 'cancelled'], true) ? $scope : null;
    }

    private function itemStatusForCancelledQuantity(OrderItem $item, int $cancelledQuantity): string
    {
        if ($cancelledQuantity <= 0) {
            return 'active';
        }

        return $cancelledQuantity >= (int) $item->quantity
            ? 'cancelled'
            : 'partially_cancelled';
    }

    private function markOrderItemsCancelled(Order $order, string $cancelledByType, ?int $cancelledById, ?string $reason): void
    {
        $items = OrderItem::where('order_id', $order->id)
            ->lockForUpdate()
            ->get();

        foreach ($items as $item) {
            if ($item->quantity <= 0) {
                continue;
            }

            $item->update([
                'cancelled_quantity' => $item->quantity,
                'item_status' => 'cancelled',
                'cancelled_by_type' => $cancelledByType,
                'cancelled_by_id' => $cancelledById,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);
        }
    }

    private function markRestockedItemCancelled(OrderItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $cancelledQuantity = min(
            max((int) $item->quantity, 0),
            $item->cancelledQuantity() + $quantity
        );

        if ($cancelledQuantity <= $item->cancelledQuantity()) {
            return;
        }

        $item->update([
            'cancelled_quantity' => $cancelledQuantity,
            'item_status' => $this->itemStatusForCancelledQuantity($item, $cancelledQuantity),
            'cancelled_by_type' => $item->cancelled_by_type ?: 'staff',
            'cancelled_by_id' => $item->cancelled_by_id ?: auth()->id(),
            'cancellation_reason' => $item->cancellation_reason ?: 'Restocked from order details.',
            'cancelled_at' => $item->cancelled_at ?: now(),
        ]);
    }

    private function updateOrderTotalsForActiveItems(Order $order): void
    {
        $order->unsetRelation('items');
        $order->load('items');

        $activeSubtotal = round($order->items->sum(fn (OrderItem $item) => $item->activeSubtotal()), 2);
        $tax = $activeSubtotal > 0 ? (float) $order->tax : 0;
        $discount = $activeSubtotal > 0 ? min((float) $order->discount, $activeSubtotal + $tax) : 0;
        $shippingCost = $activeSubtotal > 0 ? (float) $order->shipping_cost : 0;
        $total = max(0, $activeSubtotal + $tax - $discount + $shippingCost);

        $order->update([
            'subtotal' => $activeSubtotal,
            'tax' => $tax,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'steadfast_cod_charger' => Order::steadfastCodChargeFor($order->payment_method, $total),
        ]);
    }

    private function restockOrderItemQuantity(OrderItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

        if ($product) {
            $product->increment('quantity', $quantity);
            $product->refresh();

            if ($product->quantity > 0 && $product->stock_status !== 'in_stock') {
                $product->update(['stock_status' => 'in_stock']);
            }
        }

        if ($item->product_variant_id) {
            ProductVariant::whereKey($item->product_variant_id)
                ->lockForUpdate()
                ->first()
                ?->increment('quantity', $quantity);
        }
    }

    private function orderFormData(?Order $order = null, ?string $storeRoute = null, ?string $backRoute = null): array
    {
        $selectedProductIds = $order
            ? $order->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->unique()
            : collect();
        $selectedVariantIds = $order
            ? $order->items->pluck('product_variant_id')->filter()->map(fn ($id) => (int) $id)->unique()
            : collect();

        $products = Product::with(['variants' => function ($query) use ($selectedVariantIds) {
                $query->where(function ($variantQuery) use ($selectedVariantIds) {
                        $variantQuery->where('is_active', true);

                        if ($selectedVariantIds->isNotEmpty()) {
                            $variantQuery->orWhereIn('id', $selectedVariantIds);
                        }
                    })
                    ->with(['values.attribute', 'values.value'])
                    ->orderBy('sort_order')
                    ->orderBy('id');
            }])
            ->where(function ($query) use ($selectedProductIds) {
                $query->where('status', 'published');

                if ($selectedProductIds->isNotEmpty()) {
                    $query->orWhereIn('id', $selectedProductIds);
                }
            })
            ->orderBy('name')
            ->get();

        $deliveryAreaGroups = DeliveryArea::query()
            ->active()
            ->orderBy('district_name')
            ->orderBy('name')
            ->get(['id', 'name', 'district_id', 'district_name'])
            ->groupBy('district_name')
            ->map(function ($areas, $districtName) {
                return [
                    'district_id' => $areas->first()?->district_id,
                    'district_name' => $districtName,
                    'areas' => $areas->map(fn (DeliveryArea $area) => [
                        'id' => $area->id,
                        'name' => $area->name,
                    ])->values(),
                ];
            })
            ->values();

        return [
            'order' => $order,
            'isEdit' => (bool) $order,
            'products' => $products,
            'deliveryAreaGroups' => $deliveryAreaGroups,
            'deliveryCharges' => $this->deliveryCharges(),
            'sourceOptions' => $this->sourceOptions(),
            'storeRoute' => $storeRoute,
            'backRoute' => $backRoute,
            'defaultOrderItems' => $this->defaultOrderItems($order),
        ];
    }

    private function defaultOrderItems(?Order $order): array
    {
        if (! $order) {
            return [
                [
                    'product_id' => '',
                    'product_variant_id' => '',
                    'quantity' => 1,
                ],
            ];
        }

        return $order->items
            ->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'cancelled_quantity' => $item->cancelledQuantity(),
            ])
            ->values()
            ->all();
    }

    private function deliveryCharges(): array
    {
        return [
            'inside_dhaka' => (float) Setting::get('delivery_charge_inside_dhaka', 80),
            'outside_dhaka' => (float) Setting::get('delivery_charge_outside_dhaka', 150),
        ];
    }

    private function sourceOptions(): array
    {
        return [
            'facebook' => 'Facebook',
            'whatsapp' => 'WhatsApp',
            'ad' => 'Ad',
            'phone' => 'Phone',
            'website' => 'Website',
            'other' => 'Other',
        ];
    }

    private function shouldScopeToCreatedOrders(): bool
    {
        $user = auth()->user();

        return (bool) ($user
            && $user->user_type === 'staff'
            && ! $user->hasRole('Super Admin')
            && (
                $user->hasAnyRole(['Moderator', 'Manager'])
                || $user->canAny([
                    'moderator-order-management.show',
                    'moderator-order-management.create',
                ])
            ));
    }

    private function authorizeOrderAccess(Order $order): void
    {
        if ($this->shouldScopeToCreatedOrders() && (int) $order->created_by_id !== (int) auth()->id()) {
            abort(403, 'You do not have permission to access this order.');
        }
    }

    private function statusRouteMap(): array
    {
        return [
            'pending' => [
                'value' => 'pending',
                'title' => 'Pending Orders',
                'route' => 'orders.pending',
            ],
            'confirmed' => [
                'value' => 'confirmed',
                'title' => 'Confirmed Orders',
                'route' => 'orders.confirmed',
            ],
            'packaging' => [
                'value' => 'processing',
                'title' => 'Packaging Orders',
                'route' => 'orders.packaging',
            ],
            'processing' => [
                'value' => 'processing',
                'title' => 'Packaging Orders',
                'route' => 'orders.processing',
            ],
            'shipped' => [
                'value' => 'shipped',
                'title' => 'Shipped Orders',
                'route' => 'orders.shipped',
            ],
            'delivered' => [
                'value' => 'delivered',
                'title' => 'Delivered Orders',
                'route' => 'orders.delivered',
            ],
            'cancelled' => [
                'value' => 'cancelled',
                'title' => 'Cancelled Orders',
                'route' => 'orders.cancelled',
            ],
        ];
    }

    private function orderStatusDetails(string $status): array
    {
        return match ($status) {
            'confirmed' => [
                'status' => 'Order Confirmed',
                'description' => 'Your order is confirmed',
            ],
            'processing' => [
                'status' => 'Order Processing',
                'description' => 'Your order is processing',
            ],
            'shipped' => [
                'status' => 'Order Shipped',
                'description' => 'Your order is shipped',
            ],
            'delivered' => [
                'status' => 'Order Delivered',
                'description' => 'Your order is delivered',
            ],
            'cancelled' => [
                'status' => 'Order Cancelled',
                'description' => 'Your order is cancelled',
            ],
            default => [
                'status' => 'Order Pending',
                'description' => 'Your order is pending',
            ],
        };
    }

    private function orderStatusResponse(Request $request, bool $success, string $message, Order $order, int $statusCode = 200)
    {
        $redirectUrl = $success ? $this->statusListUrl($order->order_status, $request->input('return_url')) : null;

        if ($request->expectsJson() || $request->ajax()) {
            if ($success) {
                flash_message($message);
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'status' => $order->order_status,
                'status_label' => $this->statusLabel($order->order_status),
                'redirect_url' => $redirectUrl,
                'steadfast' => [
                    'consignment_id' => $order->steadfast_consignment_id,
                    'tracking_code' => $order->steadfast_tracking_code,
                    'status' => $order->steadfast_status,
                ],
            ], $success ? 200 : $statusCode);
        }

        if ($success) {
            return redirect($redirectUrl)->with('success', $message);
        }

        return redirect()->back()->with('error', $message);
    }

    private function statusLabel(string $status): string
    {
        return $status === 'processing'
            ? 'Packaging'
            : ucfirst(str_replace('_', ' ', $status));
    }

    private function nextOrderStatus(string $status): ?string
    {
        return [
            'pending' => 'confirmed',
            'confirmed' => 'processing',
            'processing' => 'shipped',
            'shipped' => 'delivered',
        ][$status] ?? null;
    }

    private function canCancelOrderStatus(string $status): bool
    {
        return in_array($status, ['pending', 'confirmed', 'processing', 'shipped'], true);
    }

    private function isSuccessfulSteadfastBulkItem(mixed $response): bool
    {
        return is_array($response)
            && filled(data_get($response, 'consignment_id'))
            && filled(data_get($response, 'tracking_code'))
            && strtolower((string) data_get($response, 'status')) === 'success';
    }

    private function invalidStatusTransitionMessage(string $currentStatus): string
    {
        $nextStatus = $this->nextOrderStatus($currentStatus);

        if (! $nextStatus) {
            return 'This order has no further status update available.';
        }

        return 'Order status must be updated step by step. Next allowed status is '.$this->statusLabel($nextStatus).'.';
    }

    private function statusListUrl(string $status, ?string $fallbackUrl = null): string
    {
        $routeMap = [
            'pending' => ['route' => 'orders.pending', 'permission' => 'orders.pending'],
            'confirmed' => ['route' => 'orders.confirmed', 'permission' => 'orders.confirmed'],
            'processing' => ['route' => 'orders.packaging', 'permission' => 'orders.packaging'],
            'shipped' => ['route' => 'orders.shipped', 'permission' => 'orders.shipped'],
            'delivered' => ['route' => 'orders.delivered', 'permission' => 'orders.delivered'],
            'cancelled' => ['route' => 'orders.cancelled', 'permission' => 'orders.cancelled'],
        ];

        $user = auth()->user();
        $target = $routeMap[$status] ?? null;

        if ($target && $user?->can($target['permission'])) {
            return route($target['route']);
        }

        $fallbackUrl = $this->safeBackendOrdersReturnUrl($fallbackUrl);

        if ($fallbackUrl) {
            return $fallbackUrl;
        }

        if ($user?->can('orders.all')) {
            return route('orders.index');
        }

        foreach ($routeMap as $route) {
            if ($user?->can($route['permission'])) {
                return route($route['route']);
            }
        }

        return route('dashboard');
    }

    private function safeBackendOrdersReturnUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! $path || ! str_starts_with($path, '/backend/orders')) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host && $host !== request()->getHost()) {
            return null;
        }

        $permissions = [
            '/backend/orders' => 'orders.all',
            '/backend/orders/pending' => 'orders.pending',
            '/backend/orders/confirmed' => 'orders.confirmed',
            '/backend/orders/packaging' => 'orders.packaging',
            '/backend/orders/processing' => 'orders.packaging',
            '/backend/orders/shipped' => 'orders.shipped',
            '/backend/orders/delivered' => 'orders.delivered',
            '/backend/orders/cancelled' => 'orders.cancelled',
        ];

        $permission = $permissions[rtrim($path, '/')] ?? null;

        if (! $permission || ! auth()->user()?->can($permission)) {
            return null;
        }

        return $url;
    }
}
