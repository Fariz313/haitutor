<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Answer;
use App\FileAsk;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AnswerController extends Controller
{
    public function index(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = Answer::where(function ($where) use ($query){
                $where->where('text','LIKE','%'.$query.'%');
                })->paginate(10);    
            }else{
                $data = Answer::paginate(10);
            }
            
            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ],501);
        }
    }


    public function create()
    {
        //
    }


    public function store(Request $request, $ask_id)
    {
        try{
            $user               = JWTAuth::parseToken()->authenticate();
    		$validator          = Validator::make($request->all(), [
    			'text'          => 'required|max:2000',
				'file'	        => 'file',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = new Answer();
            $data->text             = $request->input('text');
            $data->ask_id           = $ask_id;
            $data->user_id          = $user->id;
            if($request->hasFile('file')){     
                try {
                    $dataFile       = new FileAsk();
                    $file           = $request->file('file');
                    $tujuan_upload  = 'temp/answer';
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
                    $dataFile->ask_type     =   'answer';
                    $dataFile->save();
                    return response()->json([
                        'status'	=> 'success',
                        'message'	=> 'Ask added with image successfully'
                    ], 201);
                } catch (\Throwable $th) {
                    return response()->json([
                        'status'	=> 'failed',
                        'message'	=> 'failed adding ask with image'
                    ], 501);
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
                'message' => 'cant add Answer'
            ],500);
        }
    }
}
