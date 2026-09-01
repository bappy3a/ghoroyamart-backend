<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AboutPageSettingRequest;
use App\Models\AboutPageSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AboutPageSettingController extends Controller
{
    public function index(): View
    {
        $aboutPageSetting = AboutPageSetting::first();

        return view('admin.about-page-settings.index', compact('aboutPageSetting'));
    }

    public function create(): View
    {
        return view('admin.about-page-settings.create', [
            'aboutPageSetting' => new AboutPageSetting(),
        ]);
    }

    public function store(AboutPageSettingRequest $request): RedirectResponse
    {
        AboutPageSetting::create($this->payloadFrom($request));

        flash_message('About page settings created successfully!');

        return redirect()->route('about-page-settings.index');
    }

    public function edit(AboutPageSetting $aboutPageSetting): View
    {
        return view('admin.about-page-settings.edit', compact('aboutPageSetting'));
    }

    public function update(AboutPageSettingRequest $request, AboutPageSetting $aboutPageSetting): RedirectResponse
    {
        $aboutPageSetting->update($this->payloadFrom($request, $aboutPageSetting));

        flash_message('About page settings updated successfully!');

        return redirect()->route('about-page-settings.index');
    }

    public function destroy(AboutPageSetting $aboutPageSetting): RedirectResponse
    {
        $aboutPageSetting->delete();

        flash_message('About page settings deleted successfully!');

        return redirect()->route('about-page-settings.index');
    }

    protected function payloadFrom(AboutPageSettingRequest $request, ?AboutPageSetting $aboutPageSetting = null): array
    {
        $data = $request->validated();
        foreach (['cover_image', 'section_one_image', 'section_two_image'] as $imageField) {
            if ($request->hasFile($imageField)) {
                delete_uploaded_file($aboutPageSetting?->{$imageField});
                $data[$imageField] = upload_webp_image($request->file($imageField), 'uploads/about-page', 80);
            } else {
                unset($data[$imageField]);
            }
        }

        return $data;
    }
}
