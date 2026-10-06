<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    use ApiResponse;

    private const IMAGE_DIR = 'uploads/reviews';

    /**
     * Public: approved reviews of a product + rating summary.
     */
    public function productReviews(Request $request, int $productId)
    {
        $product = Product::query()->find($productId);

        if (! $product) {
            return $this->error('Product not found.', null, null, 404);
        }

        $reviews = $product->approvedReviews()
            ->with('user:id,name')
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 10), 1), 50));

        return $this->success([
            'summary' => $this->summary($product),
            'reviews' => $reviews->getCollection()->map(fn (Review $r) => $this->format($r))->values()->all(),
            'pagination' => $this->pagination($reviews),
        ], null, 'Reviews fetched successfully.');
    }

    /**
     * Authenticated: the user's own reviews (optionally filtered by order number).
     */
    public function index(Request $request)
    {
        $query = Review::query()
            ->with(['product:id,name,slug,thumbnail_image', 'order:id,order_number', 'user:id,name'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('order_number')) {
            $query->whereHas('order', fn ($q) => $q->where('order_number', $request->input('order_number')));
        }

        $reviews = $query->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 50));

        return $this->success([
            'reviews' => $reviews->getCollection()->map(fn (Review $r) => $this->format($r))->values()->all(),
            'pagination' => $this->pagination($reviews),
        ], null, 'Reviews fetched successfully.');
    }

    /**
     * Create a review for a product from one of the user's delivered orders.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_number' => ['required', 'string', 'max:255'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'review_text' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), null, 422);
        }

        $user = $request->user();

        $order = $user->orders()
            ->where('order_number', $request->input('order_number'))
            ->whereHas('items', fn ($q) => $q->where('product_id', $request->input('product_id')))
            ->first();

        if (! $order) {
            return $this->error('Order not found for this product.', null, null, 404);
        }

        if (! in_array($order->order_status, ['delivered', 'partial_delivered'], true)) {
            return $this->error('You can review a product only after the order is delivered.', null, null, 422);
        }

        if (Review::where('user_id', $user->id)->where('product_id', $request->input('product_id'))->exists()) {
            return $this->error('You have already reviewed this product.', null, null, 409);
        }

        $review = DB::transaction(function () use ($request, $user, $order) {
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => (int) $request->input('product_id'),
                'order_id' => $order->id,
                'rating' => (int) $request->input('rating'),
                'review_text' => $request->input('review_text'),
                'images' => $this->storeImages($request),
                'status' => 'approved',
            ]);

            $this->refreshProductRating($review->product_id);

            return $review;
        });

        return $this->success(
            $this->format($review->load(['product:id,name,slug,thumbnail_image', 'order:id,order_number', 'user:id,name'])),
            ['product_rating' => $this->summary($review->product)],
            'Review submitted successfully.',
            201
        );
    }

    public function show(Request $request, int $id)
    {
        $review = $this->ownedReview($request, $id);

        if (! $review) {
            return $this->error('Review not found.', null, null, 404);
        }

        return $this->success($this->format($review), null, 'Review fetched successfully.');
    }

    /**
     * Update rating / text / images of the user's own review.
     * Send images[] to replace images; remove_images=1 to clear them.
     */
    public function update(Request $request, int $id)
    {
        $review = $this->ownedReview($request, $id);

        if (! $review) {
            return $this->error('Review not found.', null, null, 404);
        }

        $validator = Validator::make($request->all(), [
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'review_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_images' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), null, 422);
        }

        DB::transaction(function () use ($request, $review) {
            $data = $request->only(['rating', 'review_text']);

            if ($request->hasFile('images') || $request->boolean('remove_images')) {
                $this->deleteImages($review->images ?? []);
                $data['images'] = $request->hasFile('images') ? $this->storeImages($request) : [];
            }

            $review->update($data);
            $this->refreshProductRating($review->product_id);
        });

        $review->refresh()->load(['product:id,name,slug,thumbnail_image', 'order:id,order_number', 'user:id,name']);

        return $this->success(
            $this->format($review),
            ['product_rating' => $this->summary($review->product)],
            'Review updated successfully.'
        );
    }

    public function destroy(Request $request, int $id)
    {
        $review = $this->ownedReview($request, $id);

        if (! $review) {
            return $this->error('Review not found.', null, null, 404);
        }

        $productId = $review->product_id;

        DB::transaction(function () use ($review, $productId) {
            $this->deleteImages($review->images ?? []);
            $review->delete();
            $this->refreshProductRating($productId);
        });

        return $this->success(
            ['id' => $id, 'deleted' => true],
            ['product_rating' => $this->summary(Product::find($productId))],
            'Review deleted successfully.'
        );
    }

    /**
     * Recalculate products.reviews_avg / num_of_reviews from approved reviews.
     */
    private function refreshProductRating(int $productId): void
    {
        $stats = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as average')
            ->first();

        Product::whereKey($productId)->update([
            'num_of_reviews' => (int) $stats->total,
            'reviews_avg' => round((float) $stats->average, 2),
        ]);
    }

    private function summary(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        $product->refresh();

        $distribution = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->select('rating', DB::raw('COUNT(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');

        return [
            'product_id' => $product->id,
            'reviews_avg' => (float) $product->reviews_avg,
            'num_of_reviews' => (int) $product->num_of_reviews,
            'distribution' => collect([5, 4, 3, 2, 1])
                ->mapWithKeys(fn ($star) => [(string) $star => (int) ($distribution[$star] ?? 0)])
                ->all(),
        ];
    }

    private function ownedReview(Request $request, int $id): ?Review
    {
        return Review::query()
            ->with(['product:id,name,slug,thumbnail_image', 'order:id,order_number', 'user:id,name'])
            ->where('user_id', $request->user()->id)
            ->find($id);
    }

    private function format(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'images' => collect($review->images ?? [])->map(fn ($img) => api_asset($img))->values()->all(),
            'status' => $review->status,
            'product' => $review->relationLoaded('product') && $review->product ? [
                'id' => $review->product->id,
                'name' => $review->product->name,
                'slug' => $review->product->slug,
                'thumbnail_image' => api_asset($review->product->thumbnail_image),
            ] : null,
            'order_number' => $review->relationLoaded('order') ? $review->order?->order_number : null,
            'user' => $review->relationLoaded('user') && $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
            ] : null,
            'created_at' => optional($review->created_at)?->toIso8601String(),
            'updated_at' => optional($review->updated_at)?->toIso8601String(),
        ];
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function storeImages(Request $request): array
    {
        $paths = [];

        foreach ($request->file('images', []) as $file) {
            $name = uniqid('review_', true).'.'.$file->getClientOriginalExtension();
            $file->move(public_path(self::IMAGE_DIR), $name);
            $paths[] = self::IMAGE_DIR.'/'.$name;
        }

        return $paths;
    }

    private function deleteImages(array $paths): void
    {
        foreach ($paths as $path) {
            if (str_starts_with($path, self::IMAGE_DIR.'/')) {
                File::delete(public_path($path));
            }
        }
    }
}
