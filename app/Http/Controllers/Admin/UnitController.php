<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::orderBy('name')->paginate(20);

        return view('admin.units.index', compact('units'));
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        flash_message('Unit created successfully!');

        return redirect()->route('units.index');
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        flash_message('Unit updated successfully!');

        return redirect()->route('units.index');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        flash_message('Unit deleted successfully!');

        return redirect()->route('units.index');
    }
}


