<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Model;

/** Public website content (see App\Services\Website\WebsiteContent). */
class WebsiteItem extends Model
{
    protected $table = 'website_items';

    protected $fillable = ['kind', 'title', 'body', 'subtitle', 'image', 'price', 'price_source', 'stock_source', 'link', 'icon', 'sort', 'published'];

    protected $casts = ['published' => 'boolean'];

    public const KINDS = [
        'product'     => ['Products', 'What the farm sells. A price can follow the egg price in Prices & Costs, and stock can follow the farm records.'],
        'testimonial' => ['Testimonials', 'What customers and partners say about the farm.'],
        'faq'         => ['FAQs', 'Questions shown on the home and products pages.'],
        'value'       => ['Values', 'What the farm stands for (icon, title, text).'],
        'partner'     => ['Partners & certifications', 'Logos shown in the partners strip.'],
        'highlight'   => ['Why buy from us', 'Short highlights on the home page (icon, title, text).'],
    ];
}
