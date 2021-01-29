<?php

namespace App\Http\Controllers;

use App\Information;
use Illuminate\Http\Request;
use App\Package;
use Illuminate\Support\Facades\Validator;
use App\Helpers\LogApps;
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
                $query  = $request->get('search');
                $data   = Package::where(function ($where) use ($query){
                            $where->where('name','LIKE','%'.$query.'%')
                                ->orWhere('price','LIKE','%'.$query.'%')
                                ->orWhere('balance','LIKE','%'.$query.'%');
                            })
                            ->where('is_deleted', Package::PACKAGE_DELETED_STATUS["ACTIVE"])
                            ->paginate(10);
            }else{
                $data   = Package::with(['user'])
                        ->where('is_deleted', Package::PACKAGE_DELETED_STATUS["ACTIVE"])
                        ->paginate(10);
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

            $minimumPrice       = Information::where('variable', Information::ATRRIBUTE_NAME["MINIMUM_PACKAGE_PRICE"])->first();

            if($request->input('price') >= (int)$minimumPrice->value){
                $data               = new Package();
                $data->name         = $request->input('name');
                $data->price        = $request->input('price');
                $data->balance      = $request->input('balance');
                $data->user_id      = JWTAuth::parseToken()->authenticate()->id;
                $data->save();

                return response()->json([
                    'status'	=> 'Success',
                    'message'	=> 'Package added successfully',
                    'data'      => $data
                ], 201);
            } else {
                return response()->json([
                    'status'	=> 'Failed',
                    'message'	=> 'Price less than minimum price allowed'
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
        try {
            $data   =   Package::where('id', $id)->with(['user'])->first();

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
            $data                   = Package::findOrFail($id);
            if ($request->input('name')) {
                $data->name         = $request->input('name');
            }
            if ($request->input('balance')) {
                $data->balance      = $request->input('balance');
            }
            if ($request->input('price')) {
                $minimumPrice       = Information::where('variable', Information::ATRRIBUTE_NAME["MINIMUM_PACKAGE_PRICE"])->first();
                if($request->input('price') >= (int)$minimumPrice->value){
                    $data->price        = $request->input('price');
                    $data->save();
                    return response()->json([
                        'status'	=> 'Success',
                        'message'	=> 'Package updated successfully'
                    ], 201);

                } else {
                    return response()->json([
                        'status'	=> 'Failed',
                        'message'	=> 'Price less than minimum price allowed'
                    ], 201);
                }

            } else {
                $data->save();
                return response()->json([
                    'status'	=> 'Success',
                    'message'	=> 'Package updated successfully'
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $package = Package::findOrFail($id);

            if($package->is_deleted == Package::PACKAGE_DELETED_STATUS["DELETED"]){
                return response([
                    "status"	=> "Failed",
                    "message"   => "Package Already Deleted"
                ]);
            } else {

                $package->is_deleted    = Package::PACKAGE_DELETED_STATUS["DELETED"];
                $package->save();

                return response([
                    "status"	=> "Success",
                    "message"   => "Package is Deleted"
                ]);
            }

        } catch(\Exception $e){
            return response([
            	"status"	=> "Failed",
                "message"   => $e->getMessage()
            ]);
        }
    }

    public function recordPackageInterest(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $dataLog = [
                "USER"      => $user,
                "USER_IP"   => $request->ip(),
                "PACKAGE"   => Package::findOrFail($request->input('id_package'))
            ];

            LogApps::packageDetail($dataLog);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $user,
                'message'   =>  'Package Interest Log Recorded'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }
}
