<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    public $timestamps = false;

    protected $casts = [
        'price'      => 'decimal:2',
        'attributes' => 'array',
        'images'     => 'array',
    ];

    public function getFirstImageAttribute(): ?string
    {
        $images = $this->images;
        return (!empty($images) && is_array($images)) ? $images[0] : null;
    }

    public function getProductUrlAttribute(): ?string
    {
        if (!$this->url) return null;
        return 'https://www.texenergo.ru' . $this->url;
    }

    public function getAttributesMapAttribute(): array
    {
        $attrs = $this->attributes['attributes'] ?? [];
        if (empty($attrs) || !is_array($attrs)) return [];
        if (isset($attrs[0]['name'])) {
            return collect($attrs)->pluck('value', 'name')->toArray();
        }
        return $attrs;
    }
}
