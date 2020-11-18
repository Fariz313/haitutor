<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TutorDoc;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Helpers\CloudKilatHelper;


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
                'type'          => 'in:ijazah,cv,certificate,other'
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = new TutorDoc();
            $data->name         = $request->input('name');
            $data->type         = $request->input('type');
            $data->tutor_id     = $user->id;

            $file = CloudKilatHelper::put($request->file('file'), '/document/tutor', 'file');

            $data->file = $file;

	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
                'message'	=> 'Document added successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
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
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $validator = Validator::make($request->all(), [
    			'name'          => 'required|string|max:255',
                'file'			=> 'file',
                'type'          => 'in:ijazah,cv,certificate,other'
            ]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
            }

            $data               = TutorDoc::findOrFail($id);
            $data->name         = $request->input('name');
            $data->type         = $request->input('type');
            $data->tutor_id     = $user->id;

            CloudKilatHelper::delete($data->file);
            $file = CloudKilatHelper::put($request->file('file'), '/document/tutor', 'file');

            $data->file = $file;

            $data->save();

            return response()->json([
                'status'	=> 'Success',
                'message'	=> 'Document updated successfully',
                'data'      => $data
            ], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
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

            $data = TutorDoc::findOrFail($id);
            CloudKilatHelper::delete($data->file);
            $delete = $data->delete();

            if($delete){
              return response([
                  "status"	=> "Success",
                  "message"   => "Document deleted successfully"
              ]);
            } else {
              return response([
                  "status"  => "Failed",
                  "message"   => "Failed delete document"
              ]);
            }
        } catch(\Exception $e){
            return response([
                "status"	=> "Failed",
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
