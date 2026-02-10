<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaypalPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    protected $client_id;
    protected $client_secret;
    public function __construct()
    {
        $this->base_url =env("PAYPAL_BASE_URL");
        $this->client_id = env("PAYPAL_SANDBOX_CLIENT_ID");
        $this->client_secret = env("PAYPAL_SANDBOX_CLIENT_SECRET");
        $this->header = [
            'Accept' => 'application/json',
            'Content-Type' =>'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
        ];

    }

    public function sendPayment(Request $request): array
    {
        $data=$this->formatData($request);
        $response = $this->buildRequest("POST", "/v2/checkout/orders", $data);
        //handel payment response data and return it
        if ($response->getData(true)['success']){

            return ['success' => true,'url'=>$response->getData(true)['data']['links'][1]['href']];
        }
        return ['success' => false,'url'=>route('payment.failed')];

    }

    public function callBack(Request $request)
    {
        $token=$request->get('token');
        $response=$this->buildRequest('POST',"/v2/checkout/orders/$token/capture");
        Storage::put('paypal.json',json_encode([
            'callback_response'=>$request->all(),
            'capture_response'=>$response
        ]));
        if($response->getData(true)['success']&& $response->getData(true)['data']['status']==='COMPLETED' ){
            return ['status'=> true , 'order_id'=>$response->getData(true)['data']['purchase_units'][0]['payments']['captures'][0]['custom_id']];
        }
        return ['status'=>false];
    }

    public function formatData($request): array
    {
        $order = Order::with('items.product')->findOrFail($request->order_id);

        $items = [];
        $total = 0;

        foreach ($order->items as $item) {
            $items[] = [
                "name" => $item->product->name,
                "description" => $item->product->description ?? 'Product',
                "unit_amount" => [
                    "currency_code" => "USD",
                    "value" => number_format($item->price, 2, '.', '')
                ],
                "quantity" => (string) $item->quantity
            ];

            $total += $item->price * $item->quantity;
        }
        return [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "custom_id" => (string) $order->id, // ⭐ Order ID sent to PayPal
                    "items" => $items,
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => number_format($total, 2, '.', ''),
                        "breakdown" => [
                            "item_total" => [
                                "currency_code" => "USD",
                                "value" => number_format($total, 2, '.', '')
                            ]
                        ]
                    ]
                ]
            ],
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
                        "return_url" => $request->getSchemeAndHttpHost().'/api/payment/callback',
                        "cancel_url" => route("payment.failed"),
                    ]
                ]
            ]
        ];
    }
}
