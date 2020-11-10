<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AppVersion;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class AppVersionController extends Controller {
    
    public function getAll(Request $request){
    
         try{
           
            if($request->get('search')){
                $query = $request->get('search');
                $data = AppVersion::where(function ($where) use ($query){
                    $where->where('versionCode','LIKE','%'.$query.'%')
                        ->orWhere('versionName','LIKE','%'.$query.'%')
                        ->orWhere('type','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = AppVersion::paginate(10);
            }
            
            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        
         }catch(\Throwable $e){
             
              return response()->json([
               "status"=>"gagal",
               "error"=>$e
               ],500);
         }       
    }

    public function getOne($id)
    {
        try {
            $data   =   AppVersion::findOrFail($id);  
            
            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Get Data Success',
                'data'      =>  $data
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }

    public function store(Request $request)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'versionCode'   => 'required|string',
				'versionName'	=> 'required|string',
				'type'	        => 'required|string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                  = new AppVersion();
            $data->versionCode     = $request->input('versionCode');
            $data->versionName     = $request->input('versionName');
            $data->type            = $request->input('type');
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Version added successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'versionName'   => 'string',
				'versionCode'	=> 'string',
				'type'	        => 'string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = AppVersion::findOrFail($id);
            if ($request->input('versionCode')) {
                $data->versionCode         = $request->input('versionCode');
            }
            if ($request->input('versionName')) {
                $data->versionName        = $request->input('versionName');
            }
            if ($request->input('type')) {
                $data->type        = $request->input('type');
            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Version updated successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function destroy($id)
    {
        try{

            $delete = AppVersion::findOrFail($id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Version deleted successfully"
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
                "message"   => $e->getMessage()
            ]);
        }
    }
    
    
}