<?php

namespace App\Http\Controllers;

use App\EbookLibrary;
use App\EbookOrder;
use App\EbookOrderDetail;
use App\EbookRedeem;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;

class EbookOrderController extends Controller
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
                $data   = EbookOrder::select("ebook_order.*")
                            ->join("users", "ebook_order.id_customer", "=", "users.id")
                            ->where('is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                            ->with(array('customer', 'detail' => function($query){
                                $query->get();
                            }))
                            ->where('users.name','LIKE','%'.$query.'%')
                            ->where('ebook_order.is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                            ->paginate(10);
            } else {
                $data = EbookOrder::where('is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                            ->with(array('customer', 'detail' => function($query){
                                $query->with(array('ebook'))->get();
                            }))
                            ->where('ebook_order.is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                            ->paginate(10);
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

            $validator = Validator::make($request->all(), [
                'id_customer'   => 'required',
                'net_price'     => 'required'
            ]);

            if($validator->fails()){
                return response()->json([
                    'status'    => 'Failed',
                    'error'     => $validator->errors()
                ],400);
            }

            $data                   = new EbookOrder();
            $data->invoice          = "";
            $data->id_customer      = $request->input('id_customer');
            $data->net_price        = $request->input('net_price');

            if($request->input('id_publisher')){
                $data->id_publisher = $request->input('id_publisher');
            }

            if($request->input('gross_price')){
                $data->gross_price  = $request->input('gross_price');
            } else {
                $data->gross_price  = $request->input('net_price');
            }

            $user = JWTAuth::parseToken()->authenticate();
            $status                 = 'Success';
            if($user->role == Role::ROLE["PUBLISHER"]){
                // If Redeem is Requested by Publisher
                $data->status       = EbookOrder::EBOOK_ORDER_STATUS["PENDING"];
                $message            = "Request Manual Order Succeeded";
            } else {
                // If Redeem is Requested by Non-Publisher (Admin)
                $data->status       = EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"];
                $message            = "Manual Order Succeeded";
            }

            $data->save();
            $data->invoice          = "INVHT1" . str_pad($data->id, 7, '0', STR_PAD_LEFT);
            $data->save();

            $newData = array();
            foreach(json_decode(json_encode($request->input('ebook_id_array')), FALSE) as $ebookId){
                $dataDetail                 = new EbookOrderDetail();
                $dataDetail->id_order       = $data->id;
                $dataDetail->id_ebook       = $ebookId;
                $dataDetail->amount         = 1;
                
                $dataLibrary                = EbookLibrary::where('id_user', $data->id_customer)->where('id_ebook', $dataDetail->id_ebook)->first();
                if($dataLibrary == null){
                    // If Ebook Not Exist in Student Library
                    if($data->status == EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"]){
                        // If Manual Order Registered by Admin
                        $newLibrary             = new EbookLibrary();
                        $newLibrary->id_user    = $data->id_customer;
                        $newLibrary->id_ebook   = $dataDetail->id_ebook;
                        $newLibrary->save();
                    }

                    $dataDetail->save();
                    array_push($newData, $dataDetail);
                }
            }

            if(count($newData) == 0){
                $data->status       = EbookOrder::EBOOK_ORDER_STATUS["NON_ACTIVE"];
                $data->save();

                $status             = 'Failed';
                $message            = 'All Ebooks Already Exist in Student Library';
            }

            $data = EbookOrder::where('id', $data->id)->with(array('customer', 'detail' => function($query){
                $query->get();
            }))->first();

            return response()->json([
                'status'    =>  $status,
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $data = EbookOrder::where('is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                        ->with(array('customer', 'publisher', 'detail' => function($query){
                            $query->with(array('ebook' => function($query){
                                $query->with(array('ebookCategory', 'ebookPublisher' => function($query){
                                    $query->select('id','name', 'email');
                                }));
                            }))->get();
                        }))
                        ->where('id', $id)
                        ->first();
            
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
            $data                   = EbookOrder::findOrFail($id);
            if($data->status != EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"]){
                if($request->input('id_customer')){
                    $data->id_customer      = $request->input('id_customer');
                }

                if($request->input('id_publisher')){
                    $data->id_publisher = $request->input('id_publisher');
                }
    
                if($request->input('net_price')){
                    $data->net_price        = $request->input('net_price');
                }
    
                $data->save();
    
                $newData = array();

                $ebookId = json_decode(json_encode($request->input('ebook_id_array')), FALSE);
                $existingEbook = EbookOrderDetail::where('id_order', $id)->get();

                // Delete Nonmatch Ebook
                foreach($existingEbook as $exist){
                    if(!in_array($exist->id_ebook, $ebookId)){
                        $exist->delete();
                    }
                }

                // Check Update of Existing Ebook and Create the new one
                $existingEbookId = EbookOrderDetail::where('id_order', $id)->pluck('id_ebook')->toArray();
                foreach($ebookId as $inputEbookId){
                    if(!in_array($inputEbookId, $existingEbookId)){
                        $dataDetail                 = new EbookOrderDetail();
                        $dataDetail->id_order       = $data->id;
                        $dataDetail->id_ebook       = $inputEbookId;
                        $dataDetail->amount         = 1;
                        $dataDetail->save();
                    } else {
                        $dataDetail                 = EbookOrderDetail::where('id_ebook',$inputEbookId)->where('id_order', $id)->first();
                    }
                    
                    array_push($newData, $dataDetail);
                }

                if($data->status == EbookOrder::EBOOK_ORDER_STATUS["NON_ACTIVE"]){
                    $data->status = EbookOrder::EBOOK_ORDER_STATUS["PENDING"];
                    $data->save();
                }
    
                $data = EbookOrder::where('id', $data->id)->with(array('customer', 'detail' => function($query){
                    $query->get();
                }))->first();
    
                return response()->json([
                    'status'    =>  'Success',
                    'data'      =>  $data,
                    'message'   =>  'Update Manual Order Succeeded'
                ], 200);
            } else {
                return response()->json([
                    'status'    =>  'Failed',
                    'message'   =>  'Manual order already accepted [Cannot be edited]'
                ], 200);
            }
            
            
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
            $data   = EbookOrder::findOrFail($id);
            if($data->status == EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"]){
                return response()->json([
                    'status'    =>  "Failed",
                    'message'   =>  "Data Ebook Manual Order Cannot be Changed"
                ], 200);

            } else if($data->is_deleted == EbookOrder::EBOOK_ORDER_DELETED_STATUS["DELETED"]){
                return response()->json([
                    'status'    =>  "Failed",
                    'message'   =>  "Data Ebook Manual Order Already Deleted"
                ], 200);

            } else {
                $data->is_deleted = EbookOrder::EBOOK_ORDER_DELETED_STATUS["DELETED"];
                $data->status = EbookOrder::EBOOK_ORDER_STATUS["NON_ACTIVE"];
                $data->save();

                return response()->json([
                    'status'    =>  "Success",
                    'data'      =>  $data,
                    'message'   =>  "Data Manual Order Deleted"
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function acceptEbookManualOrder($id)
    {
        try {
            $data           = EbookOrder::findOrFail($id);
            $data->status   = EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"];

            $dataDetail     = EbookOrderDetail::where('id_order', $data->id)->get();
            $newData        = array();
            foreach($dataDetail as $ebook){
                $dataLibrary    = EbookLibrary::where('id_user', $data->id_customer)->where('id_ebook', $ebook->id_ebook)->first();
                if($dataLibrary == null){
                    $newLibrary             = new EbookLibrary();
                    $newLibrary->id_user    = $data->id_customer;
                    $newLibrary->id_ebook   = $ebook->id_ebook;
                    $newLibrary->save();

                    array_push($newData, $dataDetail);
                }
            }
            
            $data->save();

            $data = EbookOrder::where('id', $data->id)->with(array('customer', 'detail' => function($query){
                $query->get();
            }))->first();

            if(count($newData) == 0){
                $data->status       = EbookOrder::EBOOK_ORDER_STATUS["NON_ACTIVE"];
                $data->save();
                
                return response()->json([
                    'status'    =>  'Failed',
                    'message'   =>  "All Ebooks Already Exist in Student Library"
                ], 200);

            } else {
                return response()->json([
                    'status'    =>  'Success',
                    'data'      =>  $data,
                    'message'   =>  "Manual Order Accepted"
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function rejectEbookManualOrder($id)
    {
        try {
            $data           = EbookOrder::findOrFail($id);
            $data->status   = EbookOrder::EBOOK_ORDER_STATUS["NON_ACTIVE"];
            $data->save();

            return response()->json([
                'status'    =>  "Success",
                'data'      =>  $data,
                'message'   =>  "Manual Order Rejected"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }
}
