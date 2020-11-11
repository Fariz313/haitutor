<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Information;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class InformationController extends Controller {
    
    public function getAll(Request $request){
    
         try{
           
            if($request->get('search')){
                $query = $request->get('search');
                $data = Information::where(function ($where) use ($query){
                    $where->where('variable','LIKE','%'.$query.'%')
                        ->orWhere('value','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = Information::paginate(10);
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
            $data   =   Information::findOrFail($id);  
            
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
    			'variable'   => 'required|string',
				'value'	    => 'required|string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                  = new Information();
            $data->variable        = $request->input('variable');
            $data->value           = $request->input('value');
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Information added successfully',
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
    			'variable'   => 'string',
				'value'	    => 'string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = Information::findOrFail($id);
            if ($request->input('variable')) {
                $data->variable         = $request->input('variable');
            }
            if ($request->input('value')) {
                $data->value        = $request->input('value');
            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Information updated successfully',
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

            $delete = Information::findOrFail($id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Information deleted successfully"
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