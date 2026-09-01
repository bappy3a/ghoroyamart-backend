<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $coupons = Coupon::latest()
            ->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $products = Product::where('status', 'published')
            ->orderBy('name')
            ->get();

        return view('admin.coupons.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CouponRequest $request): RedirectResponse
    {
        Coupon::create($this->payloadFrom($request));

        flash_message('Coupon created successfully!');

        return redirect()->route('coupons.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Coupon $coupon): View
    {
        $products = Product::where('status', 'published')
            ->orderBy('name')
            ->get();

        return view('admin.coupons.edit', compact('coupon', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->payloadFrom($request, $coupon));

        flash_message('Coupon updated successfully!');

        return redirect()->route('coupons.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        flash_message('Coupon deleted successfully!');

        return redirect()->route('coupons.index');
    }

    /**
     * Prepare payload from request data
     */
    protected function payloadFrom(CouponRequest $request, ?Coupon $coupon = null): array
    {
        $data = $request->validated();

        // Handle product_ids for product_wise type
        if ($request->input('type') === 'product_wise' && $request->has('product_ids')) {
            $data['product_ids'] = $request->input('product_ids');
        } else {
            $data['product_ids'] = null;
        }

        // Handle minimum_order_amount for order_based type
        if ($request->input('type') === 'order_based') {
            $data['minimum_order_amount'] = $request->input('minimum_order_amount', 0);
        } else {
            $data['minimum_order_amount'] = null;
        }

        // Set is_active
        $data['is_active'] = $request->boolean('is_active', true);

        // Ensure discount_value is valid for percentage type
        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            $data['discount_value'] = 100;
        }

        return $data;
    }
}
