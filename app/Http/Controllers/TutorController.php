<?php

namespace App\Http\Controllers;

use App\User;
use App\TutorDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
use Illuminate\Support\Str;

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
            $data           = User::where('role','tutor')
                            ->with(array('detail'=>function($query)
                                        {$query->where('status','verified');},
                                        'tutorSubject'=>function($query)
                                        {$query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');}))
                            ->where(function ($where) use ($querySearch){
                                $where->where('name','LIKE','%'.$querySearch.'%');
                            })->paginate($paginate);
            return $data;
        }
        $data   =   User::whereHas('detail', function ($q){
                                    $q->where('status','verified');})
                          ->where('role','tutor')
                          ->with(array('detail','tutorSubject'=>function($query)
                          {$query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');}))
                          ->paginate($paginate);
        return $data;
    }
    public function getAllTutor(Request $request){
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        if($request->get('search')){
            $querySearch    =$request->get('search');
            $data           =User::where('role','tutor')
                            ->with(array('detail','tutorSubject'=>function($query)
                            {$query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');}))
                            ->where(function ($where) use ($querySearch){
                                $where->where('name','LIKE','%'.$querySearch.'%');
                            })->paginate($paginate);
            return $data;
        }
        $data   =   User::where('role','tutor')
                          ->with(array('detail','tutorSubject'=>function($query)
                          {$query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');}))
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
            $data           = User::where('role','tutor')
                            ->with(array('detail'=>function($query)
                                        {$query->where('status','unverified');},
                                        'tutorSubject'))
                            ->where(function ($where) use ($querySearch){
                                $where->where('name','LIKE','%'.$querySearch.'%');
                            })->paginate($paginate);
            return $data;
        }
        $data   =   User::whereHas('detail', function ($q){
                                    $q->where('status','verified');})
                          ->where('role','tutor')
                          ->with('detail','tutorSubject')
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
                'role'          => "tutor",
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
            $tutor          = User::where('role','tutor')->findOrFail($id);
            $tutor->status  = 'verified';
            $tutor->save();
            return $tutor;
        } catch (\Throwable $th) {
            //throw $th;
        }   
    }

    public function unverifyTutor($id)
    {
        try {
            $tutor          = User::where('role','tutor')->findOrFail($id);
            $tutor->status  = 'unverified';
            $tutor->save();
            return $tutor;
        } catch (\Throwable $th) {
            //throw $th;
        }   
    }

    public function getTutorBySubject($subject_id)
    {
        $paginate = 10;
        
        try {
            $data =  $data = User::select('users.*')->whereHas('detail', function ($q){
                                $q->where('status','verified');
                            })
                            ->leftJoin('tutor_subject','users.id','=','tutor_subject.user_id')
                            ->where('role','tutor')
                            ->where('tutor_subject.subject_id',$subject_id)
                            ->with(array('detail','tutorSubject'=>function($query){
                                $query->leftJoin('subject', 'subject.id', '=', 'tutor_subject.subject_id');
                            }))
                            ->paginate($paginate);  
        
            return $data;
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
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
