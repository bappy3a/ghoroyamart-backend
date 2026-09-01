<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::orderByDesc('status')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.brands.create', [
            'brand' => new Brand(),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        Brand::create($this->payloadFrom($request));

        HomeController::clearCache();
        flash_message('Brand created successfully!');

        return redirect()->route('brands.index');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($this->payloadFrom($request, $brand));

        HomeController::clearCache();
        flash_message('Brand updated successfully!');

        return redirect()->route('brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        HomeController::clearCache();
        flash_message('Brand deleted successfully!');

        return redirect()->route('brands.index');
    }

    protected function payloadFrom(BrandRequest $request, ?Brand $brand = null): array
    {
        $data = $request->validated();

        $data['status'] = $request->boolean('status', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($request->hasFile('logo')) {
            $data['logo'] = upload_webp_image($request->file('logo'), 'uploads/brands', 75);
        } else {
            unset($data['logo']);
        }

        return $data;
    }
}


