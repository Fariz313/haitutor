<?php

namespace App\Http\Controllers;

use App\EbookCategory;
use Illuminate\Http\Request;

class EbookCategoryController extends Controller
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
                $data   = EbookCategory::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->where('slug','LIKE','%'.$query.'%');
                })->where('is_deleted', EbookCategory::EBOOK_CATEGORY_DELETED_STATUS["ACTIVE"])->paginate(10);
            } else {
                $data = EbookCategory::where('is_deleted', EbookCategory::EBOOK_CATEGORY_DELETED_STATUS["ACTIVE"])->paginate(10);
            }
            
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
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
    public function store(Request $request)
    {
        try {

            $data           = new EbookCategory();
            $data->name     = $request->input('name');
            $data->slug     = $request->input('slug');
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Insert Ebook Category Succeeded'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
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
            $data = EbookCategory::findOrFail($id);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
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
        try {
            $data           = EbookCategory::findOrFail($id);

            if($request->input('name')){
                $data->name     = $request->input('name');
            }
            if($request->input('slug')){
                $data->slug     = $request->input('slug');
            }
            
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Ebook Category Succeeded'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
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
        try {
            $data           = EbookCategory::findOrFail($id);
            $message        = 'Ebook Category Deleted';

            if($data->is_deleted == EbookCategory::EBOOK_CATEGORY_DELETED_STATUS["DELETED"]){
                $message    = 'Ebook Category Already Deleted';
            } else {
                $data->is_deleted    = EbookCategory::EBOOK_CATEGORY_DELETED_STATUS["DELETED"];
                $data->save();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  $message
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }
}
