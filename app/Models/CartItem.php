<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    protected $hidden = [
        'created_at','updated_at'
    ];

    public $timestamps = true;

    public function cart(){
        return $this->belongsTo(cart::class);
    }
    public function product(){
        return $this->hasOne(Product::class);
    }
}
