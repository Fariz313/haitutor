<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TutorSubject;
use App\Subject;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;


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
                $data = TutorSubject::select('users.name AS tutor_name','subject.name AS subject_name')
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

    public function getSubjectTutor($tutor_id)
    {
        $paginate = 10;

        try {
            $data = TutorSubject::select('tutor_subject.*', 'subject.*', 'tutor_subject.id as tutor_subject_id')
                                ->join('users','users.id','=','tutor_subject.user_id')
                                ->join('subject','subject.id','=','tutor_subject.subject_id')
                                ->where('users.id', '=', $tutor_id)
                                ->orderBy("priority", "ASC")
                                ->paginate($paginate);

            return $data;
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  $th->getMessage(),
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

            $last_priority_number = TutorSubject::where("user_id", $user->id)->max("priority");

            $data               = new TutorSubject();
            $data->user_id      = $user->id;
            $data->subject_id   = $request->input('subject_id');
            $data->priority     = $last_priority_number + 1;
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Subject added successfully'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ],500);
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

            DB::beginTransaction();

            $tutor_subject = TutorSubject::where("id", $id)->firstOrFail(); // Ambil tutor_subject yang akan dihapus

            $data = TutorSubject::where("user_id", $tutor_subject->user_id)->get();

            $delete_tutor_subject = $tutor_subject->delete();

            if ($delete_tutor_subject) {
                foreach ($data as $item) {
                    /*
                        Jika priority item tutor_subject lebih dari priority tutor_subject yang akan didelete, ubah priority item tutor_subject
                    */
                    if ($item->priority > $tutor_subject->priority) {
                        $item->priority = $item->priority - 1;
                        $item->save();
                    }
                }

                DB::commit();

                return response([
                    "status"	=> "success",
                    "message"   => "Subject deleted successfully"
                ], 200);
            }

        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollback();
            return response([
            	"status"	=> "failed",
                "message"   => "Tutor subject not found"
            ], 500);
        } catch(\Exception $e){
            DB::rollback();
            return response([
            	"status"	=> "failed",
                "message"   => "Failed deleting",
                "data"      => $e->getMessage()
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
    			'tutor_subjects'          => 'required',
    		]);

            if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed',
                    'message'     =>$validator->errors()
                ],400);
    		}

            DB::beginTransaction();

            $tutor_subjects = $request->input("tutor_subjects");

            foreach ($tutor_subjects as $item) {
                $item_tutor_subjects = TutorSubject::findOrFail($item["tutor_subject_id"]);
                $item_tutor_subjects->priority = $item["priority"];
                $item_tutor_subjects->save();
            }

            DB::commit();

            return response([
                "status"	=> "success",
                "message"   => "Subject reordered"
            ], 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return response([
            	"status"	=> "failed",
                "message"   => "Failed reordering",
                "data"      => $th->getMessage()
            ], 500);
        }
    }
}
