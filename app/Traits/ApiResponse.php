<?php

namespace App\Traits;

use App\Http\Resources\Product\ProductsCollection;

trait ApiResponse
{
    public function success($data, $metadata = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'metadata' => $metadata
        ], $code);
    }
    public function productsWithPagination($data, $metadata = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'current_page' => $data->currentPage(),
                'data' => new ProductsCollection($data), // Converts paginated items to collection
                'first_page_url' => $data->url(1),
                'from' => $data->firstItem(),
                'last_page' => $data->lastPage(),
                'last_page_url' => $data->url($data->lastPage()),
                'links' => $data->linkCollection(), // Links for navigation
                'next_page_url' => $data->nextPageUrl(),
                'path' => $data->path(),
                'per_page' => $data->perPage(),
                'prev_page_url' => $data->previousPageUrl(),
                'to' => $data->lastItem(),
                'total' => $data->total()
            ],
            'metadata' => $metadata
        ], $code);
    }

    public function successWithPagination($data, $metadata = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'current_page' => $data->currentPage(),
                'data' => $data, // Converts paginated items to collection
                'first_page_url' => $data->url(1),
                'from' => $data->firstItem(),
                'last_page' => $data->lastPage(),
                'last_page_url' => $data->url($data->lastPage()),
                'links' => $data->linkCollection(), // Links for navigation
                'next_page_url' => $data->nextPageUrl(),
                'path' => $data->path(),
                'per_page' => $data->perPage(),
                'prev_page_url' => $data->previousPageUrl(),
                'to' => $data->lastItem(),
                'total' => $data->total()
            ],
            'metadata' => $metadata
        ], $code);
    }

    public function error($message, $data = null, $metadata = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'metadata' => $metadata
        ], $code);
    }
}