<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;


class OrderController extends Controller
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
                $data = Order::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%');
                } )->paginate(10);    
            }else{
                $data = Order::paginate(10);
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $package_id)
    {
        try{
            $validator = Validator::make($request->all(), [
    			'proof'          => 'required|file',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = new Order();
            $data->user_id      = JWTAuth::parseToken()->authenticate()->id;
            $data->package_id   = $package_id;
            $data->invoice      = Str::random(16);
            try{
                $photo = $request->file('proof');
                $tujuan_upload = 'temp/proof';
                $photo_name = $data->id.'__'.Str::random(3).$photo->getClientOriginalName();
                $photo->move($tujuan_upload,$photo_name);
                $data->proof = $photo_name;
                $data->save();
            }catch(\throwable $e){
                    return "Tidak ada Bukti";
            }

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Order added successfully'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function verify($id)
    {
        try{
    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data               = Order::findOrFail($id);
            $data->status       = JWTAuth::parseToken()->authenticate()->id;
            $user               = User::findOrFail($data->user_id);
            $user->balance      = $user->balance + $data->balance;
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Order successfully verify'
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
