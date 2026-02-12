<?php

namespace App\Models;

use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
       'id', 'name', 'description' , 'price' , 'stock' , 'category_id'
    ];

    protected $hidden = [
        'created_at' , 'updated_at'
    ];
    public $timestamps = true;

    protected static function booted()
    {
        static::deleting(function ($product) {
            // Delete all images when product is deleted
            $imageService = app(ProductImageService::class);
            $imageService->deleteAllProductImages($product);
        });
    }
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function images(){
        return $this->hasMany(ProductImage::class);
    }
}
