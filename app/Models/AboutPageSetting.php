<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutPageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_title',
        'breadcrumb_title',
        'breadcrumb_subtitle',
        'cover_image',
        'section_one_subtitle',
        'section_one_title',
        'section_one_content',
        'section_one_image',
        'section_two_subtitle',
        'section_two_title',
        'section_two_content',
        'section_two_image',
        'features_subtitle',
        'features_title',
        'features_description',
        'feature_one_title',
        'feature_one_description',
        'feature_two_title',
        'feature_two_description',
        'feature_three_title',
        'feature_three_description',
        'reviews_subtitle',
        'reviews_title',
        'reviews_description',
    ];
}
