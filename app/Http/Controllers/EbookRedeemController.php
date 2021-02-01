<?php

namespace App\Http\Controllers;

use App\EbookLibrary;
use App\EbookRedeem;
use App\EbookRedeemDetail;
use App\EbookRedeemHistory;
use App\Notification;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use FCM;

use function PHPSTORM_META\elementType;

class EbookRedeemController extends Controller
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
                $data   = EbookRedeem::select("ebook_redeem.*")
                            ->where(function ($where) use ($query){
                                $where->where('ebook_redeem.redeem_invoice','LIKE','%'.$query.'%')
                                ->orWhere('ebook_redeem.request_invoice','LIKE','%'.$query.'%')
                                ->orWhere('users.name','LIKE','%'.$query.'%');
                            })
                            ->join("users", "ebook_redeem.id_customer", "=", "users.id")
                            ->with(array('customer', 'marketing', 'publisher', 'detail' => function($query){
                                $query->get();
                            }))
                            ->where('ebook_redeem.is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                            ->paginate(10);
            } else {
                $data = EbookRedeem::where('is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                            ->with(array('customer', 'marketing', 'publisher', 'detail' => function($query){
                                $query->with(array('ebook'))->get();
                            }))
                            ->where('ebook_redeem.is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
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
                'request_invoice'   => 'required',
                'id_customer'       => 'required',
                'id_marketing'      => 'required',
                'net_price'         => 'required'
            ]);

            if($validator->fails()){
                return response()->json([
                    'status'    => 'Failed',
                    'error'     => $validator->errors()
                ],400);
            }

            $data                   = new EbookRedeem();
            $data->redeem_invoice   = "";
            $data->request_invoice  = $request->input('request_invoice');
            $data->id_customer      = $request->input('id_customer');
            $data->id_marketing     = $request->input('id_marketing');
            $data->net_price        = $request->input('net_price');

            if($request->input('id_publisher')){
                $data->id_publisher     = $request->input('id_publisher');
            }

            if($request->input('gross_price')){
                $data->gross_price  = $request->input('gross_price');
            } else {
                $data->gross_price  = $request->input('net_price');
            }

            $user = JWTAuth::parseToken()->authenticate();
            if($user->role == Role::ROLE["PUBLISHER"]){
                // If Redeem is Requested by Publisher
                $data->status       = EbookRedeem::EBOOK_REDEEM_STATUS["PENDING"];
                $message            = "Request Claim Redeem Succeeded";

                $dataNotif = [
                    "title"         => "HaiTutor",
                    "message"       => $user->name . " mengajukan permohonan redeem ebook",
                    "channel_name"  => Notification::CHANNEL_NOTIF_NAMES[15]
                ];
                FCM::pushNotificationAdmin($dataNotif);

            } else {
                // If Redeem is Requested by Non-Publisher (Admin)
                $data->status       = EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"];
                $message            = "Claim Redeem Succeeded";
            }

            $data->save();
            $data->redeem_invoice       = "INVHT" . str_pad($data->id, 8, '0', STR_PAD_LEFT);

            if($request->input('validity_month')){
                $data->validity_month   = $request->input('validity_month');
                $data->expired_at       = date('Y-m-d H:i:s', strtotime($data->created_at . '+' . $data->validity_month . ' months'));
            }

            $data->save();

            $newData = array();
            $ebookLimit = json_decode(json_encode($request->input('ebook_limit_array')), FALSE);
            foreach(json_decode(json_encode($request->input('ebook_id_array')), FALSE) as $idx => $ebookId){
                $dataDetail                 = new EbookRedeemDetail();
                $dataDetail->id_redeem      = $data->id;
                $dataDetail->id_ebook       = $ebookId;
                $dataDetail->redeem_amount  = $ebookLimit[$idx];
                $dataDetail->save();
                $dataDetail->redeem_code    = strtoupper(Str::random(2) . str_pad(substr($dataDetail->id, -2), 2, '0', STR_PAD_LEFT) . Str::random(2));
                $dataDetail->save();

                array_push($newData, $dataDetail);
            }

            $data = EbookRedeem::where('id', $data->id)->with(array('customer', 'detail' => function($query){
                $query->get();
            }))->first();

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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $data = EbookRedeem::where('is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                        ->with(array('customer', 'marketing', 'publisher', 'detail' => function($query){
                            $query->with(array('ebook'))->get();
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
            $data                   = EbookRedeem::findOrFail($id);

            if($data->status != EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"]){
                if($request->input('request_invoice')){
                    $data->request_invoice  = $request->input('request_invoice');
                }

                if($request->input('id_customer')){
                    $data->id_customer      = $request->input('id_customer');
                }

                if($request->input('id_marketing')){
                    $data->id_marketing     = $request->input('id_marketing');
                }

                if($request->input('id_publisher')){
                    $data->id_publisher     = $request->input('id_publisher');
                }

                if($request->input('net_price')){
                    $data->net_price        = $request->input('net_price');
                }

                if($request->input('validity_month')){
                    $data->validity_month   = $request->input('validity_month');
                    $data->expired_at       = date('Y-m-d H:i:s', strtotime($data->created_at . '+' . $data->validity_month . ' months'));
                }

                $data->save();

                $newData = array();
                $ebookId = json_decode(json_encode($request->input('ebook_id_array')), FALSE);
                $ebookAmount = json_decode(json_encode($request->input('ebook_limit_array')), FALSE);
                $existingEbook = EbookRedeemDetail::where('id_redeem', $id)->get();

                // Delete Nonmatch Ebook
                foreach($existingEbook as $exist){
                    if(!in_array($exist->id_ebook, $ebookId)){
                        $exist->delete();
                    }
                }

                // Check Update of Existing Ebook and Create the new one
                $existingEbookId = EbookRedeemDetail::where('id_redeem', $id)->pluck('id_ebook')->toArray();
                foreach($ebookId as $idx => $inputEbookId){
                    if(in_array($inputEbookId, $existingEbookId)){
                        $dataDetail                 = EbookRedeemDetail::where('id_ebook',$inputEbookId)->where('id_redeem', $id)->first();
                        $dataDetail->redeem_amount  = $ebookAmount[$idx];
                        $dataDetail->save();
                    } else {
                        $dataDetail                 = new EbookRedeemDetail();
                        $dataDetail->id_redeem      = $data->id;
                        $dataDetail->id_ebook       = $inputEbookId;
                        $dataDetail->redeem_amount  = $ebookAmount[$idx];
                        $dataDetail->save();
                        $dataDetail->redeem_code    = strtoupper(Str::random(2) . str_pad(substr($dataDetail->id, -2), 2, '0', STR_PAD_LEFT) . Str::random(2));
                        $dataDetail->save();
                    }
                    array_push($newData, $dataDetail);
                }

                $data = EbookRedeem::where('id', $data->id)->with(array('customer', 'detail' => function($query){
                    $query->get();
                }))->first();

                return response()->json([
                    'status'    =>  'Success',
                    'data'      =>  $data,
                    'message'   =>  'Update Claim Redeem Succeeded'
                ], 200);

            } else {
                return response()->json([
                    "status"   => "Failed",
                    "message"  => 'Ebook Redeem already accepted [Cannot be edited]'
                ], 500);
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
            $data   = EbookRedeem::findOrFail($id);
            if($data->status == EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"]){
                return response()->json([
                    'status'    =>  "Failed",
                    'message'   =>  "Data Claim Order Cannot be Changed"
                ], 200);

            } else if($data->is_deleted == EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["DELETED"]){
                return response()->json([
                    'status'    =>  "Failed",
                    'message'   =>  "Data Claim Order Already Deleted"
                ], 200);

            } else {
                $data->is_deleted = EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["DELETED"];
                $data->save();

                return response()->json([
                    'status'    =>  "Success",
                    'data'      =>  $data,
                    'message'   =>  "Data Claim Order Deleted"
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function acceptClaimRedeem($id)
    {
        try {
            $data           = EbookRedeem::findOrFail($id);
            $data->status   = EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"];
            $data->save();

            return response()->json([
                'status'    =>  "Success",
                'data'      =>  $data,
                'message'   =>  "Claim Order Accepted"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function rejectClaimRedeem($id)
    {
        try {
            $data           = EbookRedeem::findOrFail($id);
            $data->status   = EbookRedeem::EBOOK_REDEEM_STATUS["NON_ACTIVE"];
            $data->save();

            return response()->json([
                'status'    =>  "Success",
                'data'      =>  $data,
                'message'   =>  "Claim Order Rejected"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function doRedeem(Request $request)
    {
        try {
            $dataRedeemDetail   = EbookRedeemDetail::where('redeem_code', $request->input('redeem_code'))->first();

            $isValid            = true;
            $dataRedeem         = EbookRedeem::findOrFail($dataRedeemDetail->id_redeem);
            $today              = date('Y-m-d H:i:s');

            if($isValid && $today > $dataRedeem->expired_at){
                $isValid        = false;
                $message        = 'Redeem Code Expired';
            }

            if($isValid && $dataRedeemDetail->redeem_used >= $dataRedeemDetail->redeem_amount){
                $isValid        = false;
                $message        = 'Maximum Quota is exceeded';
            }

            if($isValid){
                $userId             = JWTAuth::parseToken()->authenticate()->id;

                $data = new EbookRedeemHistory();
                $data->id_redeem_detail     = $dataRedeemDetail->id;
                $data->id_user              = $userId;

                $dataLibrary                = EbookLibrary::where('id_user', $userId)->where('id_ebook', $dataRedeemDetail->id_ebook)->first();
                if($dataLibrary == null){
                    $newLibrary             = new EbookLibrary();
                    $newLibrary->id_user    = $userId;
                    $newLibrary->id_ebook   = $dataRedeemDetail->id_ebook;
                    $newLibrary->save();

                    $dataRedeemDetail->redeem_used      = $dataRedeemDetail->redeem_used + 1;
                    $dataRedeemDetail->save();

                    $data->save();

                    return response()->json([
                        'status'    =>  'Success',
                        'data'      =>  $data,
                        'message'   =>  'Ebook Redeem Succeeded'
                    ], 200);
                } else {
                    return response()->json([
                        'status'    =>  'Failed',
                        'message'   =>  'This Ebook already exist at The Library'
                    ], 200);
                }
            } else {
                return response()->json([
                    'status'    =>  'Failed',
                    'message'   =>  $message
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getAllEbookRedeemHistory(Request $request)
    {
        try {
            if($request->get('search')){
                $query  = $request->get('search');
                $data   = EbookRedeemHistory::select("ebook_redeem_history.*")
                            ->join("users", "ebook_redeem_history.id_user", "=", "users.id")
                            ->with(array('redeem_detail', 'user' => function($query){
                                $query->get();
                            }))
                            ->where('users.name','LIKE','%'.$query.'%')
                            ->paginate(10);
            } else {
                $data = EbookRedeemHistory::select("ebook_redeem_history.*")
                            ->with(array('redeem_detail', 'user' => function($query){
                                $query->get();
                            }))
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

    public function getDetailEbookRedeemHistory($id)
    {
        try {
            $data = EbookRedeemHistory::select("ebook_redeem_history.*")
                        ->with(array('redeem_detail', 'user' => function($query){
                            $query->get();
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

    public function getListCustomer()
    {
        try {
            $roleList   = array(Role::ROLE["SCHOOL"], Role::ROLE["COMPANY"]);
            $data       = User::whereIn('role', $roleList)->get();

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

    public function getHistoryByRedeemDetail(Request $request, $idRedeemDetail)
    {
        try {
            if($request->get('search')){
                $query  = $request->get('search');
                $data   = EbookRedeemHistory::select("ebook_redeem_history.*")
                            ->join("users", "ebook_redeem_history.id_user", "=", "users.id")
                            ->with(array('redeem_detail', 'user' => function($query){
                                $query->get();
                            }))
                            ->where('users.name','LIKE','%'.$query.'%')
                            ->where('id_redeem_detail', $idRedeemDetail)
                            ->paginate(10);
            } else {
                $data = EbookRedeemHistory::select("ebook_redeem_history.*")
                            ->with(array('redeem_detail', 'user' => function($query){
                                $query->get();
                            }))
                            ->where('id_redeem_detail', $idRedeemDetail)
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
}
