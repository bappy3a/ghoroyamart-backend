<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sub_title',
        'en_content',
        'bn_content',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->name);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('name') && empty($page->slug)) {
                $page->slug = Str::slug($page->name);
            }
        });
    }

    /**
     * Absolute storefront URL for this page.
     */
    public function publicUrl(): string
    {
        return route('pages.show', ['slug' => $this->slug]);
    }
}
