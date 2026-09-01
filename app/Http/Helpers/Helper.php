<?php

// highlights the selected navigation on admin panel

use App\Models\Product;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image;

if (! function_exists('active_menu')) {
    function active_menu(array $routes, string $output = 'active current-page'): ?string
    {
        foreach ($routes as $route) {
            if (Route::is($route)) {
                return $output;
            }
        }

        return null;
    }
}

if (! function_exists('active_customer_menu')) {
    function active_customer_menu(array $routes, string $output = 'active'): ?string
    {
        foreach ($routes as $route) {
            if (Route::is($route)) {
                return $output;
            }
        }

        return null;
    }
}

if (! function_exists('upload_webp_image')) {
    /**
     * Store an optimized image on the default filesystem disk.
     *
     * WebP is preferred, but the original encoding is kept when it is smaller.
     */
    function upload_webp_image(UploadedFile $file, string $relativePath = 'uploads', int $quality = 80, bool $watermark = false): string
    {
        $diskName = (string) config('filesystems.default');
        $relativePath = trim($relativePath, '/');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'agonito_webp_');

        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary image file.');
        }

        try {
            $sourcePath = $file->getRealPath();
            $sourceSize = max(1, (int) ($file->getSize() ?: filesize($sourcePath)));
            $currentQuality = min(75, max(1, $quality));
            $minimumQuality = min(55, $currentQuality);
            $extension = 'webp';
            $contentType = 'image/webp';
            $image = Image::load($sourcePath);

            if ($image->image() instanceof Imagick) {
                foreach ($image->image() as $frame) {
                    $frame->stripImage();
                    $frame->setOption('webp:method', '4');
                    $frame->setOption('webp:use-sharp-yuv', 'true');
                }
            }

            // if ($watermark) {
            //     $watermarkPath = public_path('images/watermark_new.png');
            //     if (File::exists($watermarkPath)) {
            //         $image->watermark($watermarkPath, AlignPosition::Center);
            //     }
            // }

            do {
                $image->format('webp')
                    ->quality($currentQuality)
                    ->save($temporaryPath);

                clearstatcache(true, $temporaryPath);
                $convertedSize = (int) filesize($temporaryPath);

                if ($convertedSize < $sourceSize || $currentQuality === $minimumQuality) {
                    break;
                }

                $currentQuality = max($minimumQuality, $currentQuality - 20);
            } while (true);

            if ($convertedSize >= $sourceSize) {
                $sourceFormat = match ($file->getMimeType()) {
                    'image/jpeg' => ['jpg', 'image/jpeg'],
                    'image/png' => ['png', 'image/png'],
                    'image/gif' => ['gif', 'image/gif'],
                    'image/webp' => ['webp', 'image/webp'],
                    default => null,
                };

                if ($sourceFormat !== null) {
                    if (! copy($sourcePath, $temporaryPath)) {
                        throw new RuntimeException('Unable to preserve the smaller source image.');
                    }

                    [$extension, $contentType] = $sourceFormat;
                }
            }

            $filename = Str::uuid()->toString().'.'.$extension;
            $storagePath = ($relativePath !== '' ? $relativePath.'/' : '').$filename;

            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Unable to read the converted image.');
            }

            try {
                $disk = Storage::disk($diskName);
                $stored = $disk->put($storagePath, $stream, [
                    'ContentType' => $contentType,
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ]);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new RuntimeException("Unable to store image on the [{$diskName}] disk.");
            }

            return $storagePath;
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }
}

if (! function_exists('uploaded_storage_path')) {
    /**
     * Normalize a stored image value to a disk-relative path.
     */
    function uploaded_storage_path(?string $location): ?string
    {
        $location = trim((string) $location);

        if ($location === '' || Str::startsWith($location, 'data:')) {
            return null;
        }

        $diskName = (string) config('filesystems.default');
        $diskConfig = config("filesystems.disks.{$diskName}", []);
        $diskUrl = rtrim((string) ($diskConfig['url'] ?? ''), '/');
        $root = trim((string) ($diskConfig['root'] ?? ''), '/');

        $stripRoot = static function (string $path) use ($root): string {
            $path = ltrim($path, '/');

            if ($root !== '' && (str_starts_with($path, $root.'/') || $path === $root)) {
                $path = ltrim(substr($path, strlen($root)), '/');
            }

            return $path;
        };

        if (Str::startsWith($location, ['http://', 'https://', '//'])) {
            $normalized = Str::startsWith($location, '//') ? 'https:'.$location : $location;

            if ($diskUrl !== '' && str_starts_with($normalized, $diskUrl.'/')) {
                $path = $stripRoot(rawurldecode(substr($normalized, strlen($diskUrl) + 1)));

                return $path !== '' ? $path : null;
            }

            $urlPath = rawurldecode(ltrim((string) (parse_url($normalized, PHP_URL_PATH) ?: ''), '/'));

            // Legacy local/app URLs such as http://agonito.test/uploads/... or
            // http://localhost:8000/uploads/... should resolve to the cloud path.
            if (str_starts_with($urlPath, 'uploads/') || ($root !== '' && str_starts_with($urlPath, $root.'/uploads/'))) {
                $path = $stripRoot($urlPath);

                return $path !== '' ? $path : null;
            }

            return null;
        }

        $path = $stripRoot($location);

        return $path !== '' ? $path : null;
    }
}

if (! function_exists('rewrite_api_assets_in_html')) {
    /**
     * Replace uploaded-file src/href values inside HTML with api_asset() URLs.
     */
    function rewrite_api_assets_in_html(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $rewritten = preg_replace_callback(
            '/(\s(?:src|href)\s*=\s*)([\'"])([^\'"]+)\2/i',
            static function (array $matches): string {
                $url = html_entity_decode($matches[3], ENT_QUOTES);
                $path = uploaded_storage_path($url) ?? ltrim($url, '/');

                if (! str_starts_with($path, 'uploads/')) {
                    return $matches[0];
                }

                $assetUrl = api_asset($url);

                return $assetUrl
                    ? $matches[1].$matches[2].htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8').$matches[2]
                    : $matches[0];
            },
            $html
        );

        return is_string($rewritten) ? $rewritten : $html;
    }
}

if (! function_exists('delete_uploaded_file')) {
    /**
     * Delete an image created by upload_webp_image from cloud or legacy local storage.
     */
    function delete_uploaded_file(?string $location): bool
    {
        $path = uploaded_storage_path($location);

        if ($path === null || $path === '') {
            return false;
        }

        $diskName = (string) config('filesystems.default');
        $diskConfig = config("filesystems.disks.{$diskName}", []);
        $deleted = false;

        if (($diskConfig['driver'] ?? null) !== 'local') {
            $deleted = Storage::disk($diskName)->delete($path);
        }

        $absolutePath = public_path($path);

        if (is_file($absolutePath)) {
            $deleted = File::delete($absolutePath) || $deleted;
        }

        return $deleted;
    }
}
// flash message
if (! function_exists('flash_message')) {
    function flash_message(string $message, string $type = 'success')
    {
        flash()->use('theme.ios')->option('timeout', 5000)->option('position', 'top-center')->$type($message);
    }
}

// settings helper
if (! function_exists('setting')) {
    /**
     * Get a setting value by key
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if setting doesn't exist
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('frontend_menu_defaults')) {
    function frontend_menu_defaults(): array
    {
        return [
            ['id' => 'home', 'parent_id' => null, 'label' => 'Home', 'url' => route('home'), 'target' => '_self', 'is_active' => true, 'sort_order' => 1, 'type' => 'core'],
            ['id' => 'products', 'parent_id' => null, 'label' => 'Products', 'url' => route('products'), 'target' => '_self', 'is_active' => true, 'sort_order' => 2, 'type' => 'core'],
            ['id' => 'women', 'parent_id' => null, 'label' => 'Women', 'url' => route('products').'?category=women', 'target' => '_self', 'is_active' => true, 'sort_order' => 3, 'type' => 'custom'],
            ['id' => 'mens', 'parent_id' => null, 'label' => 'Men\'s', 'url' => route('products').'?category=men', 'target' => '_self', 'is_active' => true, 'sort_order' => 4, 'type' => 'custom'],
            ['id' => 'kids', 'parent_id' => null, 'label' => 'Kids', 'url' => route('products').'?category=kids', 'target' => '_self', 'is_active' => true, 'sort_order' => 5, 'type' => 'custom'],
            ['id' => 'blog', 'parent_id' => null, 'label' => 'Blog', 'url' => route('blog.index'), 'target' => '_self', 'is_active' => true, 'sort_order' => 6, 'type' => 'core'],
            ['id' => 'pages', 'parent_id' => null, 'label' => 'Pages', 'url' => '#', 'target' => '_self', 'is_active' => true, 'sort_order' => 7, 'type' => 'custom'],
            ['id' => 'about', 'parent_id' => 'pages', 'label' => 'About', 'url' => route('about.us'), 'target' => '_self', 'is_active' => true, 'sort_order' => 8, 'type' => 'core'],
            ['id' => 'contact', 'parent_id' => 'pages', 'label' => 'Contact', 'url' => route('contact.us'), 'target' => '_self', 'is_active' => true, 'sort_order' => 9, 'type' => 'core'],
            ['id' => 'faq', 'parent_id' => 'pages', 'label' => 'FAQ', 'url' => route('faq'), 'target' => '_self', 'is_active' => true, 'sort_order' => 10, 'type' => 'core'],
            ['id' => 'reviews', 'parent_id' => 'pages', 'label' => 'Reviews', 'url' => route('reviews'), 'target' => '_self', 'is_active' => true, 'sort_order' => 11, 'type' => 'core'],
            ['id' => 'wishlist', 'parent_id' => 'pages', 'label' => 'Wishlist', 'url' => route('customer.wishlist'), 'target' => '_self', 'is_active' => true, 'sort_order' => 12, 'type' => 'core'],
            ['id' => 'cart', 'parent_id' => 'pages', 'label' => 'Cart', 'url' => route('cart.index'), 'target' => '_self', 'is_active' => true, 'sort_order' => 13, 'type' => 'core'],
            ['id' => 'checkout', 'parent_id' => 'pages', 'label' => 'Checkout', 'url' => route('checkout.index'), 'target' => '_self', 'is_active' => true, 'sort_order' => 14, 'type' => 'core'],
        ];
    }
}

if (! function_exists('frontend_menu_url')) {
    function frontend_menu_url(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '#';
        }

        if (
            str_starts_with($url, '#') ||
            str_starts_with($url, '/') ||
            str_starts_with($url, 'http://') ||
            str_starts_with($url, 'https://') ||
            str_starts_with($url, 'mailto:') ||
            str_starts_with($url, 'tel:')
        ) {
            return $url;
        }

        return url($url);
    }
}

if (! function_exists('frontend_menu_items')) {
    function frontend_menu_items(): array
    {
        $items = json_decode(setting('frontend_menu_items', ''), true);

        if (! is_array($items)) {
            $items = frontend_menu_defaults();
        }

        $flatItems = frontend_menu_flat_items($items);

        return frontend_menu_tree($flatItems);
    }
}

if (! function_exists('frontend_menu_flat_items')) {
    function frontend_menu_flat_items(array $items, ?string $parentId = null, int &$fallbackOrder = 1): array
    {
        $flatItems = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (string) ($item['id'] ?? 'menu-'.$fallbackOrder);
            $flatItems[] = [
                'id' => $id,
                'parent_id' => $item['parent_id'] ?? $parentId,
                'label' => trim((string) ($item['label'] ?? '')),
                'url' => frontend_menu_url($item['url'] ?? '#'),
                'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'is_active' => filter_var($item['is_active'] ?? true, FILTER_VALIDATE_BOOL),
                'sort_order' => (int) ($item['sort_order'] ?? $fallbackOrder),
                'type' => $item['type'] ?? 'custom',
            ];

            $fallbackOrder++;

            if (! empty($item['children']) && is_array($item['children'])) {
                $flatItems = array_merge($flatItems, frontend_menu_flat_items($item['children'], $id, $fallbackOrder));
            }
        }

        return $flatItems;
    }
}

if (! function_exists('frontend_menu_tree')) {
    function frontend_menu_tree(array $flatItems, ?string $parentId = null): array
    {
        return collect($flatItems)
            ->filter(fn ($item) => ($item['parent_id'] ?? null) === $parentId && ($item['label'] ?? '') !== '' && ($item['is_active'] ?? true))
            ->sortBy('sort_order')
            ->map(function ($item) use ($flatItems) {
                $item['children'] = frontend_menu_tree($flatItems, $item['id']);
                unset($item['parent_id'], $item['sort_order'], $item['is_active'], $item['type']);

                return $item;
            })
            ->values()
            ->all();
    }
}
// send sms helper
if (! function_exists('smsSend')) {
    function smsSend($message, $number, $code = '880', $unicode = false)
    {
        // Check throttle limits before sending
        $ip = request()->ip();

        // Throttle: Max 5 SMS per hour per mobile number
        $mobileNo = ($code == '880') ? '880'.$number : (($code == '88') ? '88'.$number : $number);
        $mobileThrottle = SmsLog::where('mobile_no', $mobileNo)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($mobileThrottle >= 5) {
            throw new Exception('SMS rate limit exceeded for this mobile number. Please try again after an hour.');
        }

        // Throttle: Max 10 SMS per hour per IP address
        $ipThrottle = SmsLog::where('ip', $ip)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($ipThrottle >= 10) {
            throw new Exception('SMS rate limit exceeded for this IP address. Please try again after an hour.');
        }

        if ($code == '880') {
            return minsms($mobileNo, $message, $unicode);
        } elseif ($code == '88') {
            return minsms($mobileNo, $message, $unicode);
        } else {
            return minsms($number, $message, $unicode);
        }
    }
}

if (! function_exists('minsms')) {
    function minsms($mobileNo, $message, $unicode, ?string $loggedMobileNo = null)
    {

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->post('https://api.mimsms.com/api/V2/SMS', [
                'apiKey' => config('services.mimsms.api_key'),
                'userName' => config('services.mimsms.username'),
                'senderName' => config('services.mimsms.sender_id'),
                'transactionType' => 'T',
                'message' => $message,
                'mobileNumber' => $mobileNo,
            ]);
        if ($response->successful()) {
            SmsLog::query()->create([
                'mobile_no' => $loggedMobileNo ?? $mobileNo,
                'ip' => request()->ip(),
                'message' => $message,
                'status' => $response->json('status'),
            ]);

            return true;
        }

        Log::error('Minsms API Error: '.$response->body());

        return false;
    }
}



if (! function_exists('bulksmsbd')) {
    function bulksmsbd($number, $message, $unicode = false)
    {
        $url = 'http://bulksmsbd.net/api/smsapi';
        $api_key = 'RpQhqajEgimpJ25bNXEz';
        $senderid = '8809617625742';
        $number = $number;
        $message = $message;

        $data = [
            'api_key' => $api_key,
            'senderid' => $senderid,
            'number' => $number,
            'message' => $message,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}

if (! function_exists('whatsappSend')) {
    function whatsappSend($number, $message, $code = '+880')
    {
        $number = whatsappNumber($number, $code);

        return whatsappCloudApi($number, $message);
    }
}

if (! function_exists('whatsappCloudApi')) {
    function whatsappCloudApi($number, $message)
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $accessToken = config('services.whatsapp.access_token');
        $graphVersion = config('services.whatsapp.graph_version', 'v25.0');
        $timeout = (int) config('services.whatsapp.timeout', 15);

        if (empty($phoneNumberId) || empty($accessToken)) {
            throw new Exception('WhatsApp API credentials are missing. Set WHATSAPP_PHONE_NUMBER_ID and WHATSAPP_ACCESS_TOKEN.');
        }

        $response = Http::timeout($timeout)
            ->withToken($accessToken)
            ->post("https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $number,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('WhatsApp message failed: '.$response->body());
        }

        return $response->json();
    }
}

if (! function_exists('whatsappNumber')) {
    function whatsappNumber($number, $code = '+880')
    {
        $number = preg_replace('/\D+/', '', (string) $number);
        $countryCode = preg_replace('/\D+/', '', (string) $code);

        if (Str::startsWith($number, '00')) {
            return ltrim($number, '0');
        }

        if ($countryCode && Str::startsWith($number, $countryCode)) {
            return $number;
        }

        if ($countryCode === '880') {
            return '880'.ltrim($number, '0');
        }

        return $countryCode.ltrim($number, '0');
    }
}

if (! function_exists('api_asset')) {
    function api_asset($url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return local_asset($url);
    }
}

if (! function_exists('media_disk_name')) {
    /**
     * Disk used for uploaded media. Prefers the configured S3/cloud disk even
     * when the default filesystem disk is still "local".
     */
    function media_disk_name(): string
    {
        $default = (string) config('filesystems.default');

        if ((config("filesystems.disks.{$default}.driver") ?? 'local') !== 'local') {
            return $default;
        }

        $s3 = config('filesystems.disks.s3', []);

        if (($s3['driver'] ?? null) === 's3' && filled($s3['url'] ?? $s3['bucket'] ?? null)) {
            return 's3';
        }

        return $default;
    }
}

if (! function_exists('is_media_path')) {
    /**
     * True for uploaded/public files; false for icon component names like "Watch".
     */
    function is_media_path(string $path): bool
    {
        if (str_contains($path, '/') || str_contains($path, '\\')) {
            return true;
        }

        return (bool) preg_match('/\.[A-Za-z0-9]{2,5}$/', $path);
    }
}

if (! function_exists('local_asset')) {
    function local_asset($url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return asset('');
        }

        if (Str::startsWith($url, 'data:')) {
            return $url;
        }

        $path = uploaded_storage_path($url);

        if ($path === null) {
            return $url;
        }

        if (! is_media_path($path)) {
            return $url;
        }

        $diskName = media_disk_name();
        $diskConfig = config("filesystems.disks.{$diskName}", []);
        $isCloudDisk = ($diskConfig['driver'] ?? null) !== 'local';
        $isUpload = str_starts_with($path, 'uploads/');

        // Uploaded files live on Laravel Cloud even when APP_URL is agonito.store
        // or a leftover copy still exists under public/uploads.
        if ($isCloudDisk && $isUpload) {
            return Storage::disk($diskName)->url($path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        if ($isCloudDisk) {
            return Storage::disk($diskName)->url($path);
        }

        return asset($path);
    }
}

/**
 * Build color/size style variant groups for storefront product cards.
 *
 * @param  Product|Model  $product
 * @return array<int, array{name: string, slug: ?string, type: string, options: array<int, array{value: string, stock: int, delta: float, image: ?string, hex: ?string}>}>
 */
if (! function_exists('product_variant_groups')) {
    function product_variant_groups($product): array
    {
        if (! $product->relationLoaded('variants')) {
            $product->load([
                'variants' => fn ($q) => $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['values.attribute', 'values.value']),
            ]);
        }

        $normalizeHex = static function (?string $colorCode): ?string {
            if (! is_string($colorCode)) {
                return null;
            }
            $colorCode = trim($colorCode);
            if ($colorCode === '') {
                return null;
            }
            if ($colorCode[0] !== '#') {
                $colorCode = '#'.$colorCode;
            }
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $colorCode)) {
                return null;
            }

            return strtoupper($colorCode);
        };

        $variantGroups = [];

        foreach ($product->variants as $variant) {
            foreach ($variant->values as $row) {
                $attrName = $row->attribute?->name;
                $attrSlug = $row->attribute?->slug;
                $value = $row->value?->value;
                if (! $attrName || ! $value) {
                    continue;
                }

                $key = $attrSlug ?: $attrName;
                if (! isset($variantGroups[$key])) {
                    $type = str_contains(strtolower($attrName), 'color')
                        || str_contains(strtolower((string) $attrSlug), 'color')
                        ? 'color'
                        : (str_contains(strtolower($attrName), 'size')
                            || str_contains(strtolower((string) $attrSlug), 'size')
                            ? 'size'
                            : 'option');

                    $variantGroups[$key] = [
                        'name' => $attrName,
                        'slug' => $attrSlug,
                        'type' => $type,
                        'options' => [],
                    ];
                }

                if (! isset($variantGroups[$key]['options'][$value])) {
                    $variantGroups[$key]['options'][$value] = [
                        'value' => $value,
                        'stock' => 0,
                        'delta' => 0.0,
                        'image' => null,
                        'hex' => $normalizeHex($row->value?->color_code),
                    ];
                }

                $variantGroups[$key]['options'][$value]['stock'] += (int) $variant->quantity;

                $basePrice = (float) $product->price;
                $selling = (float) ($variant->selling_price ?? $basePrice);
                $delta = round($selling - $basePrice, 2);
                if (abs($delta) > abs((float) $variantGroups[$key]['options'][$value]['delta'])) {
                    $variantGroups[$key]['options'][$value]['delta'] = $delta;
                }

                if ($variant->image && ! $variantGroups[$key]['options'][$value]['image']) {
                    $variantGroups[$key]['options'][$value]['image'] = api_asset($variant->image);
                }

                if (empty($variantGroups[$key]['options'][$value]['hex'])) {
                    $hex = $normalizeHex($row->value?->color_code);
                    if ($hex) {
                        $variantGroups[$key]['options'][$value]['hex'] = $hex;
                    }
                }
            }
        }

        return array_values(array_map(function (array $group) {
            $group['options'] = array_values($group['options']);

            return $group;
        }, $variantGroups));
    }
}

function username_generator($username, $table = null): string
{
    $new_slug = Str::slug($username);

    $originalSlug = $new_slug;
    $i = 1;
    while (User::where('username', $new_slug)->exists()) {
        $new_slug = $originalSlug.'-'.$i++;
    }
    if (empty($new_slug)) {
        return str()->random();
    }

    return $new_slug;
}
if (! function_exists('send_verification_code')) {
    function send_verification_code($user)
    {
        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'verification_code' => $code,
        ]);

        // Local/dev: write OTP to the log file instead of sending SMS.
        if (app()->environment('local')) {
            Log::info("Auth OTP for {$user->phone}: {$code}");
            return true;
        }
        $message = "Your verification code is: {$code}. Please use this code to verify your phone number.";
        $phoneNumber = ltrim((string) $user->phone, '0');
        smsSend($message, $phoneNumber);

        return true;
    }
}
