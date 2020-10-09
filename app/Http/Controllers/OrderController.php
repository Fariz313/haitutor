<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\User;
use App\Package;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use Illuminate\Support\Facades\Http;

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
                $data = Order::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = Order::paginate(10);
            }
            
            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
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
    public function store($package_id)
    {
        try{

            // Get Required Object (Order, Package and User)
            $data               = new Order();
            $dataPackage        = Package::findOrFail($package_id);
            $user               = JWTAuth::parseToken()->authenticate();

            // Fill initial value of Order object
            $data->user_id      = $user->id;
            $data->package_id   = $package_id;
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
                "paymentMethod" => Order::PAYMENT_METHOD["ATM_BERSAMA"],
                "returnUrl" => Order::DUITKU_ATTRIBUTES["RETURN_URL"],
                "callbackUrl" => Order::DUITKU_ATTRIBUTES["CALLBACK_URL"],
                "signature" => md5(Order::DUITKU_ATTRIBUTES["MERCHANT_CODE"]. $data->id. $data->amount. Order::DUITKU_ATTRIBUTES["MERCHANT_KEY"])
            ];
    
            $responsePayment    = Http::post('https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry', $body);

            // Update Order object with response value
            $responseObject      = json_decode($responsePayment);

            $data->va_number    = $responseObject->vaNumber;
            $data->invoice      = $responseObject->reference;
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Order added successfully',
                'data'      => $responseObject
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
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
    public function show($id)
    {
        //
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

            $data = Order::findOrFail($request->input('merchantOrderId'));
            $data->invoice = $request->input('reference');
            $data->detail = $request->input('productDetail');
            $data->amount = $request->input('amount');

            if($request->input('amount')){
                if('00' == $request->input('resultCode')){
                    $data->status = 'completed';
                } else {
                    $data->status = 'failed';
                }
            }

            $data->save();

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
}
