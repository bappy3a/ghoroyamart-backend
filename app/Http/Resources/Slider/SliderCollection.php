<?php

namespace App\Http\Resources\Slider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SliderCollection extends ResourceCollection
{
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     *
     * Keys match the storefront Hero slide shape.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($item) {
            return [
                'id' => $item->id,
                'image' => $item->image ? api_asset($item->image) : null,
                'eyebrow' => $item->subtitle,
                'title' => $item->title,
                'copy' => $item->description,
                'cta' => $item->button_text,
                'cta_link' => $item->button_link,
                'alt_text' => $item->alt_text,
                'sort_order' => $item->sort_order,
            ];
        })->values()->all();
    }
}
