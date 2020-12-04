<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\User;
use App\Package;
use App\PaymentMethod;
use App\Notification;
use App\PaymentMethodProvider;
use App\PaymentMethodProviderVariable;
use App\PaymentProviderVariable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use Illuminate\Support\Facades\Http;
use FCM;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $dataRaw = Order::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%');
                })->where('type_code', '1');
            }else{
                $dataRaw = Order::where('type_code', '1')->with(array('user','package'));
            }
            if($request->get('filter')){
                if($request->get('filter') == "pending"){
                    $data = $dataRaw->where('status','pending')
                                    ->where('type_code', '1')->paginate(10);
                }else if($request->get('filter') == "completed"){
                    $data = $dataRaw->where('status','completed')
                                    ->where('type_code', '1')->paginate(10);
                } else if ($request->get('filter') == "failed") {
                    $data = $dataRaw->where('status','failed')
                                    ->where('type_code', '1')->paginate(10);
                } else {
                    $data = $dataRaw->paginate(10);
                }
            }else if ($request->get('invoice')) {
                $query = $request->get('invoice');
                $data = $dataRaw->where('invoice','LIKE', '%'.$query.'%')
                                    ->where('type_code', '1')->paginate(10);
            } else {
                $data = $dataRaw->paginate(10);
            }

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  $th->getMessage()
            ], 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $package_id)
    {
        try{

            $idPaymentMethodProvider    = $request->input('id_payment_method_provider');
            $activePaymentMethod        = PaymentMethod::select('payment_method.*', 
                                                'payment_method_provider.id as id_payment_method_provider', 
                                                'payment_method_provider.id_payment_provider', 
                                                'payment_provider.name as provider_name')
                                            ->join("payment_method_provider", "payment_method.id", "=", "payment_method_provider.id_payment_method")
                                            ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                            ->with('paymentMethodProviderVariable')
                                            ->where('payment_method_provider.id', $idPaymentMethodProvider)
                                            ->first();

            $providerVariable           = PaymentProviderVariable::where('environment', Order::getEnvironment())
                                            ->where('id_payment_provider', $activePaymentMethod->id_payment_provider)
                                            ->get();

            $paymentMethodProviderVariable = PaymentMethodProviderVariable::where('id_payment_method_provider', $idPaymentMethodProvider)
                                            ->get();

            // Get Required Object (Order, Package and User)
            $data               = new Order();
            $dataPackage        = Package::findOrFail($package_id);
            $user               = JWTAuth::parseToken()->authenticate();

            // Fill initial value of Order object
            $data->user_id      = $user->id;
            $data->package_id   = $package_id;
            $data->method_id    = $idPaymentMethodProvider;
            $data->invoice      = "";
            $data->amount       = $dataPackage->price;
            $data->detail       = "Pembelian " . $dataPackage->name . " (" . $dataPackage->balance . " Token)";
            $data->pos          = Order::POS_STATUS["DEBET"];
            $data->type_code    = Order::TYPE_CODE["PAYMENT_GATEWAY"];

            $const                                  = array();
            $const['user']                          = $user;
            $const['dataOrder']                     = $data;
            $const['activePaymentMethod']           = $activePaymentMethod;
            $const['providerVariable']              = $providerVariable;
            $const['paymentMethodProviderVariable'] = $paymentMethodProviderVariable;

            if ($activePaymentMethod->provider_name == Order::PAYMENT_PROVIDER["DUITKU"]){
                $returnValue    = $this->orderDuitku($const);

                $result         = Order::where('id', $data->id)
                                    ->with(array('package' => function ($query) {
                                        $query->select("id", "price", "balance", "name");
                                    }))
                                    ->with(array('payment_method' => function($query){
                                        $query->select("payment_method.*")->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                                ->with(array('paymentMethodProviderVariable'));
                                    }))
                                    ->with(array("user" => function ($query) {
                                        $query->select("id", "name", "email", "role");
                                    }))->first();
                
                if($result->statusCode == "00"){
                    return response()->json([
                        'status'	=> 'Success',
                        'message'	=> 'Order added successfully',
                        'data'      => $returnValue,
                        'order'     => $result
                    ], 201);
                } else {
                    return response()->json([
                        'status'	=> 'Failed',
                        'message'	=> $returnValue->Message,
                        'data'      => $returnValue,
                        'order'     => $result
                    ], 201);
                }
                
            } else {
                return response()->json([
                    'status'	=> 'Failed',
                    'message'	=> 'Payment Provider Not Available'
                ], 201);
            }

            return $activePaymentMethod;

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function convertToList($variables){
        $listVariable   = array();
        foreach ($variables as $providerVariable) {
            $listVariable += array($providerVariable->variable => $providerVariable->value);
        }
        return $listVariable;
    }

    private function orderDuitku($const){
        $const['dataOrder']->save();

        $listProviderVariable   = $this->convertToList($const['providerVariable']);
        $listMethodVariable     = $this->convertToList($const['paymentMethodProviderVariable']);
        
        // Request Transaction with Payment Gateway
        $body = [
            "merchantCode" => $listProviderVariable["MERCHANT_CODE"],
            "paymentAmount" => $const['dataOrder']->amount,
            "merchantOrderId" => $const['dataOrder']->id,
            "productDetails" => $const['dataOrder']->detail,
            "email" => $const['user']->email,
            "paymentMethod" => $listMethodVariable["CODE"],
            "returnUrl" => $listProviderVariable["RETURN_URL"],
            "callbackUrl" => $listProviderVariable["CALLBACK_URL"],
            "signature" => md5($listProviderVariable["MERCHANT_CODE"]. $const['dataOrder']->id. $const['dataOrder']->amount. $listProviderVariable["MERCHANT_KEY"])
        ];

        $requestAPI = $listProviderVariable["API_REQUEST"];
        $responsePayment    = Http::post($requestAPI, $body);

        // Update Order object with response value
        $responseObject      = json_decode($responsePayment);
        
        // return $responseObject;
        if(isset($responseObject->statusCode) && $responseObject->statusCode == "00"){
            if($listMethodVariable["IS_VA"] == Order::IS_VA["TRUE"]){
                $const['dataOrder']->va_number  = $responseObject->vaNumber;
            } else {
                $const['dataOrder']->va_number  = $responseObject->paymentUrl;
            }
    
            $const['dataOrder']->invoice        = $responseObject->reference;
            $const['dataOrder']->save();
    
            return $responseObject;
        } else {
            $const['dataOrder']->status         = Order::ORDER_STATUS["FAILED"];
            $const['dataOrder']->save();

            $responseObject->statusCode = "01";
            return $responseObject;
        }
    }

    private function orderTripay($const){

        $header = [
            'Authorization'=> 'Bearer DEV-CYJkpTwOQta20ZRplLctm5TLvXi74ekQunsjbpKe',
        ];

        // Request Transaction with Payment Gateway
        $body = [
            "method" => "BRIVA",
            "merchant_ref" => 1,
            "amount" => 10000,
            "customer_name" => "Akhmad Muzanni",
            "customer_email" => "akhmadmuzannisafii@gmail.com",
            "order_items" => [
                [
                    "name" => "Paket 1",
                    "price" => 10000,
                    "quantity" => 1
                ]
            ],
            "returnUrl" => "https://haitutor.id/restfull_api/api/return",
            "callbackUrl" => "https://haitutor.id/restfull_api/api/return",
            "signature" => "5f1cebf1bc289332f01f3046898780155bd4f51a569dd9a7c2999e1b5001f10b"
        ];

        $response = Http::withHeaders($header)->post('https://payment.tripay.co.id/api-sandbox/transaction/create', $body);

        return $response;
    }

    public function verify($id)
    {
        try{

            $data               = Order::findOrFail($id);
            $data_detail        = $data->select('package.balance')->join('package', 'package.id', '=', 'order.package_id')->first();
            $data->status       = "completed";
            $user               = User::findOrFail($data->user_id);
            $user->balance      = $user->balance + $data_detail->balance;
            $data->save();
            $user->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Order successfully verify'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        try {

            $user           = JWTAuth::parseToken()->authenticate();

            if ($request->get("type") || $request->get("search")) {

                $query      = $request->get("search");
                $type_code  = str_replace('"', '', $request->get("type"));

                $data       = Order::where('user_id', $user->id)
                                ->with(array('package' => function ($query) {
                                    $query->select("id", "price", "balance", "name");
                                }))
                                ->with(array('payment_method' => function($query){
                                    $query->select("payment_method.*", "payment_provider.name as active_provider_name")
                                            ->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                            ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                            ->with(array('paymentMethodProviderVariable'));
                                }))
                                ->orderBy('created_at','DESC')
                                ->where("type_code", 'LIKE', '%'.$type_code.'%')
                                ->where("detail", 'LIKE',  '%'.$query.'%')
                                ->paginate(10);

                return response()->json($data, 200);

            } else {
                $data       = Order::where('user_id', $user->id)
                                ->where(function($query) use ($user) {
                                    $query->select('id','name','email', 'photo');
                                })
                                ->with(array('package' => function ($query) {
                                    $query->select("id", "price", "balance", "name");
                                }))
                                ->with(array('payment_method' => function($query){
                                    $query->select("payment_method.*", "payment_provider.name as active_provider_name")
                                            ->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                            ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                            ->with(array('paymentMethodProviderVariable'));
                                }))
                                ->orderBy('created_at','DESC')
                                ->paginate(10);

                return response()->json($data, 200);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to get order historyu',
                'data'      =>  $th->getMessage()
            ],400);
        }
    }

    public function showById($id)
    {
        try {

            $data       = Order::findOrFail($id);

            $data       = Order::where('id', $id)
                            ->with(array('package' => function ($query) {
                                $query->select("id", "price", "balance", "name");
                            }))
                            ->with(array('payment_method' => function($query){
                                $query->select("payment_method.*", "payment_provider.name as active_provider_name")
                                        ->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                        ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                        ->with(array('paymentMethodProviderVariable'));
                            }))
                            ->with(array("user" => function ($query) {
                                $query->select("id", "name", "email", "role");
                            }))->first();

            return response()->json([
                'status'    => "success",
                'message'   => "Success fetch order",
                'data'      => $data
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to get order historyu',
                'data'      =>  $th->getMessage()
            ],400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {

            $data           = Order::where("id", $id)->firstOrfail();

            $delete         = $data->delete();

            if ($delete) {
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'Delete order history success'
                ],200);
            } else {
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Failed to delete order history'
                ],400);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to delete order history',
                'data'      =>  $th->getMessage()
            ],400);
        }
    }

    public function manualVerifyOrder($id)
    {
        try {
            $data           = Order::findOrFail($id);
            $dataUser       = User::findOrFail($data->user_id);
            $dataPackage    = Package::findOrFail($data->package_id);

            // $data->invoice  = $request->input('reference');
            // $data->detail   = $request->input('productDetail');
            // $data->amount   = $request->input('amount');

            $dataUser->balance = $dataUser->balance + $dataPackage->balance;
            $data->status      = 'completed';
            // if($request->input('amount')){
            //     if('00' == $request->input('resultCode')){
            //         $data->status = 'completed';
            //     } else {
            //         $data->status = 'failed';
            //     }
            // }

            $data->save();
            $dataUser->save();

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => $data->detail . " berhasil",
                "sender_id" => 0,
                "target_id" => $dataUser->id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[4],
                'token_recipient' => $dataUser->firebase_token,
                'amount' => $dataPackage->balance,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Order verified',
                'data'      => $data
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed to manual verify order',
                'data'      =>  $th->getMessage()
            ],400);
        }
    }


    public function callbackTransaction(Request $request)
    {
        try{

            $data           = Order::findOrFail($request->input('merchantOrderId'));
            $dataUser       = User::findOrFail($data->user_id);
            $dataPackage    = Package::findOrFail($data->package_id);

            $data->invoice  = $request->input('reference');
            $data->detail   = $request->input('productDetail');
            $data->amount   = $request->input('amount');

            $dataUser->balance = $dataUser->balance + $dataPackage->balance;

            if($request->input('amount')){
                if('00' == $request->input('resultCode')){
                    $data->status = 'completed';
                } else {
                    $data->status = 'failed';
                }
            }

            $data->save();
            $dataUser->save();

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => $data->detail . " berhasil",
                "sender_id" => 0,
                "target_id" => $dataUser->id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[4],
                'token_recipient' => $dataUser->firebase_token,
                'amount' => $dataPackage->balance,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Callback Transaction',
                'data'      => $data
            ], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function callbackTransactionTripay(Request $request){
        try{

            $data = $request->input('merchant_ref');

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Callback Transaction',
                'data'      => $data
            ], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function requestTransaction(Request $request){
        // $body = [
        //     "merchantCode" => $request->input('merchantCode'),
        //     "paymentAmount" => $request->input('paymentAmount'),
        //     "merchantOrderId" => $request->input('merchantOrderId'),
        //     "productDetails" => $request->input('productDetails'),
        //     "email" => $request->input('email'),
        //     "paymentMethod" => $request->input('paymentMethod'),
        //     "returnUrl" => $request->input('returnUrl'),
        //     "callbackUrl" => $request->input('callbackUrl'),
        //     "signature" => md5(Order::DUITKU_ATTRIBUTES["MERCHANT_CODE"]. $request->input('merchantOrderId'). $request->input('paymentAmount'). Order::DUITKU_ATTRIBUTES["MERCHANT_KEY"])
        // ];

        $body = [
            "merchantCode" => $request->input('merchantCode'),
            "paymentAmount" => $request->input('paymentAmount'),
            "merchantOrderId" => $request->input('merchantOrderId'),
            "productDetails" => $request->input('productDetails'),
            "email" => $request->input('email'),
            "paymentMethod" => $request->input('paymentMethod'),
            "returnUrl" => $request->input('returnUrl'),
            "callbackUrl" => $request->input('callbackUrl'),
            "signature" => md5(Order::PAYMENT["DUITKU"]["DEVELOPMENT"]["MERCHANT_CODE"]. $request->input('merchantOrderId'). $request->input('paymentAmount'). Order::PAYMENT["DUITKU"]["DEVELOPMENT"]["MERCHANT_KEY"])
        ];

        $response = Http::post('https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry', $body);
        return $response;
    }

    public function getAllPaymentMethod()
    {
        try {
            $data = PaymentMethod::get();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  $th->getMessage()
            ], 400);
        }
    }

    public function historyToken(Request $request)
    {

        $internal_type = Order::TYPE_CODE["INTERNAL"];

        try {

            if ($request->get('search')) {
                $query = $request->get('search');

                $data = Order::where('detail', 'LIKE', '%'.$query.'%')->paginate(10);

            } else {
                $data = Order::where('type_code', $internal_type)->paginate(10);
            }

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'Failed to get data',
                'message'   =>  $th->getMessage()
            ], 400);
        }
    }

    public function detailHistoryToken($id)
    {
        try {

            $data = Order::where("id", $id)->firstOrFail();

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'Failed to get detail history token',
                'message'   =>  $th->getMessage()
            ], 400);
        }
    }
}
