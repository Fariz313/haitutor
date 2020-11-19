<?php

namespace App\Http\Controllers;

use App\Disbursement;
use App\Notification;
use App\User;
use Illuminate\Http\Request;
use JWTAuth;
use FCM;

class DisbursementController extends Controller
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
                $data = Disbursement::paginate(10);
            }else{
                $data = Disbursement::paginate(10);
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Disbursement Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Disbursement Data Failed'
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
    public function store(Request $request)
    {
        try{
            $data               = new Disbursement();
            $data->user_id      = JWTAuth::parseToken()->authenticate()->id;
            $data->token        = $request->input('token');
            $data->amount       = $request->input('amount');
            $data->information  = $request->input('information');
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
    			'message'	=> 'Request Disbursement Sent'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
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
            $data = Disbursement::where('id',$id)->first();
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Detail Disbursement Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Detail Disbursement Failed'
            ]);
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

    public function getDisbursementByUserId($userId){
        try {
            $data = Disbursement::where('user_id',$userId)->paginate(10);
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Disbursement By User Id Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Disbursement By User Id Failed'
            ]);
        }
    }

    public function acceptDisbursement(Request $request, $id){
        try {
            $data               = Disbursement::where('id',$id)->first();
            $data->status       = Disbursement::DisbursementStatus["ACCEPTED"];
            $data->information  = $request->input('information');
            $data->accepted_at  = date("Y-m-d H:i:s");
            $data->save();

            $userTutor          = User::findOrFail($data->user_id);

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => "Pengajuan Pencairan Token Anda disetujui",
                "sender_id" => JWTAuth::parseToken()->authenticate()->id,
                "target_id" => $userTutor->id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[9],
                'token_recipient' => $userTutor->firebase_token,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Accept Disbursement Success'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Accept Disbursement Failed'
            ], 500);
        }
    }

    public function rejectDisbursement(Request $request, $id){
        try {
            $data               = Disbursement::where('id',$id)->first();
            $data->status       = Disbursement::DisbursementStatus["REJECTED"];
            $data->information  = $request->input('information');
            $data->accepted_at  = date("Y-m-d H:i:s");
            $data->save();

            $userTutor          = User::findOrFail($data->user_id);

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => "Pengajuan Pencairan Token Anda ditolak",
                "sender_id" => JWTAuth::parseToken()->authenticate()->id,
                "target_id" => $userTutor->id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[9],
                'token_recipient' => $userTutor->firebase_token,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Accept Disbursement Success'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Accept Disbursement Failed'
            ], 500);
        }
    }
}
