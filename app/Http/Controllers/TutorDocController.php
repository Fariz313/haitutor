<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TutorDoc;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class TutorDocController extends Controller
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
                $data = TutorDoc::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('type','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = TutorDoc::paginate(10);
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
            $user = JWTAuth::parseToken()->authenticate();
    		$validator = Validator::make($request->all(), [
    			'name'          => 'required|string|max:255',
                'file'			=> 'file',
                'type'          => 'in:ijazah,skhu,certificare,other'
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = new TutorDoc();
            $data->name         = $request->input('name');
            $data->tutor_id     = $user->id;
            
            $file = $request->file('file');
            $tujuan_upload = 'tempdoc';
            $file_name = $user->id.'_'.$file->getClientOriginalName().'_'.Str::random(3).'.'.$file->getClientOriginalExtension();
            $file->move($tujuan_upload,$file_name);
            $data->file = $file_name;

	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Company added successfully'
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
        try{

            $delete = TutorDoc::where("id", $id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Document deleted successfully"
              ]);
            } else {
              return response([
                "status"  => "failed",
                  "message"   => "Failed delete document"
              ]);
            }
        } catch(\Exception $e){
            return response([
            	"status"	=> "failed",
                "message"   => $e->getMessage()
            ]);
        }
    }

    public function verifyingDoc($id)
    {
        try {
            $data           = TutorDoc::findOrFail($id);
            $data->status   = 'verified';
            $data->save()   ;
            return response()->json([
                "status"    =>   'success',
                "message"   =>   'Document Verified'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status"    =>   'failed',
                "message"   =>   'Document Not Verified'
            ]);
        }
    }

    public function unverifyingDoc($id)
    {
        try {
            $data           = TutorDoc::findOrFail($id);
            $data->status   = 'unverified';
            $data->save()   ;
            return response()->json([
                "status"    =>   'success',
                "message"   =>   'Document Unverified'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "status"    =>   'failed',
                "message"   =>   'Document Not Unverified'
            ]);
        }
    }

    
}
