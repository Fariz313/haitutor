<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Company;
use Illuminate\Support\Facades\Validator;


class CompanyController extends Controller
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
                $data = Company::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                        ->orWhere('company_type','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = Company::paginate(10);
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
				'company_type'	=> 'required|string|max:255',
				'email'			=> 'string|max:255',
				'address'       => 'string|max:255',
				'contact'       => 'string|max:255',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = new Company();
            $data->name         = $request->input('name');
            $data->email        = $request->input('email');
            $data->company_type = $request->input('company_type');
            $data->address      = $request->input('address');
            $data->contact      = $request->input('contact');
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
        try {
            $data = Company::where('id',$id)->first();
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
    			'name'          => 'string|max:255|unique:company',
				'company_type'	=> 'string|max:255',
				'email'			=> 'string|max:255',
				'address'       => 'string|max:255',
				'contact'       => 'string|max:255',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = Company::where('id',$id)->first();
            if($request->input('name')){
                $data->name         = $request->input('name');
            }if($request->input('email')){
                $data->email        = $request->input('email');
            }if($request->input('company_type')){
                $data->company_type = $request->input('company_type');
            }if($request->input('address')){
                $data->address      = $request->input('address');
            }if($request->input('contact')){
                $data->contact      = $request->input('contact');
            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Company updated successfully'
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

            $delete = Company::where("id", $id)->delete();

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
