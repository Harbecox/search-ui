<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slami extends Model
{
    protected $table = 'slamis';

    public $timestamps = false;

    protected $casts = [
        'price_retail' => 'decimal:2',
    ];

    public function getFirstImageAttribute(): ?string
    {
        return $this->image_thumb ?: $this->image_full ?: null;
    }

    public function getProductUrlAttribute(): ?string
    {
        // У slamis нет публичной страницы — возвращаем null
        return null;
    }

    public function getPriceAttribute(): ?string
    {
        return $this->price_retail;
    }

    public function getTitleAttribute(): string
    {
        return $this->name;
    }
}
