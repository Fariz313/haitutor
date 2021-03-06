<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Report;
use App\ReportIssue;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ReportController extends Controller
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
                $data = Report::select("report.*")
                                ->where("sender_table.name", "LIKE", "%".$query."%")
                                ->join("users as sender_table", "sender_table.id", "=", "report.sender_id")
                                ->with(array("reportIssue" => function ($query) {

                                }))
                                ->with(array("sender" => function ($query) {
                                    $query->select("id", 'name', 'email', 'role');
                                }))
                                ->with(array('target' => function ($query) {
                                    $query->select("id", 'name', 'email', 'role');
                                }));
            }else{
                $data = Report::with(array('reportIssue' => function ($query) {

                }))
                ->with(array('sender' => function ($query) {
                    $query->select("id", 'name', 'email', 'role');
                }))
                ->with(array('target' => function ($query) {
                    $query->select("id", 'name', 'email', 'role');
                }));
            }
            if($request->get('filter-time')){
                try {
                    $data->orderBy('created_at',$request->get('filter-time'));
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data->paginate(10),
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
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
            $data               = new Report();
            $data->sender_id    = JWTAuth::parseToken()->authenticate()->id;
            $data->target_id    = $request->input('target_id');
            $data->issue_id     = $request->input('issue_id');
            $data->information  = $request->input('information');
            $data->read         = Report::ReportStatus["NEW"];
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
    			'message'	=> 'Report added successfully'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ], 500);
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
            $data = Report::where('id',$id)
            ->with(array('reportIssue', 'sender' => function ($query) {
                $query->select("id", 'name', 'email', 'role');
            }))
            ->with(array('target' => function ($query) {
                $query->select("id", 'name', 'email', 'role');
            }
            ))->first();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
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
        try {
            $data = Report::where('id',$id)->first();
            $data->delete();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Delete Report Data Succeeded'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Delete Report Data Failed'
            ]);
        }
    }

    public function getReportIssue(Request $request)
    {
        try {
            if($request->get('search')){
                $query = $request->get('search');
                $data = ReportIssue::where(function ($where) use ($query){
                    $where->where('issue','LIKE','%'.$query.'%')
                          ->where("deleted", "0");
                })->paginate(10);
            } else {
                $data = ReportIssue::where("deleted", "0")->paginate(10);
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }

    public function getDetailReportIssue($id)
    {
        try {
            $data = ReportIssue::where('id',$id)->first();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }

    public function insertReportIssue(Request $request)
    {
        try{
            $data           = new ReportIssue();
            $data->issue    = $request->input('issue');
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
    			'message'	=> 'Report Issue added successfully'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateReportIssue(Request $request, $id)
    {
        try{
            $data           = ReportIssue::findOrFail($id);
            $data->issue    = $request->input('issue');
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
    			'message'	=> 'Report Issue updated'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteReportIssue(Request $request, $id)
    {
        try{
            $data           = ReportIssue::findOrFail($id);
            $data->deleted  = ReportIssue::DELETE_STATUS["TRUE"];
	        $data->save();

    		return response()->json([
    			'status'	=> 'Success',
    			'message'	=> 'Report Issue updated'
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
