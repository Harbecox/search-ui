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
    ];

    /**
     * Преобразует attributes из [{"name":"..", "value":".."}] в ["name" => "value"]
     */
    public function getAttributesMapAttribute(): array
    {
        $attrs = $this->attributes['attributes'] ?? [];

        if (empty($attrs) || !is_array($attrs)) {
            return [];
        }

        // Если массив объектов {name, value}
        if (isset($attrs[0]['name'])) {
            return collect($attrs)
                ->pluck('value', 'name')
                ->toArray();
        }

        // Если уже ключ => значение
        return $attrs;
    }
}
