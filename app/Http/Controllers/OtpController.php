<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Otp;
use App\User;
use JWTAuth;
use Mail;
use Carbon\Carbon;
use View;

class OtpController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = Company::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('company_type','LIKE','%'.$query.'%');
                } )->paginate(10);
            }else{
                $data = Company::paginate(10);
            }

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }

    public function createOtpVerification($device_id)
    {
        try {
            $user           =   JWTAuth::parseToken()->authenticate();
            $otp            =   strval(rand(100000,999999));
            $clientIP       =   \Request::getClientIp(true);
            try {
                $otpData            = Otp::where('user_id',$user->id)
                                    ->where('type','verification')
                                    ->orderBy('created_at','desc')->first();
                if($otpData->status=='pending'){
                    $otpData->status = 'failed';
                    $otpData->save();
                }
            } catch (\Throwable $th) {

            }

            $data           =   new Otp();
            $data->user_id  =   $user->id;
            $data->user_ip  =   $clientIP;
            $data->otp      =   $otp;
            $data->type     =   'verification';
            $data->device_id = $device_id;
            if($data->save()){
                try{
                    Mail::send([], [], function ($message) use ($user, $otp)
                    {
                        $message->subject('Kode OTP Akun HaiTutor');
                        $message->to($user->email);
                        $view = View::make('otpVerification', [
                            'otp' => $otp,
                            "otp_title" => "Verifikasi Email Anda",
                            "otp_message" => "Anda mengajukan verifikasi email, berikut Kode OTP untuk verifikasi email Anda :",
                            "otp_type" => "email"
                        ]);

                        $html = $view->render();
                        $message->setBody($html,'text/html');

                    });
                    return response()->json([
                        'status'    =>  'success',
                        'Otp'       =>  'OTP is on sended to your emaail',
                        'message'   =>  'OTP is on sended to your emaail'],200);
                }catch(\throwable $e){
                    return response()->json([
                        'status'    =>  'failed',
                        'Otp'       =>  'No OTP crated',
                        'message'   =>  'cant create OTP crated'],403);
                }
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'Otp'       =>  'No OTP crated',
                'message'   =>  'cant create OTP crated'
            ]);
        }
    }

    public function verifying(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|max:6',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }
        try {
            $user               =   JWTAuth::parseToken()->authenticate();
            if($user->verified_at){
                return response()->json([
                    'status'    =>  'success',
                    'message'   =>  'Your account is verified'
                ]);
            }
            $otpData            = Otp::where('user_id',$user->id)
                                ->where('type','verification')
                                ->orderBy('created_at','desc')->first();
            $expiredDate        =$otpData->created_at->addDays(1);

            $today = Carbon::today();
            if($expiredDate > $today){
                if($request->input('otp')==$otpData->otp){
                    $otpData->status        =   'completed';
                    $otpData->verified_at   =   $today;
                    $user->status           =   'verified';
                    $otpData->save();
                    $user->save();
                    return response()->json([
                        'status'    =>  'success',
                        'message'   =>  'your account verified now'
                    ]);
                }else{
                    return response()->json([
                        'status'    =>  'failed',
                        'message'   =>  'Wrong OTP'
                    ]);
                }
            }else{
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Your OTP expired'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'message'   =>  'Failed verifying'
            ]);
        }
    }

    public function sendByEmail()
    {
        try{
            $user               = JWTAuth::parseToken()->authenticate();
            $otpData            = Otp::where('user_id',$user->id)
                                ->where('type','verification')
                                ->where('status','pending')
                                ->orderBy('created_at','desc')->first();
            $otp                =$otpData->otp;
            $expiredDate        =$otpData->created_at->addDays(1);
            $today              = Carbon::today();

            if($today>$expiredDate){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Your OTP expired'
                ]);
            }else if($otpData){
                Mail::send([], [], function ($message) use ($user, $otp)
                {
                    $message->subject('Contoh Otp');
                    $message->to($user->email);
                    $message->setBody('<p> Hi!! </p><h1>OTP Anda Adalah</h1><br/><h1><b>'.$otp.'</b></h1>','text/html');

                });
                return response()->json([
                    'status'    =>  'success',
                    'Otp'       =>  'OTP is on sended to your emaail',
                    'message'   =>  'OTP is on sended to your emaail'],200);
            }
            return response()->json([
                'status'    =>  'failed',
                'Otp'       =>  'OTP doesnt Sended',
                'message'   =>  'Failed to send otp to your email'],403);
        }catch(\throwable $e){
            return response()->json([
                'status'    =>  'failed',
                'Otp'       =>  'OTP doesnt Sended',
                'message'   =>  'Failed to send otp to your email'],403);
        }
    }

    function sendBySms(){
        try{
            $user               = JWTAuth::parseToken()->authenticate();
            $otpData            = Otp::where('user_id',$user->id)
                                ->where('type','verification')
                                ->where('status','pending')
                                ->orderBy('created_at','desc')->first();
            $otp                =$otpData->otp;
            $expiredDate        =$otpData->created_at->addDays(1);
            $today              = Carbon::today();

            if($today>$expiredDate){
                return response()->json([
                    'status'    =>  'failed',
                    'message'   =>  'Your OTP expired'
                ]);
            }else if($otpData){
                $username = "vokanesia";
                $apikey = "1b0d834c330fcee8e57456d7f96f7254";

                $postRequest = array(
                    'action' => 'sendsms',
                    'username' => $username,
                    'apikey' => $apikey,
                    'destination' => $user->contact,
                    'message' => "Your Tutor Vokeanesia Otp is ".$otp,
                );
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "http://smsapi.rosihanari.net/v2/restapi.php");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postRequest);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                curl_close($ch);

                // return $output;
                return response()->json([
                    'status'    =>  'success',
                    'Otp'       =>  'OTP is on sended to your Phone',
                    'message'   =>  'OTP is on sended to your Phone'],200);
            }
            return response()->json([
                'status'    =>  'failed',
                'Otp'       =>  'OTP doesnt Sended',
                'message'   =>  'Failed to send otp to your email'],403);
        }catch(\throwable $e){
            return response()->json([
                'status'    =>  'failed',
                'Otp'       =>  'OTP doesnt Sended',
                'message'   =>  'Failed to send otp to your email'],403);
        }
    }

    public function showOtp(){
        $otp = '8291821';
        return view('otpVerification', [
            'otp' => $otp,
            "otp_title" => "Verifikasi Email Anda",
            "otp_type" => "email",
            "no_hp" => "08123456789",
            "alamat" => "Perumahan Green Living Residence Blok C No. 14 Kecamatan Sukun, Kota Malang",
            "otp_action_user" => "Jika Anda tidak merasa melakukan permintaan ini, harap abaikan email ini.",
            "otp_message" => "Anda mengajukan verifikasi email, berikut Kode OTP untuk verifikasi email Anda :"
            ]);
    }
}
