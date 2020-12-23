<?php

namespace App\Http\Controllers;

use App\Ebook;
use App\EbookLibrary;
use App\EbookPurchase;
use App\Notification;
use App\Order;
use App\PaymentMethod;
use App\PaymentMethodProviderVariable;
use App\PaymentProviderVariable;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use JWTAuth;
use FCM;

class EbookPurchaseController extends Controller
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
                $dataRaw = EbookPurchase::where(function ($where) use ($query){
                    $where->where('detail','LIKE','%'.$query.'%');
                })
                ->where('is_deleted', EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["ACTIVE"])
                ->with(array('user','ebook','payment_method' => function($query){
                    $query->with(array('paymentMethod', 'paymentProvider'))->get();
                }));
            } else{
                $dataRaw = EbookPurchase::where('is_deleted', EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["ACTIVE"])
                            ->with(array('user','ebook','payment_method' => function($query){
                                $query->with(array('paymentMethod', 'paymentProvider'))->get();
                            }));
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
    public function store(Request $request, $ebook_id)
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
            $data               = new EbookPurchase();
            $dataEbook          = Ebook::findOrFail($ebook_id);
            $user               = JWTAuth::parseToken()->authenticate();

            // Fill initial value of Order object
            $data->user_id      = $user->id;
            $data->ebook_id     = $ebook_id;
            $data->method_id    = $idPaymentMethodProvider;
            $data->invoice      = "";
            $data->amount       = $dataEbook->price;
            $data->detail       = "Pembelian Ebook " . $dataEbook->name . " (Rp. " . $dataEbook->price . ")";

            $const                                  = array();
            $const['user']                          = $user;
            $const['dataOrder']                     = $data;
            $const['activePaymentMethod']           = $activePaymentMethod;
            $const['providerVariable']              = $providerVariable;
            $const['paymentMethodProviderVariable'] = $paymentMethodProviderVariable;

            if ($activePaymentMethod->provider_name == Order::PAYMENT_PROVIDER["DUITKU"]){
                $returnValue    = $this->orderDuitku($const);

                $result         = EbookPurchase::where('id', $data->id)
                                    ->with(array('ebook' => function ($query) {
                                        $query->select("id", "price", "name");
                                    }))
                                    ->with(array('payment_method' => function($query){
                                        $query->select("payment_method.*")->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                                ->with(array('paymentMethodProviderVariable'));
                                    }))
                                    ->with(array("user" => function ($query) {
                                        $query->select("id", "name", "email", "role");
                                    }))->first();
                
                if($returnValue->statusCode == "00"){
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
            
                
            } else if ($activePaymentMethod->provider_name == Order::PAYMENT_PROVIDER["TRIPAY"]){
                $returnValue    = $this->orderTripay($const);

                $result         = EbookPurchase::where('id', $data->id)
                                    ->with(array('ebook' => function ($query) {
                                        $query->select("id", "price", "name");
                                    }))
                                    ->with(array('payment_method' => function($query){
                                        $query->select("payment_method.*")->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                                ->with(array('paymentMethodProviderVariable'));
                                    }))
                                    ->with(array("user" => function ($query) {
                                        $query->select("id", "name", "email", "role");
                                    }))->first();
                
                if($returnValue->success){
                    return response()->json([
                        'status'	=> 'Success',
                        'message'	=> 'Ebook Purchase Request Sent',
                        'data'      => $returnValue->data,
                        'order'     => $result
                    ], 201);
                } else {
                    return response()->json([
                        'status'	=> 'Failed',
                        'message'	=> $returnValue->message,
                        'data'      => $returnValue,
                        'order'     => $result
                    ], 201);
                }

                return $returnValue;
                
            } else {
                return response()->json([
                    'status'	=> 'Failed',
                    'message'	=> 'Payment Provider Not Available'
                ], 201);
            }

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
            "merchantOrderId" => "E" . $const['dataOrder']->id,
            "productDetails" => $const['dataOrder']->detail,
            "email" => $const['user']->email,
            "paymentMethod" => $listMethodVariable["CODE"],
            "returnUrl" => $listProviderVariable["RETURN_URL"],
            "callbackUrl" => $listProviderVariable["CALLBACK_URL"],
            "signature" => md5($listProviderVariable["MERCHANT_CODE"]. "E" . $const['dataOrder']->id. $const['dataOrder']->amount. $listProviderVariable["MERCHANT_KEY"])
        ];

        $requestAPI = $listProviderVariable["API_REQUEST"];
        $responsePayment    = Http::post($requestAPI, $body);

        // Update Order object with response value
        $responseObject      = json_decode($responsePayment);
        
        if(isset($responseObject->statusCode) && $responseObject->statusCode == "00"){
            if($listMethodVariable["IS_VA"] == Order::IS_VA["TRUE"]){
                $const['dataOrder']->payment_information    = $responseObject->vaNumber;
            } else {
                $const['dataOrder']->payment_information    = $responseObject->paymentUrl;
            }
    
            $const['dataOrder']->invoice        = $responseObject->reference;
            $const['dataOrder']->save();
    
            return $responseObject;
        } else {
            $const['dataOrder']->status         = EbookPurchase::EBOOK_PURCHASE_STATUS["FAILED"];
            $const['dataOrder']->save();

            $responseObject->statusCode = "01";
            return $responseObject;
        }
    }

    private function orderTripay($const){
        $const['dataOrder']->save();

        $listProviderVariable   = $this->convertToList($const['providerVariable']);
        $listMethodVariable     = $this->convertToList($const['paymentMethodProviderVariable']);

        $header = [
            'Authorization'=> 'Bearer '. $listProviderVariable["API_KEY"],
        ];

        // Request Transaction with Payment Gateway
        $body = [
            "method" => $listMethodVariable["CODE"],
            "merchant_ref" => 'E' . $const['dataOrder']->id,
            "amount" => $const['dataOrder']->amount,
            "customer_name" => $const['user']->name,
            "customer_email" => $const['user']->email,
            "order_items" => [
                [
                    "name" => $const['dataOrder']->detail,
                    "price" => $const['dataOrder']->amount,
                    "quantity" => 1
                ]
            ],
            "returnUrl" => $listProviderVariable["RETURN_URL"],
            "callbackUrl" => $listProviderVariable["CALLBACK_URL"],
            "signature" => hash_hmac('sha256', $listProviderVariable["MERCHANT_CODE"] . 'E'.  $const['dataOrder']->id . $const['dataOrder']->amount, $listProviderVariable["MERCHANT_KEY"])
        ];

        $requestAPI = $listProviderVariable["API_REQUEST"];
        $responsePayment = Http::withHeaders($header)->post($requestAPI, $body);

        $responseObject      = json_decode($responsePayment);

        if(isset($responseObject->success) && $responseObject->success){
            if($listMethodVariable["IS_VA"] == Order::IS_VA["TRUE"]){
                $const['dataOrder']->payment_information    = $responseObject->data->pay_code;
            } else {
                $const['dataOrder']->payment_information    = $responseObject->data->pay_code;
            }
    
            $const['dataOrder']->invoice        = $responseObject->data->reference;
            $const['dataOrder']->save();
    
            return $responseObject;
        } else {
            $const['dataOrder']->status         = Order::ORDER_STATUS["FAILED"];
            $const['dataOrder']->save();
            
            return $responseObject;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {

            $data       = EbookPurchase::where('id', $id)
                            ->with(array('ebook' => function ($query) {
                                $query->select("id", "price", "name");
                            }))
                            ->with(array('payment_method' => function($query){
                                $query->select("payment_method.*", "payment_method_provider.id", "payment_provider.name as active_provider_name")
                                        ->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                                        ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                        ->with(array('paymentMethodProviderVariable'));
                            }))
                            ->with(array("user" => function ($query) {
                                $query->select("id", "name", "email", "role");
                            }))->first();

            return response()->json([
                'status'    => "Success",
                'message'   => "Get Detail Ebook Purchase Succeeded",
                'data'      => $data
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Get Detail Ebook Purchase Failed',
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
            $data   = EbookPurchase::findOrFail($id);
            if($data->status == EbookPurchase::EBOOK_PURCHASE_STATUS["SUCCESS"]){
                return response()->json([
                    'status'    =>  "Failed",
                    'message'   =>  "Data Ebook Purchase Cannot be Changed"
                ], 200);

            } else if($data->is_deleted == EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["DELETED"]){
                return response()->json([
                    'status'    =>  "Failed",
                    'message'   =>  "Data Ebook Purchase Already Deleted"
                ], 200);

            } else {
                $data->status       = EbookPurchase::EBOOK_PURCHASE_STATUS["FAILED"];
                $data->is_deleted   = EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["DELETED"];
                $data->save();

                return response()->json([
                    'status'    =>  "Success",
                    'data'      =>  $data,
                    'message'   =>  "Data Ebook Purchase Deleted"
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function acceptEbookPurchase($id){
        try {
            $data                   = EbookPurchase::findOrFail($id);
            $dataUser               = User::findOrFail($data->user_id);

            $data->status           = EbookPurchase::EBOOK_PURCHASE_STATUS["SUCCESS"];

            $newLibrary             = new EbookLibrary();
            $newLibrary->id_user    = $data->user_id;
            $newLibrary->id_ebook   = $data->ebook_id;
            
            $newLibrary->save();
            $data->save();

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => $data->detail . " berhasil",
                "sender_id" => 0,
                "target_id" => $dataUser->id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[13],
                'token_recipient' => $dataUser->firebase_token,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

            return response()->json([
                'status'    =>  "Success",
                'data'      =>  $data,
                'message'   =>  "Ebook Purchase Succeeded"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function rejectEbookPurchase($id){
        try {
            $data                   = EbookPurchase::findOrFail($id);
            $dataUser               = User::findOrFail($data->user_id);

            $data->status           = EbookPurchase::EBOOK_PURCHASE_STATUS["FAILED"];
            
            $data->save();

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => $data->detail . " gagal",
                "sender_id" => 0,
                "target_id" => $dataUser->id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[13],
                'token_recipient' => $dataUser->firebase_token,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

            return response()->json([
                'status'    =>  "Success",
                'data'      =>  $data,
                'message'   =>  "Ebook Purchase Failed"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getEbookPurchaseByIdUser($user_id)
    {
        try {
            $dataRaw = EbookPurchase::where('is_deleted', EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["ACTIVE"])
                            ->where('user_id', $user_id)
                            ->with(array('user','ebook','payment_method' => function($query){
                                $query->with(array('paymentMethod', 'paymentProvider'))->get();
                            }));

            $data = $dataRaw->paginate(10);

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
}
