<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PaymentMethod;
use App\PaymentMethodCategory;
use App\PaymentMethodProvider;
use App\PaymentProvider;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class PaymentMethodController extends Controller {
    
    public function getAll(Request $request){
        try{
            // Get All Provider for each Payment Method
            $activePaymentMethodProvider = PaymentMethodProvider::select('payment_method_provider.id_payment_method', 
                                                    'payment_provider.id as active_provider_id', 
                                                    'payment_provider.name as active_provider_name')
                                                ->where('isActive', PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["ACTIVE"])
                                                ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                                ->groupBy('id_payment_method');

            if($request->get('search')){
                $query = $request->get('search');
                
                $data = PaymentMethod::select('payment_method.*', 
                        'payment_method_category.name as category_name', 
                        'payment_method_category.order as category_order', 
                        'active_method.*')
                    ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                    ->joinSub($activePaymentMethodProvider, 'active_method', function ($join) {
                        $join->on('payment_method.id', '=', 'active_method.id_payment_method');
                    })
                    ->with(array('availablePaymentProvider' => function($query){
                        $query->select('payment_method_provider.*',
                        'payment_provider.name as provider_name')
                        ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id");
                    }))
                    ->orderBy('status','DESC')
                    ->orderBy('category_order','ASC')
                    ->orderBy('order','ASC')
                    ->where(function ($where) use ($query){
                        $where->where('payment_method.name','LIKE','%'.$query.'%')
                            ->orWhere('payment_method.code','LIKE','%'.$query.'%');
                    })->where('is_deleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                    ->paginate(10);

            } else {
                
                $data = PaymentMethod::select('payment_method.*', 
                        'payment_method_category.name as category_name', 
                        'payment_method_category.order as category_order', 
                        'active_method.*')
                    ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                    ->joinSub($activePaymentMethodProvider, 'active_method', function ($join) {
                        $join->on('payment_method.id', '=', 'active_method.id_payment_method');
                    })
                    ->with(array('availablePaymentProvider' => function($query){
                        $query->select('payment_method_provider.*',
                        'payment_provider.name as provider_name')
                        ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id");
                    }))
                    ->orderBy('status','DESC')
                    ->orderBy('category_order','ASC')
                    ->orderBy('order','ASC')
                    ->where('is_deleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                    ->paginate(10);
            }
            
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        
        } catch(\Exception $e){
            return response()->json([
                "status"    => "Failed",
                "error"     => $e->getMessage()
            ], 500);
        }       
    }

    public function getAllEnabledPaymentMethod(){
        try{
            // Get All Provider for each Payment Method
            $activePaymentMethodProvider = PaymentMethodProvider::select('payment_method_provider.id',
                                                    'payment_method_provider.id_payment_method',
                                                    'payment_provider.id as active_provider_id', 
                                                    'payment_provider.name as active_provider_name')
                                                ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                                ->where('isActive', PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["ACTIVE"])
                                                ->groupBy('id_payment_method');

            $data = PaymentMethodCategory::where(function ($where){
                        $where->where('status', PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["ENABLED"])
                            ->where('isDeleted', PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS['ACTIVE']);
                    })
                    ->with(array('enabledPaymentMethod'=> function($query) use ($activePaymentMethodProvider){
                        $query->select('payment_method.*', 
                            'active_method.active_provider_name', 
                            'active_method.active_provider_id',
                            'active_method.id as id_payment_method_provider')
                        ->joinSub($activePaymentMethodProvider, 'active_method', function ($join) {
                            $join->on('payment_method.id', '=', 'active_method.id_payment_method');
                        })
                        ->where('status', PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"])
                        ->with('paymentMethodProviderVariable')
                        ->orderBy('order','ASC');
                    }))
                    ->orderBy('order','ASC')
                    ->get();
            
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        
        } catch(\Exception $e){
            return response()->json([
                "status"    => "Failed",
                "error"     => $e->getMessage()
            ], 500);
        }       
    }

    public function getOne($id)
    {
        try {
            // Get All Provider for each Payment Method
            $activePaymentMethodProvider = PaymentMethodProvider::select('payment_method_provider.id',
                                                    'payment_method_provider.id_payment_method',
                                                    'payment_provider.id as active_provider_id', 
                                                    'payment_provider.name as active_provider_name')
                                                ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                                ->where('isActive', PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["ACTIVE"])
                                                ->groupBy('id_payment_method');

            $data   =   PaymentMethod::select('payment_method.*', 
                            'payment_method_category.name as category_name', 
                            'payment_method_category.order as category_order', 
                            'active_method.active_provider_name', 
                            'active_method.active_provider_id',
                            'active_method.id as id_payment_method_provider')
                        ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                        ->joinSub($activePaymentMethodProvider, 'active_method', function ($join) {
                            $join->on('payment_method.id', '=', 'active_method.id_payment_method');
                        })
                        ->with(array('availablePaymentProvider' => function($query){
                            $query->select('payment_method_provider.*',
                            'payment_provider.name as provider_name')
                            ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id");
                        }))
                        ->with('paymentMethodProviderVariable')
                        ->where('payment_method.id', $id)->first();
            
            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Get Data Success',
                'data'      =>  $data
            ]);
        } catch(\Exception $e){
            return response()->json([
                "status"    => "Failed",
                "error"     => $e->getMessage()
            ], 500);
        }   
    }

    public function store(Request $request)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'name'   => 'required|string',
				'code'	    => 'required|string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data          = new PaymentMethod();
            $data->name    = $request->input('name');
            $data->code    = $request->input('code');
            $data->status  = '0';   
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Payment Method added successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'name'   => 'string',
				'code'	    => 'string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = PaymentMethod::findOrFail($id);
            if ($request->input('name')) {
                $data->name = $request->input('name');
            }
            if ($request->input('code')) {
                $data->code = $request->input('code');
            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Payment Method updated successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $data = PaymentMethod::findOrFail($id);
            if ($data->status == '0') {
                $data->status = '1';
            }
            else{
                $data->status = '0';
            }
            $data->save();
            
    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Status Payment Method Updated Successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function destroy($id)
    {
        try{

            $delete = PaymentMethod::findOrFail($id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Payment Method deleted successfully"
              ]);
            } else {
              return response([
                "status"  => "failed",
                  "message"   => "Failed delete data"
              ]);
            }
        } catch(\Exception $e){
            return response([
            	"status"	=> "failed",
                "message"   => $e->getMessage()
            ]);
        }
    }

    public function getPaymentMethodByMethodProviderId($idPaymentMethodProvider)
    {
        try {
            $data   =   PaymentMethod::select('payment_method.*', 'payment_method_provider.id as id_payment_method_provider')
                        ->join("payment_method_provider", "payment_method.id", "=", "payment_method_provider.id_payment_method")
                        ->with('paymentMethodProviderVariable')
                        ->where('payment_method_provider.id', $idPaymentMethodProvider)->first();
            
            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Get Data Success',
                'data'      =>  $data
            ]);
        } catch(\Exception $e){
            return response()->json([
                "status"    => "Failed",
                "error"     => $e->getMessage()
            ], 500);
        }   
    }

    public function getAvailablePaymentMethodProvider($idPaymentMethod)
    {
        try {
            $data   =   PaymentProvider::select("payment_provider.*", 'payment_method_provider.id as id_payment_method_provider')
                        ->join("payment_method_provider", "payment_provider.id", "=", "payment_method_provider.id_payment_provider")
                        ->where('payment_method_provider.id_payment_method', $idPaymentMethod)->get();
            
            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Get Data Success',
                'data'      =>  $data
            ]);
        } catch(\Exception $e){
            return response()->json([
                "status"    => "Failed",
                "error"     => $e->getMessage()
            ], 500);
        }   
    }
    
}