<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderFormRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function placeOrderFromCart(PlaceOrderFormRequest $request)
    {
        $user = auth()->user();
        $cart = $user->activeCart;

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        \DB::beginTransaction();

        try {
            $total = 0;

            $order = Order::create([
                'user_id' => $user->id,
                'address' => $request->address,
                'payment_method' => $request->payment_method,
                'total_price' => $cart->total,
                'status' => 'pending'
            ]);

            foreach ($cart->items as $cartItem) {
                $product = Product::findOrFail($cartItem->product_id);

                $qty = $cartItem->quantity;
                if ($product->stock < $qty) {
                    throw new \Exception("Not enough stock for {$product->name}");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $product->price,
                ]);
                // Reduce stock
                $product->decrement('stock', $qty);
            }


            // Clear cart
            $cart->items()->delete();
            $cart->total = 0;

            \DB::commit();

            return new OrderResource($order->load('items') );
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function store(StoreOrderRequest $request)
    {
        $user = auth()->user();


        DB::beginTransaction();

        try {
            $product= Product::where('id' , $request->product_id)->first();

            if(!$product){
                throw new \Exception("Product not found");
            }
            if($product->stock < $request['quantity']){
                throw new \Exception("Not enough stock for {$product->name}");
            }

            $total = $product->price * $request['quantity'];


            $order = Order::create([
                'user_id' => $user->id,
                'address' => $request->address,
                'payment_method' => $request->payment_method,
                'total_price' => $total,
                'status' => 'pending'
            ]);

            $orderItems = OrderItem::create(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $request['quantity'],
                    'price' => $product->price,
                ]
            );

            $product->decrement('stock', $request['quantity']);

            DB::commit();
            if($order->payment_method == 'card'){
               $url =  route('payment.process', ['order_id' => $order->id]);

                return response()->json([
                    'message' => 'Order created',
                    'order_id' => $order->id,
                    'payment_url' => $url,
                    'data' => new OrderResource($order->load('items'))
                ], 201);
            }

            return response()->json([
                'message' => 'Order created',
                'data' => new OrderResource($order->load('items'))
            ]);


        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function index()
    {
        return OrderResource::collection(
            Order::with('items')->where('user_id', auth()->id())->get()
        );
    }


    public function show($id)
    {
        $order = Order::with('items')->where('user_id', auth()->id())->findOrFail($id);
        return new OrderResource($order);
    }
}
