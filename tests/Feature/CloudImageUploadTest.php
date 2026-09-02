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

        $disk->assertMissing($path);
        $this->assertFileExists(public_path($path));
        $this->assertSame($sourceSize, File::size(public_path($path)));
        $this->assertSame('png', pathinfo($path, PATHINFO_EXTENSION));
        $this->assertSame('image/png', File::mimeType(public_path($path)));
        $this->assertTrue(delete_uploaded_file($path));
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

        $disk->assertMissing($path);
        $this->assertFileExists(public_path($path));
        $this->assertLessThan($sourceSize, File::size(public_path($path)));
        $this->assertSame('webp', pathinfo($path, PATHINFO_EXTENSION));
        $this->assertTrue(delete_uploaded_file($path));
    }

    public function test_webp_images_are_uploaded_to_the_selected_public_folder_without_using_s3(): void
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
            'products',
        );

        $this->assertMatchesRegularExpression(
            '#^uploads/products/[0-9a-f-]+\.webp$#',
            $path,
        );
        $disk->assertMissing($path);
        $this->assertFileExists(public_path($path));
        $this->assertSame('image/webp', File::mimeType(public_path($path)));
        $this->assertSame(
            asset($path),
            api_asset($path),
        );

        $this->assertTrue(delete_uploaded_file($path));
        $disk->assertMissing($path);
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_api_asset_uses_local_urls_for_relative_public_paths(): void
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
            $localUrl = asset($path);

            $this->assertSame($localUrl, api_asset($path));
            $this->assertSame($localUrl, api_asset('/'.$path));
            $this->assertSame('http://agonito.test/'.$path, api_asset('http://agonito.test/'.$path));
            $this->assertSame('http://127.0.0.1:8000/'.$path, api_asset('http://127.0.0.1:8000/'.$path));
            $this->assertSame('https://agonito.store/'.$path, api_asset('https://agonito.store/'.$path));
            $this->assertSame(asset('Watch'), api_asset('Watch'));
            $this->assertSame(asset('BedDouble'), api_asset('BedDouble'));
            $this->assertSame($cloudUrl, api_asset('https://cdn.example.test/agonito/'.$path));
            $this->assertSame(
                '<p><img src="http://agonito.test/'.$path.'" alt="Banner"></p>',
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

    public function test_api_asset_does_not_rewrite_paths_to_s3(): void
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
            asset($path),
            api_asset($path),
        );
        $this->assertSame(
            'https://agonito.store/'.$path,
            api_asset('https://agonito.store/'.$path),
        );
    }
}
