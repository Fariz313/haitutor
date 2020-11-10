<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Faq;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class FaqController extends Controller {
    
    public function getAll(Request $request){
    
         try{
           
            if($request->get('search')){
                $query = $request->get('search');
                $data = Faq::where(function ($where) use ($query){
                    $where->where('pertanyaan','LIKE','%'.$query.'%')
                        ->orWhere('jawaban','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = Faq::paginate(10);
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
            $data   =   Faq::findOrFail($id);  
            
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
    			'pertanyaan'   => 'required|string',
				'jawaban'	    => 'required|string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                  = new Faq();
            $data->pertanyaan      = $request->input('pertanyaan');
            $data->jawaban         = $request->input('jawaban');
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Faq added successfully',
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
    			'pertanyaan'   => 'string',
				'jawabn'	   => 'string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = Faq::findOrFail($id);
            if ($request->input('pertanyaa')) {
                $data->pertanyaan   = $request->input('pertanyaan');
            }
            if ($request->input('jawaban')) {
                $data->jawaban        = $request->input('jawaban');
            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Faq updated successfully',
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

            $delete = Faq::findOrFail($id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Faq deleted successfully"
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