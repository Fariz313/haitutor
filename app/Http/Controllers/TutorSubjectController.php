<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TutorSubject;
use App\Subject;
use JWTAuth;
use Illuminate\Support\Facades\Validator;


class TutorSubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = TutorSubject::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('type','LIKE','%'.$query.'%');
                } )->paginate($paginate);    
            }else{
                $data = TutorSubject::select('users.name','subject.name')
                                    ->join('users','users.id','=','tutor_subject.user_id')
                                    ->join('subject','subject.id','=','tutor_subject.subject_id')
                                    ->paginate($paginate);
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


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'user_id'          => 'required|integer',
				'subject_id'	   => 'required|integer',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}
            $user               = JWTAuth::parseToken()->authenticate();
            $data               = new TutorSubject();
            $data->user_id      = $user->id;
            $data->type         = $request->input('subject_id');
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Subject added successfully'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
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
            $data = TutorSubject::findOrFail($id);
            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed to pick',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
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
        try{
    		$validator = Validator::make($request->all(), [
    			'subject_id'          => 'required|integer',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = TutorSubject::findOrFail($id);
            $data->subject_id   = $request->input('subject_id');
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Subject updated successfully'
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
        try{

            $delete = TutorSubject::where("id", $id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Subject deleted successfully"
              ]);
            } else {
              return response([
                "status"  => "failed",
                  "message"   => "Failed delete data"
              ]);
            }
        } catch(\Exception $e){
            return response([
            	"status"	=> "failed",
                "message"   => "Failed deleting"
            ]);
        }
    }
}
