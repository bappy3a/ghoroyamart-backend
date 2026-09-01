<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditorUploadController extends Controller
{
    /**
     * Upload image or video for CKEditor content.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => ['required', 'file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg,video/quicktime'],
        ]);

        $file = $request->file('upload');
        $mime = $file->getMimeType() ?: '';
        $isVideo = str_starts_with($mime, 'video/');

        if ($isVideo) {
            $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
            $filename = Str::uuid()->toString().'.'.$extension;
            $diskName = (string) config('filesystems.default');
            $path = Storage::disk($diskName)->putFileAs(
                'uploads/editor/videos',
                $file,
                $filename,
                [
                    'ContentType' => $mime ?: 'video/mp4',
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ]
            );

            if ($path === false) {
                abort(500, "Unable to store video on the [{$diskName}] disk.");
            }

            return response()->json([
                'url' => api_asset($path),
                'type' => 'video',
            ]);
        }

        $path = upload_webp_image($file, 'uploads/editor/images', 80);

        return response()->json([
            'url' => api_asset($path),
            'type' => 'image',
        ]);
    }
}
