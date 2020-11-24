<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PaymentMethod;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class PaymentMethodController extends Controller {
    
    public function getAll(Request $request){
    
         try{
           
            if($request->get('search')){
                $query = $request->get('search');
                $data = PaymentMethod::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('code','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = PaymentMethod::paginate(10);
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
            $data   =   PaymentMethod::findOrFail($id);  
            
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
    			'name'   => 'required|string',
				'code'	    => 'required|string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data          = new PaymentMethod();
            $data->name    = $request->input('name');
            $data->code    = $request->input('code');
            $data->status  = '0';   
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Payment Method added successfully',
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
    			'name'   => 'string',
				'code'	    => 'string',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = PaymentMethod::findOrFail($id);
            if ($request->input('name')) {
                $data->name = $request->input('name');
            }
            if ($request->input('code')) {
                $data->code = $request->input('code');
            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Payment Method updated successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $data = PaymentMethod::findOrFail($id);
            if ($data->status == '0') {
                $data->status = '1';
            }
            else{
                $data->status = '0';
            }
            $data->save();
            
    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Status Payment Method Updated Successfully',
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

            $delete = PaymentMethod::findOrFail($id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Payment Method deleted successfully"
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