<?php

namespace App\Http\Controllers;

use App\User;
use App\TutorDetail;
use App\Notification;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Illuminate\Support\Str;
use FCM;

class TutorController extends Controller
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
                $data = User::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('email','LIKE','%'.$query.'%')
                        ->orWhere('address','LIKE','%'.$query.'%');
                } )->paginate(10);
            }else{
                $data = User::paginate(10);
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
    public function getTutor(Request $request){
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        if($request->get('search')){
            $querySearch    = $request->get('search');
            $data           = User::where('role', Role::ROLE["TUTOR"])
                            ->with(array('detail'=>function($query)
                                        {$query->where('status','verified');},
                                        'tutorSubject'=>function($query)
                                        {$query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');},
                                        'rating'=>function($query){$query->selectRaw('target_id,AVG(rate) average')
                                            ->groupBy('target_id');},))
                            ->where(function ($where) use ($querySearch){
                                $where->where('name','LIKE','%'.$querySearch.'%');
                            })->paginate($paginate);
            return $data;
        }
        $data   =   User::whereHas('detail', function ($q){
                                    $q->where('status','verified');})
                          ->where('role', Role::ROLE["TUTOR"])
                          ->with(array('detail','tutorSubject'=>function($query)
                          {$query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');},
                          'rating'=>function($query){$query->selectRaw('target_id,AVG(rate) average')
                            ->groupBy('target_id');},))
                          ->paginate($paginate);
        return $data;
    }
    public function showTutor($id){

        try {

            $data   =   User::where('role', Role::ROLE["TUTOR"])
                          ->with(array('detail','tutorSubject'=>function($query) {
                              $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                            }, 'rating','avrating'=>function($query){
                                $query->selectRaw('target_id,AVG(rate) average')->groupBy('target_id');
                            }, "tutorDoc" => function ($query) {

                            }))
                          ->findOrFail($id);
            return $data;

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>'failed',
                'message'   => "failed to get tutor",
                "data"      => $th->getMessage()
            ],400);
        }
    }
    public function getAllTutor(Request $request){
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        if($request->get('search')){
            $querySearch    = $request->get('search');
            $data           = User::where('role', Role::ROLE["TUTOR"])
                                ->where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                ->with(array('detail','tutorSubject'=>function($query){
                                    $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                                },'rating'=>function($query){
                                    $query->selectRaw('target_id,AVG(rate) average')->groupBy('target_id');
                                }))
                                ->where(function ($where) use ($querySearch){
                                    $where->where('name','LIKE','%'.$querySearch.'%');
                                })->paginate($paginate);
            return $data;
        }
        $data   =   User::where('role', Role::ROLE["TUTOR"])
                            ->where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                            ->with(array('detail','tutorSubject'=>function($query){
                                $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                            }, 'rating'=>function($query){
                                $query->selectRaw('target_id,AVG(rate) average')->groupBy('target_id');
                            }))
                            ->paginate($paginate);
        return $data;
    }

    public function getRecommendedTutorList(Request $request){

        $paginate = 10;

        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

        $data   =   User::where('role', Role::ROLE["TUTOR"])
                            ->whereHas("detail", function ($query) {
                                $query->where("status", "verified");
                            })
                            ->where("isRestricted", User::IS_RESTRICTED["FALSE"])
                            ->with(array("detail", "tutorSubject" => function ($query) {
                                $query->leftJoin("subject", "subject.id", "=", "tutor_subject.subject_id");
                            }))
                            ->orderBy("total_rating", "DESC")
                            ->withCount(array("report"))
                            ->orderBy("report_count", "ASC")
                            ->paginate($paginate);
        return $data;
    }

    public function getUnverifiedTutor(Request $request){
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        if($request->get('search')){
            $querySearch    = $request->get('search');
            $data           = User::where('role', Role::ROLE["TUTOR"])
                            ->with(array('detail'=>function($query)
                                        {$query->where('status','unverified');},
                                        'tutorSubject','rating'=>function($query){$query->selectRaw('target_id,AVG(rate) average')
                                            ->groupBy('target_id');},))
                            ->where(function ($where) use ($querySearch){
                                $where->where('name','LIKE','%'.$querySearch.'%');
                            })->paginate($paginate);
            return $data;
        }
        $data   =   User::whereHas('detail', function ($q){
                                    $q->where('status','unverified');})
                          ->where('role', Role::ROLE["TUTOR"])
                          ->with(array('detail','tutorSubject','rating'=>function($query){$query->selectRaw('target_id,AVG(rate) average')
                            ->groupBy('target_id');}))
                          ->paginate($paginate);
        return $data;
    }

    public function registerTutor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'birth_date' => 'required|date',
            'photo' => 'file',
            'contact' => 'required|string|max:20',
            'company_id' => 'integer|max:20',
            'address' => 'required|string',
            'biography' => 'required|string',

        ]);

        if($validator->fails()){
            // return response()->json($validator->errors()->toJson(), 400);
            return response()->json([
                'status'    =>'failed',
                'error'     =>$validator->errors()
            ],400);
        }
        $message = "Upload";
        try {
            $user = User::create([
                'name'          => $request->get('name'),
                'email'         => $request->get('email'),
                'password'      => Hash::make($request->get('password')),
                'birth_date'    => $request->get('birth_date'),
                'role'          => Role::ROLE["TUTOR"],
                'contact'       => $request->get('contact'),
                'company_id'    => $request->get('company_id'),
                'address'       => $request->get('address'),
            ]);
            try{
                $photo = $request->file('photo');
                $tujuan_upload = 'temp';
                $photo_name = $user->id.'_'.$photo->getClientOriginalName().'_'.Str::random(3).'.'.$photo->getClientOriginalExtension();
                $photo->move($tujuan_upload,$photo_name);
                $user->photo = $photo_name;
                $detail= new TutorDetail();
                $detail->user_id = $user->id;
                if ($request->input('biography')) {
                    $detail->biography = $request->input('biography');
                }$detail->save();
                $user->save();
                    $message = "Upload Success";
            }catch(\throwable $e){
                    $message = "Upload Success no image";
            }
            $token = JWTAuth::fromUser($user);
        } catch (\Throwable $th) {
            $user       = 'no user';
            $token      = 'no token';
            $message    = 'Failed To Create User';
            return response()->json(compact('user','token','message'),500);
        }
        $detail= new TutorDetail();
        $detail->user_id = $user->id;
        if ($request->input('biography')) {
            $detail->biography = $request->input('biography');
        }$detail->save();



        return response()->json(compact('user','token','message'),201);
    }

    public function verifyTutor($id)
    {
        try {
            $tutor          = TutorDetail::where('user_id', '=', $id)->firstOrFail();
            $tutor->status  = TutorDetail::TutorStatus["VERIFIED"];
            $tutor->save();

            $userTutor      = User::findOrFail($id);

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => "Pengajuan verifikasi akun Anda disetujui. Akun Anda telah terverifikasi",
                "sender_id" => JWTAuth::parseToken()->authenticate()->id,
                "target_id" => $id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[8],
                'token_recipient' => $userTutor->firebase_token,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Success verify tutor',
                'data'      =>  $tutor
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'Failed to verify tutor',
                'data'      =>  $th->getMessage()
            ]);
        }
    }

    public function unverifyTutor($id)
    {
        try {
            $tutor          = TutorDetail::where('user_id', '=', $id)->firstOrFail();
            $tutor->status  = TutorDetail::TutorStatus["UNVERIFIED"];
            $tutor->save();

            $userTutor      = User::findOrFail($id);

            $dataNotif = [
                "title" => "HaiTutor",
                "message" => "Pengajuan verifikasi akun Anda ditolak. Silahkan melakukan pengajuan ulang",
                "sender_id" => JWTAuth::parseToken()->authenticate()->id,
                "target_id" => $id,
                "channel_name"   => Notification::CHANNEL_NOTIF_NAMES[8],
                'token_recipient' => $userTutor->firebase_token,
                'save_data' => true
            ];
            $responseNotif = FCM::pushNotification($dataNotif);

            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Success unverify tutor',
                'data'      =>  $tutor
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'Failed to unverify tutor',
                'data'      =>  $th->getMessage()
            ]);
        }
    }

    public function getTutorBySubject(Request $request, $subject_id)
    {
        $paginate = 10;

        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = User::select('users.*')
                        ->leftJoin('tutor_subject','users.id','=','tutor_subject.user_id')
                        ->where('status','verified')
                        ->where('role', Role::ROLE["TUTOR"])
                        ->where('name','LIKE','%'.$query.'%')
                        ->where('tutor_subject.subject_id',$subject_id)
                        ->with(array('detail'))
                        ->with(array('tutorSubject'=> function($query) use ($subject_id){
                            $query->where('subject_id', '=', $subject_id);
                        }))
                        ->paginate($paginate);
            } else {
                $data = User::select('users.*')
                        ->leftJoin('tutor_subject','users.id','=','tutor_subject.user_id')
                        ->where('status','verified')
                        ->where('role', Role::ROLE["TUTOR"])
                        ->where('tutor_subject.subject_id',$subject_id)
                        ->with(array('detail'))
                        ->with(array('tutorSubject'=> function($query) use ($subject_id){
                            $query->where('subject_id', '=', $subject_id);
                        }))
                        ->paginate($paginate);
            }

            return $data;
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  $th
            ]);
        }
    }

    public function updateDisbursementDoc(Request $request, $userId){
        try {
            $tutor              = TutorDetail::where('user_id', '=', $userId)->firstOrFail();
            $tutor->nik         = $request->input('nik');
            $tutor->no_rekening = $request->input('no_rekening');
            $tutor->save();

            return response()->json([
                'status'    =>  'Success',
                'message'   =>  'Tutor Disbursement Info Updated',
                'data'      =>  $tutor
            ]);
        } catch(\Exception $e){
            return response()->json([
                'status'    =>  'Failed',
                'message'   =>  'Tutor Disbursement Info Failed to Update',
                'data'      =>  $e->getMessage()
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
        //
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
}
