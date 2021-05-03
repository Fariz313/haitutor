<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\GoogleCloudStorageHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\AR3d;


class aR3dController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if($data = AR3d::count()){
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  AR3d::paginate(10),
                'message'   =>  'Get Data Success'
            ],200);
        }else{
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  $data,
                'message'   =>  'data is empty'
            ],204);
        }
        return response()->json([
            'status'    =>  'Failed',
            'data'      =>  null,
            'message'   =>  'data is empty'
        ],400);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'object_name'           => 'required|string|max:200',
            'object'	            => 'required|file',
            'image'	                => 'required|file|mimes:jpeg,jpg,png',
            'image_name'	        => 'required|string|max:200',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }
        $ext = $request->file('object')->getClientOriginalExtension();
        if($ext !== 'glb' && $ext !== 'gltf' && $ext !== 'obj'){
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>'Format object must be glb, gltf or obj'
            ],400);
        }
        $rand= Str::random(3);
        $ar3d = new AR3d();
        $ar3d->object_name  = $request->input('object_name');
        $ar3d->image_name   = $request->input('image_name');
        $ar3d->object_path       = GoogleCloudStorageHelper::put($request->file('object'), '/ar3d/object', 'file', $rand);
        $ar3d->image_path        = GoogleCloudStorageHelper::put($request->file('image'), '/ar3d/image', 'file', $rand);

        $ar3d->save();
        return response()->json([
            'status'    =>'sucess',
        ],200);

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
            return response()->json([
                'status'    =>  'Success',
                'data'      =>  AR3d::findOrFail($id),
                'message'   =>  'Get Data Success'
            ],200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'Failed',
                'data'      =>  null,
                'message'   =>  'data is empty'
            ],400);
        }

    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'object_name'           => 'string|max:200',
            'object'	            => 'file',
            'image'	                => 'file|mimes:jpeg,jpg,png',
            'image_name'	        => 'string|max:200',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    =>'failed validate',
                'error'     =>$validator->errors()
            ],400);
        }
        if($request->exists('object')){
            $ext = $request->file('object')->getClientOriginalExtension();
            if($ext !== 'glb' && $ext !== 'gltf' && $ext !== 'obj'){
                return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>'Format object must be glb, gltf or obj'
                ],400);
            }
        }
        $rand= Str::random(3);
        $ar3d = AR3d::findOrFail($id);
        if($request->exists('object_name')){
            $ar3d->object_name  = $request->input('object_name');
        }if($request->exists('image_name')){
            $ar3d->image_name   = $request->input('image_name');
        }if($request->exists('image')){
            $ar3d->object_path       = GoogleCloudStorageHelper::put($request->file('object'), '/ar3d/object', 'file', $rand);
            GoogleCloudStorageHelper::delete('/ar3d/object'.$ar3d->object_path);
        }if($request->exists('object')){
            $ar3d->image_path        = GoogleCloudStorageHelper::put($request->file('image'), '/ar3d/image', 'file', $rand);
            GoogleCloudStorageHelper::delete('/ar3d/image'.$ar3d->image_path);
        }

        $ar3d->save();
        return response()->json([
            'status'    =>'sucess',
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ar3d = AR3d::findOrFail($id);
        GoogleCloudStorageHelper::delete('/ar3d/object'.$ar3d->object_path);
        GoogleCloudStorageHelper::delete('/ar3d/image'.$ar3d->image_path);
        $ar3d->delete();
        return response()->json([
            'status'    =>'sucess',
        ],200);
    }
}
