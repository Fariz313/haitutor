<?php

namespace App\Http\Controllers;

use App\PaymentMethodCategory;
use Illuminate\Http\Request;

class PaymentCategoryController extends Controller
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
                $data   = PaymentMethodCategory::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->where('isDeleted', PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS["ACTIVE"]);
                })->orderBy('order','ASC')->paginate(10);
            } else {
                $data = PaymentMethodCategory::where('isDeleted', PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS["ACTIVE"])
                        ->orderBy('order','ASC')->paginate(10);
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

            $this->tidyOrder();

            $maxOrder = PaymentMethodCategory::selectRaw("MAX(payment_method_category.order) as maxOrder")
                        ->where('status', PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["ENABLED"])
                        ->where('isDeleted', PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS["ACTIVE"])
                        ->first()->maxOrder;

            $data           = new PaymentMethodCategory();
            $data->name     = $request->input('name');
            $data->status   = PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["ENABLED"];
            $data->order    = $maxOrder + 1;
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Insert Payment Category Succeeded'
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
            $data = PaymentMethodCategory::findOrFail($id);

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
            $data           = PaymentMethodCategory::findOrFail($id);
            $data->name     = $request->input('name');
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Payment Category Succeeded'
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
            $data           = PaymentMethodCategory::findOrFail($id);
            $message        = 'Payment Category Deleted';

            if($data->isDeleted == PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS["DELETED"]){
                $message    = 'Payment Category Already Deleted';
            } else {
                $data->isDeleted    = PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS["DELETED"];
                $data->save();
            }

            $this->tidyOrder();

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

    private function tidyOrder(){
        $data   = PaymentMethodCategory::where('status', PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["ENABLED"])
                    ->where('isDeleted', PaymentMethodCategory::PAYMENT_CATEGORY_DELETED_STATUS["ACTIVE"])
                    ->orderBy('order','ASC')->get();

        for ($i = 0; $i < count($data); $i++) {
            $data[$i]->order    = $i + 1;
            $data[$i]->save();
        }

        return $data;
    }

    public function enablePaymentCategory($id)
    {
        try {
            $data           = PaymentMethodCategory::findOrFail($id);
            $message        = 'Payment Category Enabled';

            if($data->status == PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["ENABLED"]){
                $message    = 'Payment Category Already Enabled';
            } else {
                $data->status   = PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["ENABLED"];
                $data->save();
            }

            $this->tidyOrder();

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

    public function disablePaymentCategory($id)
    {
        try {
            $data           = PaymentMethodCategory::findOrFail($id);
            $message        = 'Payment Category Disabled';

            if($data->status == PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["DISABLED"]){
                $message    = 'Payment Category Already Disabled';
            } else {
                $data->status   = PaymentMethodCategory::PAYMENT_CATEGORY_STATUS["DISABLED"];
                $data->save();
            }

            $this->tidyOrder();

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
}
