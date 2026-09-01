<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        return view('admin.faqs.create', [
            'faq' => new Faq(),
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::create($this->payloadFrom($request));

        flash_message('FAQ created successfully!');

        return redirect()->route('faqs.index');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->payloadFrom($request));

        flash_message('FAQ updated successfully!');

        return redirect()->route('faqs.index');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        flash_message('FAQ deleted successfully!');

        return redirect()->route('faqs.index');
    }

    protected function payloadFrom(FaqRequest $request): array
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
