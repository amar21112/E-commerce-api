<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CartStoreRequest;
use App\Http\Requests\Api\CartUpdateRequest;
use App\Http\Resources\CartItemResource;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(){
        $user = auth()->user();
        $user_cart = $user->activeCart;
        $user_cart_items =CartItemResource::collection($user_cart->items);
        return response()->json(['message' => "success", 'cart' => $user_cart_items, 'total' =>$user_cart->total], 200);
    }

    private function calcTotalPrice()
    {
        $user = auth()->user();
        $user_cart = $user->activeCart;
        $user_cart_items =CartItemResource::collection($user_cart->items)->toArray(request());
        $total_price = 0;
        foreach ($user_cart_items as $item){
            $total_price += $item["product info"]['price'] * $item['quantity'];
        }
        $user_cart->total = $total_price;
        $user_cart->save();
    }

    public function store(CartStoreRequest $request){
        $user = auth()->user();

        $cartItem = $user->activeCart->items()
            ->where('product_id', $request->product_id)
            ->first();

        if($cartItem){
            return response()->json(['message' => 'Product already in your cart.'], 400);
        }

        $user_cart = $user->activeCart->items()->create($request->validated());
        $this->calcTotalPrice();

        return response()->json(['message'=>'Product added successfully' ,$user_cart]);
    }

    public function update(CartUpdateRequest $request){
        $user = auth()->user();
        $cartItem = $user->activeCart->items()
            ->where('product_id', $request->product_id)
            ->first();

        if(!$cartItem){
            return response()->json(['message' => 'Product not in your cart.'], 400);
        }

       $cartItem->update($request->validated());
        $cartItem->save();
        $this->calcTotalPrice();

        return response()->json(['message'=>'Product update' ,$cartItem]);
    }
    public function delete(string $cartItemId)
    {
        $user = auth()->user();

        // Get active cart
        $cart = $user->activeCart;

        if (!$cart) {
            return response()->json([
                'message' => 'No active cart found'
            ], 404);
        }

        // Check if cart item belongs to this cart
        $cartItem = $cart->items()
            ->where('id', $cartItemId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'message' => 'Item not found in your cart'
            ], 404);
        }

        // Delete item
        $cartItem->delete();
        $this->calcTotalPrice();

        return response()->json([
            'message' => 'Item removed successfully'
        ]);
    }

}
