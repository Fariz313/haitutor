<?php

namespace App\Http\Controllers;

use App\PaymentMethod;
use App\PaymentMethodProvider;
use App\PaymentMethodProviderVariable;
use App\PaymentProvider;
use App\PaymentProviderVariable;
use Illuminate\Http\Request;

class PaymentProviderController extends Controller
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
                $query  = $request->get('search');
                $data   = PaymentProvider::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->where('isDeleted', PaymentProvider::PAYMENT_PROVIDER_DELETED_STATUS["ACTIVE"]);
                })->paginate(10);
            } else {
                $data = PaymentProvider::where('isDeleted', PaymentProvider::PAYMENT_PROVIDER_DELETED_STATUS["ACTIVE"])
                        ->with(array('paymentMethod' => function($query){
                            $query->join("payment_method", "payment_method_provider.id_payment_method", "=", "payment_method.id")
                            ->select('payment_method_provider.*', 'payment_method.name as payment_method_name');
                        }))->paginate(10);
            }
            
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
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
    public function store(Request $request)
    {
        try {
            $data           = new PaymentProvider();
            $data->name     = $request->input('name');
            $data->status   = PaymentProvider::PAYMENT_PROVIDER_STATUS["ENABLED"];
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Insert Payment Provider Succeeded'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
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
            $data = PaymentProvider::where('id', $id)->with('providerVariables')->first();

            $dataPaymentMethod = PaymentMethod::select('payment_method.*', 
                                        'payment_method_category.name as category_name',
                                        'payment_method_provider.id as id_payment_method_provider',
                                        'payment_method_provider.status as status_payment_method_provider')
                                    ->selectSub(function ($query) {
                                        $query->selectRaw('1');
                                    }, 'isIncluded')
                                    ->join("payment_method_provider", "payment_method.id", "=", "payment_method_provider.id_payment_method")
                                    ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                                    ->where('payment_method_provider.id_payment_provider', $id)
                                    ->where('is_deleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                                    ->where('payment_method_provider.isDeleted', PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["ACTIVE"]);

            $allPaymentMethod = PaymentMethod::select('payment_method.*', 
                                        'payment_method_category.name as category_name',
                                        'payment_method_provider.id as id_payment_method_provider',
                                        'payment_method_provider.status as status_payment_method_provider')
                                    ->selectSub(function ($query) {
                                        $query->selectRaw('0');
                                    }, 'isIncluded')
                                    ->join("payment_method_provider", "payment_method.id", "=", "payment_method_provider.id_payment_method")
                                    ->join("payment_method_category", "payment_method.id_payment_category", "=", "payment_method_category.id")
                                    ->where('is_deleted', PaymentMethod::PAYMENT_METHOD_DELETED_STATUS["ACTIVE"])
                                    ->where('payment_method_provider.isDeleted', PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["ACTIVE"])
                                    ->whereNotIn('payment_method.id', $dataPaymentMethod->pluck('id')->toArray())
                                    ->union($dataPaymentMethod)
                                    ->orderBy('isIncluded', 'DESC')
                                    ->get();

            return response()->json([
                'status'            =>  'Success',
                'data'              =>  $data,
                'payment_method'    =>  $allPaymentMethod,
                'message'           =>  'Get Data Success'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
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
        try {
            $data           = PaymentProvider::findOrFail($id);
            $data->name     = $request->input('name');
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Payment Provider Succeeded'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
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
            $data           = PaymentProvider::findOrFail($id);
            $message        = '';

            if($data->isDeleted == PaymentProvider::PAYMENT_PROVIDER_DELETED_STATUS["DELETED"]){
                $message    = 'Payment Provider Already Deleted';
            } else {
                $data->isDeleted    = PaymentProvider::PAYMENT_PROVIDER_DELETED_STATUS["DELETED"];
                $data->save();
                $message            = 'Payment Provider Deleted';
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function enablePaymentProvider($id)
    {
        try {
            $data           = PaymentProvider::findOrFail($id);
            $message        = 'Payment Provider Enabled';

            if($data->status == PaymentProvider::PAYMENT_PROVIDER_STATUS["ENABLED"]){
                $message    = 'Payment Provider Already Enabled';
            } else {
                $data->status   = PaymentProvider::PAYMENT_PROVIDER_STATUS["ENABLED"];
                $data->save();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function disablePaymentProvider($id)
    {
        try {
            $data           = PaymentProvider::findOrFail($id);
            $message        = 'Payment Provider Disabled';

            if($data->status == PaymentProvider::PAYMENT_PROVIDER_STATUS["DISABLED"]){
                $message    = 'Payment Provider Already Disabled';
            } else {
                $data->status   = PaymentProvider::PAYMENT_PROVIDER_STATUS["DISABLED"];
                $data->save();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function includePaymentMethod(Request $request, $paymentMethodId)
    {
        try {
            $providerId     = $request->input('provider_id');
            $data           = PaymentMethodProvider::where('id_payment_method', $paymentMethodId)
                                ->where('id_payment_provider', $providerId)->first();
            $message        = 'Payment Method Included';

            if($data != null){
                if($data->isDeleted == PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["DELETED"]){
                    $data->status       = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["DISABLED"];
                    $data->isActive     = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_ACTIVE_STATUS["NON_ACTIVE"];
                    $data->isDeleted    = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["ACTIVE"];
                    $message            = 'Payment Method Reincluded';
                    $data->save();
                } else {
                    $message            = 'Payment Method Exist';
                }
            } else {
                $data                       = new PaymentMethodProvider();
                $data->id_payment_method    = $paymentMethodId;
                $data->id_payment_provider  = $providerId;
                $data->status               = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["ENABLED"];
                $data->save();
                $message                    = 'Payment Method Included';
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function excludePaymentMethod(Request $request, $paymentMethodId)
    {
        try {
            $providerId     = $request->input('provider_id');
            $data           = PaymentMethodProvider::where('id_payment_method', $paymentMethodId)
                                ->where('id_payment_provider', $providerId)->first();
            $message        = '';

            if($data != null){
                if($data->isDeleted == PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["DELETED"]){
                    $message            = 'Payment Method Already Excluded';
                } else {
                    $data->isDeleted    = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_DELETED_STATUS["DELETED"];
                    $message            = 'Payment Method Excluded';
                    $data->save();
                }
            } else {
                $message                = 'Payment Method Not Exist';
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function enablePaymentMethodProvider($idPaymentMethodProvider)
    {
        try {
            $data           = PaymentMethodProvider::findOrFail($idPaymentMethodProvider);
            $message        = 'Payment Method Provider Enabled';

            if($data->status == PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["ENABLED"]){
                $message    = 'Payment Method Provider Already Enabled';
            } else {
                $data->status   = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["ENABLED"];
                $data->save();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function disablePaymentMethodProvider($idPaymentMethodProvider)
    {
        try {
            $data           = PaymentMethodProvider::findOrFail($idPaymentMethodProvider);
            $message        = 'Payment Method Provider Disabled';

            if($data->status == PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["DISABLED"]){
                $message    = 'Payment Method Provider Already Disabled';
            } else {
                $data->status   = PaymentMethodProvider::PAYMENT_METHOD_PROVIDER_STATUS["DISABLED"];
                $data->save();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    // ============================= PAYMENT PROVIDER VARIABLE ======================================

    public function getAllPaymentProviderVariable(Request $request)
    {
        try {
            $env    = 0;
            if($request->get('environment')){
                $env    = $request->get('environment');
            }

            if($request->get('search')){
                $query  = $request->get('search');
                
                $data   = PaymentProviderVariable::where(function ($where) use ($query, $env){
                    $where->where('variable','LIKE','%'.$query.'%')
                    ->where('environment', $env)
                    ->where('isDeleted', PaymentProviderVariable::PAYMENT_PROVIDER_VAR_DELETED_STATUS["ACTIVE"]);
                })->paginate(10);
            } else {
                $data = PaymentProviderVariable::where('isDeleted', PaymentProviderVariable::PAYMENT_PROVIDER_VAR_DELETED_STATUS["ACTIVE"])
                        ->where('environment', $env)->paginate(10);
            }
            
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentProviderVariableById($id)
    {
        try {
            $data = PaymentProviderVariable::findOrFail($id);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function addPaymentProviderVariable(Request $request)
    {
        try {
            $environment        = $request->input('environment');
            $variable           = $request->input('variable');
            $paymentProviderId  = $request->input('id_payment_provider');

            $data           = PaymentProviderVariable::where('id_payment_provider', $paymentProviderId)
                                ->where('environment', $environment)
                                ->where('variable', $variable)->first();

            if($data == null){
                $data                       = new PaymentProviderVariable();
                $data->id_payment_provider  = $paymentProviderId;
                $data->environment          = $environment;
                $data->variable             = $variable;
                $data->value                = $request->input('value');
                $data->save();
                $message                    = 'Payment Provider Variable Added';
            } else {
                $message                    = 'Payment Provider Variable Already Exists';
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function updatePaymentProviderVariable(Request $request, $id)
    {
        try {
            $data           = PaymentProviderVariable::findOrFail($id);

            if($request->input('variable')){
                $data->variable = $request->input('variable');
            }

            if($request->input('value')){
                $data->value    = $request->input('value');
            }

            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Payment Provider Variable Succeeded'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function deletePaymentProviderVariable($id)
    {
        try {
            $data           = PaymentProviderVariable::findOrFail($id);
            $data->delete();
            $message        = 'Payment Provider Variable Deleted';

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    // ========================== END PAYMENT PROVIDER VARIABLE ======================================

    // ============================= PAYMENT METHOD PROVIDER VARIABLE ======================================

    public function getAllPaymentMethodProviderVariable(Request $request)
    {
        try {
            if($request->get('search')){
                $query  = $request->get('search');
                
                $data   = PaymentMethodProviderVariable::where(function ($where) use ($query){
                    $where->where('variable','LIKE','%'.$query.'%');
                })->paginate(10);
            } else {
                $data = PaymentMethodProviderVariable::paginate(10);
            }
            
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentMethodProviderVariableById($id)
    {
        try {
            $data = PaymentMethodProviderVariable::findOrFail($id);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function addPaymentMethodProviderVariable(Request $request)
    {
        try {
            $variable           = $request->input('variable');
            $paymentMethodId    = $request->input('id_payment_method_provider');

            $data           = PaymentMethodProviderVariable::where('id_payment_method_provider', $paymentMethodId)
                                ->where('variable', $variable)->first();

            if($data == null){
                $data                               = new PaymentMethodProviderVariable();
                $data->id_payment_method_provider   = $paymentMethodId;
                $data->variable                     = $variable;
                $data->value                        = $request->input('value');
                $data->save();
                $message                    = 'Payment Method Provider Variable Added';
            } else {
                $message                    = 'Payment Method Provider Variable Already Exists';
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function updatePaymentMethodProviderVariable(Request $request, $id)
    {
        try {
            $data           = PaymentMethodProviderVariable::findOrFail($id);

            if($request->input('variable')){
                $data->variable = $request->input('variable');
            }

            if($request->input('value')){
                $data->value    = $request->input('value');
            }

            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Payment Method Provider Variable Succeeded'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function deletePaymentMethodProviderVariable($id)
    {
        try {
            $data           = PaymentMethodProviderVariable::findOrFail($id);
            $data->delete();
            $message        = 'Payment Method Provider Variable Deleted';

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    // ========================== END PAYMENT METHOD PROVIDER VARIABLE ======================================
}
