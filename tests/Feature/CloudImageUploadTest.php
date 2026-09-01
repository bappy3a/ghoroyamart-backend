<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudImageUploadTest extends TestCase
{
    public function test_the_smaller_original_format_is_kept_when_webp_would_be_larger(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.key' => 'test-access-key',
            'filesystems.disks.s3.secret' => 'test-secret-key',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
        ]);

        $disk = Storage::fake('s3', ['url' => 'https://cdn.example.test/agonito']);
        $sourcePath = public_path('build/images/cover-pattern.png');
        $sourceSize = filesize($sourcePath);
        $file = new UploadedFile($sourcePath, 'pattern.png', 'image/png', null, true);

        $url = upload_webp_image($file, 'uploads/settings', 80);
        $path = $url;

        $disk->assertExists($path);
        $this->assertSame($sourceSize, $disk->size($path));
        $this->assertSame('png', pathinfo($path, PATHINFO_EXTENSION));
        $this->assertSame('image/png', $disk->mimeType($path));
    }

    public function test_an_already_compressed_image_does_not_grow_during_optimization(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.key' => 'test-access-key',
            'filesystems.disks.s3.secret' => 'test-secret-key',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
        ]);

        $disk = Storage::fake('s3', ['url' => 'https://cdn.example.test/agonito']);
        $sourcePath = public_path('build/images/small/img-7.jpg');
        $sourceSize = filesize($sourcePath);
        $file = new UploadedFile($sourcePath, 'product.jpg', 'image/jpeg', null, true);

        $url = upload_webp_image($file, 'uploads/products', 80);
        $path = $url;

        $disk->assertExists($path);
        $this->assertLessThan($sourceSize, $disk->size($path));
        $this->assertSame('webp', pathinfo($path, PATHINFO_EXTENSION));
    }

    public function test_webp_images_are_uploaded_and_deleted_using_the_configured_disk(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.key' => 'test-access-key',
            'filesystems.disks.s3.secret' => 'test-secret-key',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
            'filesystems.disks.s3.root' => 'agonito',
        ]);

        $disk = Storage::fake('s3', ['url' => 'https://cdn.example.test/agonito']);
        $path = upload_webp_image(
            UploadedFile::fake()->image('product.jpg', 120, 120),
            'uploads/products',
        );

        $this->assertMatchesRegularExpression(
            '#^uploads/products/[0-9a-f-]+\.webp$#',
            $path,
        );
        $disk->assertExists($path);
        $this->assertSame('image/webp', $disk->mimeType($path));
        $this->assertSame(
            'https://cdn.example.test/agonito/'.$path,
            api_asset($path),
        );

        $this->assertTrue(delete_uploaded_file($path));
        $disk->assertMissing($path);
    }

    public function test_api_asset_uses_the_cloud_url_even_when_a_local_copy_exists(): void
    {
        config([
            'app.url' => 'http://agonito.test',
            'filesystems.default' => 's3',
            'filesystems.disks.s3.key' => 'test-access-key',
            'filesystems.disks.s3.secret' => 'test-secret-key',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
            'filesystems.disks.s3.root' => 'agonito',
        ]);

        Storage::fake('s3', ['url' => 'https://cdn.example.test/agonito']);

        $path = 'uploads/sliders/local-copy-test.webp';
        $localPath = public_path($path);
        File::ensureDirectoryExists(dirname($localPath));
        File::put($localPath, 'fake-image');

        try {
            $cloudUrl = 'https://cdn.example.test/agonito/'.$path;

            $this->assertSame($cloudUrl, api_asset($path));
            $this->assertSame($cloudUrl, api_asset('/'.$path));
            $this->assertSame($cloudUrl, api_asset('http://agonito.test/'.$path));
            $this->assertSame($cloudUrl, api_asset('http://127.0.0.1:8000/'.$path));
            $this->assertSame($cloudUrl, api_asset('https://agonito.store/'.$path));
            $this->assertSame('Watch', api_asset('Watch'));
            $this->assertSame('BedDouble', api_asset('BedDouble'));
            $this->assertSame($cloudUrl, api_asset('https://cdn.example.test/agonito/'.$path));
            $this->assertSame(
                '<p><img src="'.$cloudUrl.'" alt="Banner"></p>',
                rewrite_api_assets_in_html('<p><img src="http://agonito.test/'.$path.'" alt="Banner"></p>'),
            );
            $this->assertSame(
                asset('build/images/cover-pattern.png'),
                api_asset('build/images/cover-pattern.png'),
            );
        } finally {
            File::delete($localPath);
        }
    }

    public function test_api_asset_uses_s3_even_when_the_default_disk_is_local(): void
    {
        config([
            'app.url' => 'https://agonito.store',
            'filesystems.default' => 'local',
            'filesystems.disks.s3.key' => 'test-access-key',
            'filesystems.disks.s3.secret' => 'test-secret-key',
            'filesystems.disks.s3.url' => 'https://cdn.example.test',
            'filesystems.disks.s3.root' => 'agonito',
        ]);

        Storage::fake('s3', ['url' => 'https://cdn.example.test/agonito']);

        $path = 'uploads/products/live-product.webp';

        $this->assertSame(
            'https://cdn.example.test/agonito/'.$path,
            api_asset($path),
        );
        $this->assertSame(
            'https://cdn.example.test/agonito/'.$path,
            api_asset('https://agonito.store/'.$path),
        );
    }
}
