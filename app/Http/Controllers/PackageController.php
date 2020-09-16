<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Package;
use Illuminate\Support\Facades\Validator;
use JWTAuth;


class PackageController extends Controller
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
                $data = Package::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('price','LIKE','%'.$query.'%')
                        ->orWhere('balance','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = Package::paginate(10);
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
    			'name'          => 'required|string|max:255|unique:company',
				'price'	        => 'required|integer',
				'balance'		=> 'required|integer',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = new Package();
            $data->name         = $request->input('name');
            $data->price        = $request->input('price');
            $data->balance      = $request->input('balance');
            $data->user_id      = JWTAuth::parseToken()->authenticate()->id;
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Package added successfully'
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
            $data   =   Package::findOrFail($id);  
            
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        
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
    			'name'          => 'required|string|max:255|unique:company',
				'price'	        => 'required|integer',
				'balance'		=> 'required|integer',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = Package::findOrFail($id);
            if ($request->input('name')) {
                $data->name         = $request->input('name');
            }
            if ($request->input('price')) {
                $data->price        = $request->input('price');
            }
            if ($request->input('balance')) {
                $data->balance      = $request->input('balance');
            }
            $data->user_id      = JWTAuth::parseToken()->authenticate()->id;
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Package added successfully'
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

            $delete = Package::where("id", $id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Company deleted successfully"
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
