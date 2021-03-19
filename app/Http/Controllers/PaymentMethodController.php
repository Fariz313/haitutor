<?php

namespace App\Http\Controllers;

use App\Helpers\GoogleCloudStorageHelper;
use Illuminate\Http\Request;
use App\PaymentMethod;
use App\PaymentMethodCategory;
use App\PaymentMethodProvider;
use App\PaymentMethodProviderVariable;
use App\PaymentProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
                    ->where(function ($where) use ($query){
                        $where->where('payment_method.name','LIKE','%'.$query.'%')
                            ->orWhere('payment_method.code','LIKE','%'.$query.'%');
                    })->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"]);

                $allData = PaymentMethod::select('payment_method.*',
                    'payment_method_category.name as category_name',
                    'payment_method_category.order as category_order')
                    ->selectSub(function ($query) {
                        $query->selectRaw("0");
                    }, 'id_payment_method')
                    ->selectSub(function ($query) {
                        $query->selectRaw('0');
                    }, 'active_provider_id')
                    ->selectSub(function ($query) {
                        $query->selectRaw("''");
                    }, 'active_provider_name')
                    ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                    ->whereNotIn('payment_method.id', $data->pluck('id')->toArray())
                    ->where(function ($where) use ($query){
                        $where->where('payment_method.name','LIKE','%'.$query.'%')
                            ->orWhere('payment_method.code','LIKE','%'.$query.'%');
                    })
                    ->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                    ->union($data)
                    ->orderBy('status','DESC')
                    ->orderBy('category_order','ASC')
                    ->orderBy('order','ASC')
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
                    ->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"]);

                $allData = PaymentMethod::select('payment_method.*',
                    'payment_method_category.name as category_name',
                    'payment_method_category.order as category_order')
                    ->selectSub(function ($query) {
                        $query->selectRaw("0");
                    }, 'id_payment_method')
                    ->selectSub(function ($query) {
                        $query->selectRaw('0');
                    }, 'active_provider_id')
                    ->selectSub(function ($query) {
                        $query->selectRaw("''");
                    }, 'active_provider_name')
                    ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                    ->whereNotIn('payment_method.id', $data->pluck('id')->toArray())
                    ->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                    ->union($data)
                    ->orderBy('status','DESC')
                    ->orderBy('category_order','ASC')
                    ->orderBy('order','ASC')
                    ->paginate(10);
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $allData,
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
            // Get Category with Existing Active Payment Method
            $validCategory = DB::table('view_active_payment_method')
                    ->where('status_payment_method', PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"])->groupBy('id_payment_category')->pluck('id_payment_category')->toArray();

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
                    ->with(array('enabledPaymentMethod'=> function($query) use ($activePaymentMethodProvider, $validCategory){
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
                    ->whereIn('id', $validCategory)
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
                                                    'payment_method_provider.status',
                                                    'payment_provider.id as active_provider_id',
                                                    'payment_provider.name as active_provider_name')
                                                ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                                                ->where('isActive', PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["ACTIVE"])
                                                ->groupBy('id_payment_method');

            if(count($activePaymentMethodProvider->where('id_payment_method', $id)->get()) > 0){
                // Show Detail for Payment Method with Existing Active Payment Method Provider
                $data   =   PaymentMethod::select('payment_method.*',
                            'payment_method_category.name as category_name',
                            'payment_method_category.order as category_order',
                            'active_method.active_provider_name',
                            'active_method.active_provider_id',
                            'active_method.id as id_payment_method_provider',
                            'active_method.status as status_payment_method_provider')
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
            } else {
                // Show Detail for Payment Method with No Existing Active Payment Method Provider
                $data   =   PaymentMethod::select('payment_method.*',
                            'payment_method_category.name as category_name',
                            'payment_method_category.order as category_order')
                        ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                        ->with(array('availablePaymentProvider' => function($query){
                            $query->select('payment_method_provider.*',
                            'payment_provider.name as provider_name')
                            ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id");
                        }))
                        ->with('paymentMethodProviderVariable')
                        ->where('payment_method.id', $id)->first();
            }

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

            $data                       = new PaymentMethod();

            if($request->input('id_payment_category')){
                $data->id_payment_category  = $request->input('id_payment_category');
            } else {
                $data->id_payment_category  = 0;
            }

            $maxOrder = PaymentMethod::selectRaw("MAX(payment_method.order) as maxOrder")
                        ->where('id_payment_category', $data->id_payment_category)
                        ->where('status', PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"])
                        ->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                        ->first()->maxOrder;

            $data->name                 = $request->input('name');
            $data->code                 = $request->input('code');
            if($request->file('icon')){
                $data->icon             = GoogleCloudStorageHelper::put($request->file('icon'), '/photos/payment_method', 'image', Str::random(3));
            } else {
                $data->icon             = 'credit_card.png';
            }
            $data->status               = 0;
            $data->order                = $maxOrder + 1;
            $data->isDeleted           = PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"];
            $data->save();

            if ($request->input('id_payment_provider')) {
                $dataPaymentProvider = new PaymentMethodProvider();
                $dataPaymentProvider->id_payment_method     = $data->id;
                $dataPaymentProvider->id_payment_provider   = $request->input('id_payment_provider');
                $dataPaymentProvider->status                = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["ENABLED"];
                $dataPaymentProvider->isActive              = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["ACTIVE"];
                $dataPaymentProvider->save();

                $provider                                   = PaymentProvider::findOrFail($dataPaymentProvider->id_payment_provider);
                $data->payment_provider_id                  = $provider->id;
                $data->payment_provider_name                = $provider->name;
                $data->payment_method_provider_id           = $dataPaymentProvider->id;
            }

            $this->tidyOrder();

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Payment Method added successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
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

            if ($request->input('id_payment_category')) {
                $data->id_payment_category = $request->input('id_payment_category');
            }
            if ($request->input('name')) {
                $data->name = $request->input('name');
            }
            if ($request->input('code')) {
                $data->code = $request->input('code');
            }
            if($request->file('icon')){
                GoogleCloudStorageHelper::delete('/photos/payment_method'.$data->icon);
                $data->icon  = GoogleCloudStorageHelper::put($request->file('icon'), '/photos/payment_method', 'image', Str::random(3));
            }

            $data->save();

            if ($request->input('id_payment_method_provider')) {
                $dataPaymentProvider = PaymentMethodProvider::where('id_payment_method', $data->id)->get();

                foreach($dataPaymentProvider as $provider){
                    if($provider->id == $request->input('id_payment_method_provider')){
                        $provider->isActive = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["ACTIVE"];

                        $dataProvider                               = PaymentProvider::findOrFail($provider->id_payment_provider);
                        $data->payment_provider_id                  = $dataProvider->id;
                        $data->payment_provider_name                = $dataProvider->name;
                        $data->payment_method_provider_id           = $provider->id;
                    } else {
                        $provider->isActive = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["NON_ACTIVE"];
                    }
                    $provider->save();
                }
            }

            $this->tidyOrder();

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Payment Method updated successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function enablePaymentMethod($id)
    {
        try{

            $data = PaymentMethod::findOrFail($id);

            if($data->status == PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"]){
                return response()->json([
                    'status'	=> 'Failed',
                    'message'	=> 'Payment Method was already enabled'
                ], 201);

            } else {
                // Get Category with Existing Active Payment Method
                $activePaymentMethodProvider = DB::table('view_active_payment_method')
                        ->where('id_payment_method', $id)->get();
                if(count($activePaymentMethodProvider) > 0){
                    $data->status = PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"];
                    $data->save();

                    $this->tidyOrder();

                    return response()->json([
                        'status'	=> 'Success',
                        'message'	=> 'Payment Method Enabled',
                        'data'      => $data
                    ], 201);

                } else {
                    return response()->json([
                        'status'	=> 'Failed',
                        'message'	=> "Payment Method doesn't have active provider"
                    ], 201);

                }
            }

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function disablePaymentMethod($id)
    {
        try{

            $data = PaymentMethod::findOrFail($id);

            if($data->status == PaymentMethod::PAYMENT_METHOD_STATUS["DISABLED"]){
                return response()->json([
                    'status'	=> 'Failed',
                    'message'	=> 'Payment Method was already disabled'
                ], 201);

            } else {
                $data->status = PaymentMethod::PAYMENT_METHOD_STATUS["DISABLED"];
                $data->save();

                $this->tidyOrder();

                return response()->json([
                    'status'	=> 'Success',
                    'message'	=> 'Payment Method Disabled',
                    'data'      => $data
                ], 201);
            }

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try{
            $data   = PaymentMethod::findOrFail($id);

            if($data->isDeleted == PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["DELETED"]){
                return response()->json([
                    'status'	=> 'Failed',
                    'message'	=> 'Payment Method was already deleted'
                ], 200);

            } else {
                // Set Payment Method
                $data->isDeleted   = PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["DELETED"];
                $data->status       = PaymentMethod::PAYMENT_METHOD_STATUS["DISABLED"];
                $data->order        = 0;
                $data->save();

                // Set Payment Method Provider
                $dataPaymentMethodProvider = PaymentMethodProvider::where('id_payment_method', $id)->get();
                foreach($dataPaymentMethodProvider as $methodProvider){
                    $methodProvider->status     = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["DISABLED"];
                    $methodProvider->isActive   = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["NON_ACTIVE"];
                    $methodProvider->isDeleted  = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["DELETED"];

                    // Set Payment Method Provider Variable
                    $dataPaymentMethodProviderVariable = PaymentMethodProviderVariable::where('id_payment_method_provider', $methodProvider->id)->get();
                    foreach($dataPaymentMethodProviderVariable as $variable){
                        $variable->delete();
                    }

                    $methodProvider->save();
                }

                $this->tidyOrder();

                return response()->json([
                    'status'	=> 'Success',
                    'message'	=> 'Payment Method Deleted',
                    'data'      => $data
                ], 200);
            }

        } catch(\Exception $e){
            return response([
            	"status"	=> "Failed",
                "message"   => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentMethodByMethodProviderId($idPaymentMethodProvider)
    {
        try {
            $data   =   PaymentMethod::select('payment_method.*',
                            'payment_method_provider.id as id_payment_method_provider',
                            'payment_provider.name as provider_name',
                            'payment_method_category.name as category_name')
                        ->join("payment_method_provider", "payment_method.id", "=", "payment_method_provider.id_payment_method")
                        ->join("payment_provider", "payment_method_provider.id_payment_provider", "=", "payment_provider.id")
                        ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                        ->with('paymentMethodProviderVariable')
                        ->with(array('paymentMethodProviderVariable' => function($query) use ($idPaymentMethodProvider){
                            $query->where('payment_method_provider.id', $idPaymentMethodProvider);
                        }))
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

    private function tidyOrder(){
        $dataCategory  = PaymentMethod::where('status', PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"])
                    ->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                    ->orderBy('id_payment_category','ASC')
                    ->orderBy('order','ASC')->groupBy('id_payment_category')->pluck('id_payment_category')->toArray();

        for ($i = 0; $i < count($dataCategory); $i++) {
            $data   = PaymentMethod::where('status', PaymentMethod::PAYMENT_METHOD_STATUS["ENABLED"])
                    ->where('isDeleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                    ->where('id_payment_category', $dataCategory[$i])
                    ->orderBy('id_payment_category','ASC')
                    ->orderBy('order','ASC')->get();

            for ($j = 0; $j < count($data); $j++) {
                $data[$j]->order    = $j + 1;
                $data[$j]->save();
            }
        }
    }
    public function setOrderPaymentMethod(Request $request)
    {
        try {
            if($request->input('id_methods')){
                $id_methods = $request->input('id_methods');

                for ($i = 0; $i < count($id_methods); $i++) {
                    $dataMethod         = PaymentMethod::findOrFail($id_methods[$i]);
                    $dataMethod->order  = $i + 1;
                    $dataMethod->save();
                }

                $this->tidyOrder();

                return $this->getAll(new Request());

            } else {
                $this->tidyOrder();

                return $this->getAll(new Request());
            }

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

}
