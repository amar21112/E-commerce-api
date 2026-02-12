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

        DB::beginTransaction();

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
                if($request->payment_method == 'cash')
                    $product->decrement('stock', $qty);
            }

            // Clear cart
            $cart->items()->delete();
            $cart->total = 0;

            DB::commit();

            return $this->getOrderData($order);

        } catch (\Exception $e) {
            DB::rollBack();
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

            if($request->payment_method == 'cash')
                $product->decrement('stock', $request['quantity']);

            DB::commit();

            return $this->getOrderData($order);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getOrderData(Order $order){
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

    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        // Optional: prevent deleting paid/shipped orders
        if (in_array($order->status, ['paid', 'shipped'])) {
            return response()->json([
                'error' => 'Cannot delete a paid or shipped order'
            ], 403);
        }

        $order = Order::with('items.product')->findOrFail($id);

        if (in_array($order->status, ['paid', 'shipped'])) {
            return response()->json(['error' => 'Cannot delete paid/shipped order'], 403);
        }

        DB::beginTransaction();

        try {
            // Restore stock
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            // Delete items first
            $order->items()->delete();

            // Delete order
            $order->delete();

            DB::commit();

            return response()->json(['message' => 'Order & items deleted']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark order as shipped
     */
    public function markAsShipped($id)
    {
        $order = Order::findOrFail($id);

        if ( $order->payment_method !=='cash' && $order->status !== 'paid') {
            return response()->json([
                'error' => 'Only paid orders can be shipped'
            ], 400);
        }

        $order->update([
            'status' => 'shipped'
        ]);

        return response()->json([
            'message' => 'Order marked as shipped',
            'data' => $order
        ]);
    }
}
