<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ask;
use App\FileAsk;
use App\Faq;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = Ask::where(function ($where) use ($query){
                $where->where('text','LIKE','%'.$query.'%');
                })->paginate(10);
            }else{
                $data = Ask::paginate(10);
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

    public function getAllFAQ(){
        try{
            $data = Faq::all();
           return response()->json([
             'status'    =>  'success',
             'data'      =>  $data,
             'message'   =>  'Get Data Success'
        ]);
        }catch(\Throwable $th){
              return response()->json([
               'status'    =>  'failed',
               'data'      =>  [],
               'message'   =>  'Get Data Failed'
           ]);
        }

    }

    public function getMyAsk()
    {
        $data   =   Ask::with('fileAsk')->with('answer.fileAsk')->find(17);
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $tutor_id)
    {
        try{
            $user               = JWTAuth::parseToken()->authenticate();
    		$validator = Validator::make($request->all(), [
    			'text'          => 'required|max:2000',
				'file'	        => 'file',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = new Ask();
            $data->text             = $request->input('text');
            $data->tutor_id         = $tutor_id;
            $data->user_id          = $user->id;
            if($request->hasFile('file')){
                try {
                    $dataFile       = new FileAsk();
                    $file           = $request->file('file');
                    $tujuan_upload  = 'temp/ask';
                    $data->save();
                    $file_name      = $user->id.'_'.$file->getClientOriginalName().'_'.Str::random(3).'.'.$file->getClientOriginalExtension();
                    $file->move($tujuan_upload,$file_name);
                    $dataFile->parent_id    =   $data->id;
                    $dataFile->file_url     =   $tujuan_upload.'/'.$file_name;
                    if($file->getClientOriginalExtension()=='png'||$file->getClientOriginalExtension()=='jpg'||$file->getClientOriginalExtension()=="jpeg"){
                        $dataFile->file_type    =   'image';
                    }else{
                        $dataFile->file_type    =   'document';
                    }
                    $dataFile->save();
                    return response()->json([
                        'status'	=> 'success',
                        'message'	=> 'Ask added with image successfully'
                    ], 201);
                } catch (\Throwable $th) {
                    return response()->json([
                        'status'	=> 'failed',
                        'message'	=> 'failed adding ask with image'
                    ], 201);
                }
            }else{
                $data->save();
                return response()->json([
                    'status'	=> 'success',
                    'message'	=> 'Ask added successfully'
                ], 201);

            }


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
        //
    }
}
