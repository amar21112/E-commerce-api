<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function paymentProcess(Request $request)
    {
        $order = Order::findOrFail($request->order_id);

        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Order already processed'], 400);
        }

        $response = $this->paymentGateway->sendPayment($request);

        if ($response['success']) {
            return redirect()->to($response['url']);
        }

        return redirect()->route('payment.failed');
    }

    public function callBack(Request $request): \Illuminate\Http\RedirectResponse
    {
        $response = $this->paymentGateway->callBack($request);

        if (!$response['status']) {
            return redirect()->route('payment.failed');
        }

        $order = Order::findOrFail($response['order_id']);

        $order->update([
            'status' => 'paid',
        ]);

        $orderItems = OrderItem::where('order_id', $order->id)->get();
        foreach ($orderItems as $orderItem) {
            $product = Product::findOrFail($orderItem->product_id);
            $product->decrement('stock', $orderItem->quantity);
        }
         return redirect()->route('payment.success');
    }

    public function success()
    {

        return view('payment-success');
    }
    public function failed()
    {

        return view('payment-failed');
    }
}
