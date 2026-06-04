<?php

namespace App\Models;

use App\Services\SecureUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'category_id', 'brand_id', 'made_in', 'compatibility',
        'base_price', 'images', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price'    => 'decimal:2',
            'images'        => 'array',
            'compatibility' => 'array',
            'is_active'     => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->oldestOfMany();
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) $this->base_price;
    }

    public function getImagePathAttribute(): ?string
    {
        $images = $this->images;

        return is_array($images) ? ($images[0] ?? null) : null;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return app(SecureUploadService::class)->url($this->image_path);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imageUrl();
    }
}