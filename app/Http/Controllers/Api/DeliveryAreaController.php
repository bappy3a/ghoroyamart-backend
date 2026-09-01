<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryArea;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DeliveryAreaController extends Controller
{
    use ApiResponse;

    /**
     * Active delivery areas grouped by district_name.
     *
     * Query: q (optional search against name, district_name, search_tags)
     */
    public function index(Request $request)
    {
        $query = DeliveryArea::query()
            ->active()
            ->orderBy('district_name')
            ->orderBy('name');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('district_name', 'like', $like)
                    ->orWhere('search_tags', 'like', $like);
            });
        }

        $groups = $query
            ->get(['id', 'name', 'district_id', 'district_name', 'search_tags', 'post_code'])
            ->groupBy('district_name')
            ->map(function ($areas, $districtName) {
                $first = $areas->first();

                return [
                    'district_id' => $first?->district_id,
                    'district_name' => $districtName,
                    'areas' => $areas->map(fn (DeliveryArea $area) => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'search_tags' => $area->search_tags,
                        'post_code' => $area->post_code,
                    ])->values(),
                ];
            })
            ->values();

        return $this->success($groups, null, 'Delivery areas fetched successfully.');
    }
}
