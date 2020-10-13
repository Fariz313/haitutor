<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Rating;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class RatingController extends Controller
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
                $data = Rating::where(function ($where) use ($query){
                    $where->where('coment','LIKE','%'.$query.'%');
                })->paginate(10);    
            }else{
                $data = Rating::paginate(10);
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
    public function store(Request $request,$id)
    {
        // try{
    		$validator = Validator::make($request->all(), [
    			'comment'          => 'required|string',
				'rate'	=> 'required|integer|max:5',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                  = new Rating();
            return JWTAuth::parseToken()->authenticate();
            $data->user_id         = JWTAuth::parseToken()->authenticate()->id;
            $data->tutor_id        = $id;
            $data->comment         = $request->input('comment');
            $data->rate            = $request->input('rate');
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Rating added successfully'
    		], 201);

        // } catch(\Exception $e){
        //     return response()->json([
        //         'status' => 'failed',
        //         'message' => $e->getMessage()
        //     ]);
        // }
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
            $data = Rating::where('id',$id)->first();
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
