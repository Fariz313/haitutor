<?php

namespace App\Http\Controllers;

use App\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use FCM;
use JWTAuth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        try{
            $data = new Notification();
            $data->sender_id = JWTAuth::parseToken()->authenticate()->id;
            $data->target_id = $request->input('target_id');
            $data->message = $request->input('message');
            $data->user_id = 
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Package added successfully'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
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

    public function getNotifByTargetId($targetId){
        try {
            $data = Notification::where('target_id',$targetId)->get();

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
            'token_recipient' => "eJLGS7DcQgab5nSaXaLI0P:APA91bGbcPEVfSdgbCU1iiqfq0YZDR_0iuyJ6lVeQwsBfojT2bj-_h6ksvPJOwIJ1W8HuSySANRltHUXZZ7YvNiDMEIlHrK8FuMYHjNHcX4zrztf3EfaABM0UESILgsaGbEr3sGxyiCZ"
        ];
        $response = FCM::pushNotification($data);
        return $response;

    }
}
