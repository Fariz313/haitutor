<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Subject;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CloudKilatHelper;
use App\Helpers\GoogleCloudStorageHelper;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $paginate = 10;
            if($request->get('paginate')){
                $paginate = $request->get('paginate');
            }
            if($request->get('search')){
                $query = $request->get('search');
                $data = Subject::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('type','LIKE','%'.$query.'%');
                } )->paginate($paginate);
            }else{
                $data = Subject::paginate($paginate);
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

    public function getSubject(Request $request){
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }
        if($request->get('search')){
            $query = $request->get('search');
            $data = Subject::where(function ($where) use ($query){
                $where->where('name','LIKE','%'.$query.'%')
                    ->orWhere('type','LIKE','%'.$query.'%');
            } )->paginate($paginate);
            return $data;
        } else {
            $data = Subject::paginate($paginate);
            return $data;
        }
    }

    public function getUnassignedSubject($tutor_id){
        try {
            $subjectModel = new Subject();
            $data = $subjectModel->getUnassignedSubject($tutor_id);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  "Get Data Succeeded"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  $th
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
                'name'          => 'required|string|max:255|unique:subject',
                'icon'         => 'required|file|',
				'type'	        => 'required|in:general,vocation',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            // $icon_path = CloudKilatHelper::put($request->file('icon'), '/photos/subject', 'image', Str::random(3));
            $icon_path = GoogleCloudStorageHelper::put($request->file('icon'), '/photos/subject', 'image', Str::random(3));


            $data               = new Subject();
            $data->name         = $request->input('name');
            $data->type        = $request->input('type');
            $data->icon_path   = $icon_path;
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
            $data = Subject::findOrFail($id);
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
    			'name'          => 'string|max:255|unique:subject',
				'type'	        => 'in:general,vocation',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = Subject::findOrFail($id);
            if($request->input('name')){
                $data->name         = $request->input('name');
            }if($request->input('type')){
                $data->type        = $request->input('type');
            }

	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Subject updated'
    		], 200);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to update subject',
                'data'  => $e->getMessage()
            ], 400);
        }
    }

    public function updateIcon(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'icon'          => 'required|file|dimensions:max_width=512,max_height=512|max:128'
            ]);

            if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = Subject::findOrFail($id);

            // CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/photos/subject'.$data->icon_path);
            GoogleCloudStorageHelper::delete('/photos/subject'.$data->icon_path);
            $icon_path = GoogleCloudStorageHelper::put($request->file('icon'), '/photos/subject', 'image', Str::random(3));

            $data->icon_path = $icon_path;

            $data->save();

            return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Subject icon updated'
    		], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to update subject icon',
                'data'  => $e->getMessage()
            ], 400);
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

            $subject = Subject::findOrFail($id);
            // CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/photos/subject'.$subject->icon_path);
            GoogleCloudStorageHelper::delete('/photos/subject'.$subject->icon_path);
            $delete = $subject->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Subject deleted"
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
