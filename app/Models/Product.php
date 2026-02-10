<?php

namespace App\Models;

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

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function images(){
        return $this->hasMany(ProductImage::class);
    }
}
