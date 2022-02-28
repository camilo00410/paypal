<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    public function create(Request $request){
        $data = json_decode($request->getContent(), true);

        // Init PayPal
        $provider = \PayPal::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->setAccessToken($token);

        $price = Order::getProductPrice($data['value']);
        $description = Order::getProductDescription($data['value']);

        $order = $provider->createOrder([
            "intent" => 'CAPTURE',
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $price
                    ],
                    "description" => $description
                ]
            ]
                    ]);
        // Save Created Order to Database
        Order::create([
            'price' => $price,
            'description' => $description,
            'status' => $order['status'],
            'reference_number' => $order['id'],
        ]);
    
        return response()->json($order);
    }

    public function capture(Request $request){
        $data = json_decode($request->getContent(), true);
        $orderId = $data['orderId'];

        $provider = new PayPalClient;
        // Init PayPal
        $provider = \PayPal::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->setAccessToken($token);

        $result = $provider->capturePaymentOrder($orderId);
        // update database
        if($result['status'] == "COMPLETED"){
            DB::table('orders')
                ->where('reference_number', $result['id'])
                ->update([
                    'status'=>'COMPLETED', 
                    'updated_at'=> \Carbon\Carbon::now()
                ]);
        }

        return response()->json($result);   
    }
}
