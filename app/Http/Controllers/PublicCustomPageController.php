<?php

namespace App\Http\Controllers;

use App\Models\CustomPage;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class PublicCustomPageController extends Controller
{
    /**
     * Display a custom page on the public website.
     */
    public function show(string $slug): View
    {
        $page = CustomPage::query()
            ->where('slug', $slug)
            ->firstOrFail();
        $siteName = trim((string) Setting::get('site_name', config('app.name')));

        return view('public.custom-pages.show', [
            'page' => $page,
            'siteName' => $siteName !== '' ? $siteName : (string) config('app.name'),
            'logoUrl' => $this->logoUrl(),
            'footerText' => Setting::get('footer_text'),
            'contactEmail' => Setting::get('contact_email_1'),
            'contactPhone' => Setting::get('contact_phone_1'),
            'enContent' => rewrite_api_assets_in_html($page->en_content),
            'bnContent' => rewrite_api_assets_in_html($page->bn_content),
        ]);
    }

    /**
     * Use the configured logo when it exists and fall back to the application logo.
     */
    private function logoUrl(): string
    {
        $logo = trim((string) Setting::get('header_logo', 'logo.png'));

        return api_asset($logo !== '' ? $logo : 'logo.png');
    }
}
