<?php

namespace App\Http\Controllers;

use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use FCM;
use JWTAuth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    const NotificationStatus = [
        "NEW"  => 0,
        "READ" => 1
    ];

    public function index()
    {
        try {
            $data = Notification::get();

            $status = 'Success';
            $message = "Get All Notification Data Succeed";
            return response()->json(compact('status','message','data'),200);
        } catch (\Throwable $th) {
            $status = 'Failed';
            $message = $th;
            $data = '';
            return response()->json(compact('status','message','data'),500);
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
            $data = new Notification();
            $data->sender_id = JWTAuth::parseToken()->authenticate()->id;
            $data->target_id = $request->input('target_id');
            $data->message = $request->input('message');
            $data->status = $request->input('status');
            $data->action = $request->input('action');
            $data->image = $request->input('image');
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
    			'message'	=> 'Notification added successfully'
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
        try{
            $data = Notification::findOrFail($id);
            $data->status = $request->input('status');
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Notification Updated',
                'data'      => $request->input('status')
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
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

    public function getNotifByTargetId($targetId){
        try {
            $tempDate = \Carbon\Carbon::today()->subDays(7);
            $data = Notification::where('target_id',$targetId)
                                ->where('created_at', '>=', $tempDate)
                                ->orderBy('created_at','desc')->get();

            $status = 'Success';
            $message = "Get Notification By Target Succeed";
            return response()->json(compact('status','message','data'),200);
        } catch (\Throwable $th) {
            $status = 'Failed';
            $message = $th;
            $data = '';
            return response()->json(compact('status','message','data'),500);
        }
    }

    public function pushNotification(Request $request)
    {
        $data = [
            "title" => "Notif Penting Woyy",
            "message" => "Harusnya Tetap Bisa",
            "sender_id" => 19,
            "target_id" => 30,
            'token_recipient' => "ev-IKguAS2Wz5UvUbaIyNM:APA91bHVffgSO4bwBDBsD0IJPWesrkw81IqM3EVoG_7YiQYBu0y8FJ_RLQKlcwCooMZkby__xy4GyUnSjvFSzifkaY-upgw69Sh1ZX3cwEys35Anbgvv6Gr6m24gL660zADQmTl5WAwC",
            'save_data' => true,
            'channel_name' => "CHANNEL_CHAT"
        ];
        // $data = [
        //     "title" => "Notif Penting Woyy",
        //     "message" => "Harusnya Tetap Bisa",
        //     "sender_id" => 19,
        //     "target_id" => 30,
        //     'token_recipient' => "f5Yvv6qHQTCOHZo_vATXT0:APA91bGbPPvX53U8uVsHwRYSnTuccx5mKFPD_tPLtiRWDxIL7F6mCjVWU_OIZsI480_5vrhQxKl4ml2YMKbVD2VPikRCodp4iF_COsIeyHquLrvlmx7xArNsANFJNd4W48qqUmxAmC2w",
        //     'save_data' => true,
        //     'channel_name' => "CHANNEL_CHAT"
        // ];
        $response = FCM::pushNotification($data);
        return $response;

    }

    public function markAllAsRead(Request $request){
        try {
            $targetId = JWTAuth::parseToken()->authenticate()->id;
            $data = Notification::where('target_id',$targetId);
            $data->update(['status' => NotificationController::NotificationStatus["READ"]]);

            $status = 'Success';
            $message = "Notification Marked All As Read";
            return response()->json(compact('status','message','data'),200);
        } catch (\Throwable $th) {
            $status = 'Failed';
            $message = $th;
            $data = '';
            return response()->json(compact('status','message','data'),500);
        }
    }
}
