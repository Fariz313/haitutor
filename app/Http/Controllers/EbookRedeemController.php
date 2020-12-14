<?php

namespace App\Http\Controllers;

use App\EbookRedeem;
use App\EbookRedeemDetail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use JWTAuth;

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
                            ->join("users", "ebook_redeem.id_customer", "=", "users.id")
                            ->where('is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                            ->with(array('customer', 'detail' => function($query){
                                $query->get();
                            }))
                            ->where('users.name','LIKE','%'.$query.'%')
                            ->paginate(10);
            } else {
                $data = EbookRedeem::where('is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                            ->with(array('customer', 'detail' => function($query){
                                $query->with(array('ebook'))->get();
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

            $data                   = new EbookRedeem();
            $data->invoice          = "";
            $data->id_customer      = $request->input('id_customer');
            $data->net_price        = $request->input('net_price');

            if($request->input('gross_price')){
                $data->gross_price  = $request->input('gross_price');
            } else {
                $data->gross_price  = $request->input('net_price');
            }

            $user = JWTAuth::parseToken()->authenticate();
            if($user->role == User::ROLE["PUBLISHER"]){
                // If Redeem is Requested by Publisher
                $data->status       = EbookRedeem::EBOOK_REDEEM_STATUS["PENDING"];
                $message            = "Request Claim Redeem Succeeded";
            } else {
                // If Redeem is Requested by Non-Publisher (Admin)
                $data->status       = EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"];
                $message            = "Claim Redeem Succeeded";
            }

            $data->save();
            $data->invoice          = "INVHT" . str_pad($data->id, 8, '0', STR_PAD_LEFT);

            if($request->input('validity_month')){
                $data->validity_month   = $request->input('validity_month');
                $data->expired_at       = date('Y-m-d H:i:s', strtotime($data->created_at . '+' . $data->validity_month . ' months'));
            }

            $data->save();

            $newData = array();
            foreach(json_decode(json_encode($request->input('ebooks')), FALSE) as $ebook){
                $dataDetail                 = new EbookRedeemDetail();
                $dataDetail->id_redeem      = $data->id;
                $dataDetail->id_ebook       = $ebook->id;
                $dataDetail->redeem_amount  = $ebook->amount;
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
                        ->with(array('customer', 'detail' => function($query){
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
            if($request->input('id_customer')){
                $data->id_customer      = $request->input('id_customer');
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
            $existingData   = EbookRedeemDetail::where('id_redeem', $data->id)->pluck('id')->toArray();
            foreach(json_decode(json_encode($request->input('ebooks')), FALSE) as $ebook){
                if($ebook->id){

                } else {
                    $dataDetail                 = new EbookRedeemDetail();
                    $dataDetail->id_redeem      = $data->id;
                    $dataDetail->id_ebook       = $ebook->id;
                    $dataDetail->redeem_amount  = $ebook->amount;
                    $dataDetail->save();
                    $dataDetail->redeem_code    = strtoupper(Str::random(2) . str_pad(substr($dataDetail->id, -2), 2, '0', STR_PAD_LEFT) . Str::random(2));
                    $dataDetail->save();
                }
                $dataDetail                 = EbookRedeemDetail::findOrFail($ebook->id);
                $dataDetail->id_redeem      = $data->id;
                $dataDetail->id_ebook       = $ebook->id;
                $dataDetail->redeem_amount  = $ebook->amount;
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
