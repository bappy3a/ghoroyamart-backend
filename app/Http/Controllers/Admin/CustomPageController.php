<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomPageRequest;
use App\Models\CustomPage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CustomPageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $customPages = CustomPage::orderByDesc('created_at')
            ->paginate(15);

        return view('admin.custom-pages.index', compact('customPages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.custom-pages.create', [
            'customPage' => new CustomPage(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomPageRequest $request): RedirectResponse
    {
        CustomPage::create($this->payloadFrom($request));

        flash_message('Custom page created successfully!');

        return redirect()->route('custom-pages.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomPage $customPage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomPage $customPage): View
    {
        return view('admin.custom-pages.edit', compact('customPage'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomPageRequest $request, CustomPage $customPage): RedirectResponse
    {
        $customPage->update($this->payloadFrom($request));

        flash_message('Custom page updated successfully!');

        return redirect()->route('custom-pages.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomPage $customPage): RedirectResponse
    {
        $customPage->delete();

        flash_message('Custom page deleted successfully!');

        return redirect()->route('custom-pages.index');
    }

    /**
     * Prepare payload from request data
     */
    protected function payloadFrom(CustomPageRequest $request): array
    {
        $data = $request->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return $data;
    }
}
