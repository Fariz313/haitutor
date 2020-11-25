<?php

namespace App\Http\Controllers;

use App\Disbursement;
use App\Notification;
use App\User;
use App\TutorDetail;
use App\TutorDoc;
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
                $data = Disbursement::select("disbursement.*")
                                       ->where("user_table.name", "LIKE", "%".$query."%")
                                       ->join("users as user_table", "user_table.id", "=", "disbursement.user_id")
                                       ->with(array("user" => function ($query) {
                                            $query->select("id", "email", "name", "role");
                                        }))->paginate(10);
            }else{
                $data = Disbursement::with(array("user" => function ($query) {
                    $query->select("id", "email", "name", "role");
                }))->paginate(10);
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
                'data'      => $data,
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
            $data = Disbursement::where('id',$id)->with(array("user" => function ($query) {
                $query->select("id", "email", "name", "role");
            }))->first();
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
            $data = Disbursement::where('user_id',$userId)->orderBy('created_at','DESC')->paginate(10);
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
                'message'   =>  'Reject Disbursement Success'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Reject Disbursement Failed'
            ], 500);
        }
    }

    public function getLatestPending(){
        try {
            $userTutor          = JWTAuth::parseToken()->authenticate();

            $data               = Disbursement::where('user_id',$userTutor->id)
                                    ->where('status', Disbursement::DisbursementStatus["PENDING"])->latest('id')->first();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Latest Pending Disbursement Succeeded'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Latest Pending Disbursement Failed'
            ], 500);
        }
    }

    public function cancelDisbursement(Request $request, $id){
        try {
            $data               = Disbursement::where('id',$id)->first();
            $data->status       = Disbursement::DisbursementStatus["REJECTED"];
            $data->information  = "Dibatalkan oleh User";
            $data->accepted_at  = date("Y-m-d H:i:s");
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Cancel Disbursement Success'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Cancel Disbursement Failed'
            ], 500);
        }
    }

    public function checkRequirements(){
        // Requirements:
        // 1. NIK and No Rek filled
        // 2. KTP and No Rek Documents Uploaded

        try {
            $userId             = JWTAuth::parseToken()->authenticate()->id;

            $isApplicable       = true;
            $isKTPVerified      = false;
            $isRekeningVerified = false;

            $errorMsg           = "";
            $userData           = User::where('id', $userId)
                                        ->with(array(
                                            'detail', 'tutorDoc'=>function($query) use ($userId){
                                                $query->where(function($q) use ($userId) {
                                                    $q->whereIn('id', $q->selectRaw('MAX(id)')->where('tutor_id', $userId)->groupBy('type'));
                                                });
                                            }))->first();

            foreach($userData->tutorDoc as $document){
                if($document->type == TutorDoc::TutorDocType["KTP"] && $document->status == TutorDoc::TutorDocStatus["VERIFIED"]){
                    $isKTPVerified = true;
                } else if($document->type == TutorDoc::TutorDocType["NO_REKENING"] && $document->status == TutorDoc::TutorDocStatus["VERIFIED"]){
                    $isRekeningVerified = true;
                }
            }

            if($isApplicable && ($userData->detail->nik == null || $userData->detail->nik == "")){
                $isApplicable   = false;
                $data           = 1;
                $errorMsg       = "NIK is Empty";
            } else if($isApplicable && ($userData->detail->no_rekening == null || $userData->detail->no_rekening == "")){
                $isApplicable   = false;
                $data           = 2;
                $errorMsg       = "No Rekening is Empty";
            } else if($isApplicable && !$isKTPVerified){
                $isApplicable   = false;
                $data           = 3;
                $errorMsg       = "KTP Document is Empty / Not Verified";
            } else if($isApplicable && !$isRekeningVerified){
                $isApplicable   = false;
                $data           = 4;
                $errorMsg       = "No Rekening Document is Empty / Not Verified";
            }
            
            if($isApplicable){
                return response()->json([
                    'status'    =>  'Success',
                    'data'      =>  0,
                    'message'   =>  'No Error'
                ], 201);
            } else {
                return response()->json([
                    'status'    =>  'Failed',
                    'data'      =>  $data,
                    'message'   =>  $errorMsg
                ], 500);
            }
            
        } catch(\Exception $e){
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  $e->getMessage(),
                'message'   =>  'Check Disbursement Requirements Error'
            ], 500);
        }
    }
}
