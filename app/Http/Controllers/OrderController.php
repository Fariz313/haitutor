<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\User;
use App\Package;
use App\PaymentMethod;
use App\Notification;
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
                } );
            }else{
                $dataRaw = Order::with(array('user','package'));
            }
            if($request->get('filter')){
                if($request->get('filter') == "pending"){
                    $data = $dataRaw->where('status','pending')->paginate(10);
                }else if($request->get('filter') == "completed"){
                    $data = $dataRaw->where('status','completed')->paginate(10);
                }else{
                    $data = $dataRaw->paginate(10);
                }
            }else{
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

            // Get Required Object (Order, Package and User)
            $data               = new Order();
            $dataPackage        = Package::findOrFail($package_id);
            $user               = JWTAuth::parseToken()->authenticate();
            $paymentMethod      = PaymentMethod::where('code', $request->input('payment_method'))->first();

            // Fill initial value of Order object
            $data->user_id      = $user->id;
            $data->package_id   = $package_id;
            $data->method_id    = $paymentMethod->id;
            $data->invoice      = "";
            $data->amount       = $dataPackage->price;
            $data->detail       = "Pembelian " . $dataPackage->name . " (" . $dataPackage->balance . " Token)";
            $data->pos          = Order::POS_STATUS["DEBET"];
            $data->type_code    = Order::TYPE_CODE["PAYMENT_GATEWAY"];
            $data->save();

            // Request Transaction with Payment Gateway
            $body = [
                "merchantCode" => Order::DUITKU_ATTRIBUTES["MERCHANT_CODE"],
                "paymentAmount" => $data->amount,
                "merchantOrderId" => $data->id,
                "productDetails" => $data->detail,
                "email" => $user->email,
                "paymentMethod" => $request->input('payment_method'),
                "returnUrl" => Order::DUITKU_ATTRIBUTES["RETURN_URL"],
                "callbackUrl" => Order::DUITKU_ATTRIBUTES["CALLBACK_URL"],
                "signature" => md5(Order::DUITKU_ATTRIBUTES["MERCHANT_CODE"]. $data->id. $data->amount. Order::DUITKU_ATTRIBUTES["MERCHANT_KEY"])
            ];

            $responsePayment    = Http::post('https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry', $body);

            // Update Order object with response value
            $responseObject      = json_decode($responsePayment);
            $isUseVA             = in_array($paymentMethod->code, Order::NON_VA);

            if($isUseVA){
                $data->va_number    = $responseObject->paymentUrl;
            } else {
                $data->va_number    = $responseObject->vaNumber;
            }

            $data->invoice      = $responseObject->reference;
            $data->save();

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Order added successfully',
                'data'      => $responseObject,
                'order'     => $data
            ], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ], 500);
        }
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

            $non_va     = Order::NON_VA;

            $data       = Order::where('id', $id)
                            ->with(array('package' => function ($query) {
                                $query->select("id", "price", "balance", "name");
                            }))
                            ->with(array('payment_method' => function ($query) {
                                $query->select('id', 'name', 'code');
                            }))->first();

            for ($i=0; $i < sizeof($non_va); $i++) {
                if ($data->payment_method->code == $non_va[$i]) {
                    $data->payment_method->is_va = "true";
                    break;
                } else {
                    $data->payment_method->is_va = "false";
                }
            }

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
        //
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

    public function requestTransaction(Request $request){
        $body = [
            "merchantCode" => $request->input('merchantCode'),
            "paymentAmount" => $request->input('paymentAmount'),
            "merchantOrderId" => $request->input('merchantOrderId'),
            "productDetails" => $request->input('productDetails'),
            "email" => $request->input('email'),
            "paymentMethod" => $request->input('paymentMethod'),
            "returnUrl" => $request->input('returnUrl'),
            "callbackUrl" => $request->input('callbackUrl'),
            "signature" => md5(Order::DUITKU_ATTRIBUTES["MERCHANT_CODE"]. $request->input('merchantOrderId'). $request->input('paymentAmount'). Order::DUITKU_ATTRIBUTES["MERCHANT_KEY"])
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
}
