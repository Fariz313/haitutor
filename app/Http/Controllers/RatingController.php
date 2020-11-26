<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Rating;
use App\User;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use DB;

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

                $data = Rating::where("users.name", "LIKE", "%".$query."%")
                        ->join("users", "users.id", "=", "rating.target_id")
                        ->groupBy('target_id')
                        ->selectRaw("target_id,AVG(rate) average")
                        ->with(array("target" => function ($query) {
                            $query->select("id", "email", "name", "role");
                        }))
                        ->paginate(10);
            }else{
                $data = Rating::selectRaw('target_id,AVG(rate) average')
                ->with(array("target" => function ($query) {
                    $query->select("id", "email", "name", "role");
                }))
                ->groupBy('target_id')
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
                'data'      =>  $th->getMessage(),
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
        try{
    		$validator = Validator::make($request->all(), [
    			'comment'   => 'required|string',
				'rate'	    => 'required|integer|max:5',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
            }

            $current_user = JWTAuth::parseToken()->authenticate();

            $ratingExist = Rating::where('sender', $current_user->id)
                                 ->where('target', $id)
                                 ->first();

            if ($ratingExist) {
                return response()->json([
                    'status'	=> 'failed',
                    'message'	=> 'Rating alread added'
                ], 409);
            } else {
                $user = User::findOrFail($id);

                DB::beginTransaction();

                $data                  = new Rating();
                $data->user_id         = $current_user->id;
                $data->tutor_id        = $id;
                $data->comment         = $request->input('comment');
                $data->rate            = $request->input('rate');
                $data->save();

                $recount_average_rating = Rating::where("target", $id)->avg('rate');
                $user->total_rating = round($recount_average_rating, 1);
                $user->save();

                DB::commit();

                return response()->json([
                    'status'	=> 'success',
                    'message'	=> 'Rating added successfully'
                ], 200);
            }

        } catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status' => 'failed',
                'message' => 'failed to insert rating',
                'data' => $e->getMessage()
            ]);
        }
    }

    public function check($user_id)
    {
        try {
            $current_user = JWTAuth::parseToken()->authenticate();

            $ratingExist = Rating::where('sender', $current_user->id)
                                 ->where('target', $user_id)
                                 ->first();

            if ($ratingExist) {
                return response()->json([
                    'status'	=> 'success',
                    'message'	=> 'Rating exist for this user'
                ], 200);
            } else {
                return response()->json([
                    'status'	=> 'failed',
                    'message'	=> 'Rating not exist for this user'
                ], 200);
             }

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'failed',
                'message' => 'failed to check rating',
                'data' => $th->getMessage()
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

    /*
        Show rating list that has been rated by user_id
    */
    public function ratedByUser($user_id)
    {
        try {

            $allRating = Rating::where('sender', $user_id)
                                ->with(array("sender" => function ($query) {
                                    $query->select("id", "email", "name", "role");
                                }))
                                ->with(array("target" => function ($query) {
                                    $query->select("id", "email", "name", "role");
                                }))
                                ->paginate(10);

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $allRating,
                'message'   =>  'Get Data Success'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  $th->getMessage(),
                'message'   =>  'Get Data Failed'
            ]);
        }
    }


    /*
        Show rating list from user_id
    */

    public function userRatingList($user_id)
    {
        try {

            $allRating = Rating::where('target', $user_id)
                                ->with(array("sender" => function ($query) {
                                    $query->select("id", "email", "name", "role");
                                }))
                                ->with(array("target" => function ($query) {
                                    $query->select("id", "email", "name", "role");
                                }))
                                ->paginate(10);

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $allRating,
                'message'   =>  'Get Data Success'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  $th->getMessage(),
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
