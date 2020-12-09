<?php

namespace App\Http\Controllers;

use App\Ebook;
use App\User;
use App\Helpers\CloudKilatHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class EbookController extends Controller
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
                $data   = Ebook::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->orWhere('slug','LIKE','%'.$query.'%');
                })->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->paginate(10);
            } else {
                $data = Ebook::where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->paginate(10);
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

            $publisher  = User::findOrFail($request->input('id_publisher'));
            if($publisher->role == User::ROLE["PUBLISHER"]){

                $validator = Validator::make($request->all(), [
                    'id_category'   => 'required|string',
                    'id_publisher'  => 'required|string',
                    'name'          => 'required|string',
                    'slug'          => 'required|string',
                    'type'          => 'required|string',
                    'price'         => 'required|string',
                    'content_file'  => 'required|file'
                ]);

                if($validator->fails()){
                    return response()->json([
                        'status'    => 'Failed',
                        'error'     => $validator->errors()
                    ],400);
                }

                $data                   = new Ebook();
                $data->id_category      = $request->input('id_category');
                $data->id_publisher     = $request->input('id_publisher');
                $data->name             = $request->input('name');
                $data->slug             = $request->input('slug');
                $data->type             = $request->input('type');
                $data->price            = $request->input('price');
                $data->description      = $request->input('description');
                $data->content_file     = CloudKilatHelper::put($request->file('content_file'), '/document/ebook', 'file', Str::random(3));

                if($request->file('front_cover')){
                    $data->front_cover  = CloudKilatHelper::put($request->file('front_cover'), '/photos/ebook', 'image', Str::random(3));
                }

                if($request->file('back_cover')){
                    $data->back_cover   = CloudKilatHelper::put($request->file('back_cover'), '/photos/ebook', 'image', Str::random(3));
                }

                $data->save();

                $data = Ebook::where('id', $data->id)->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))->first();

                return response()->json([
                    'status'    =>  'Success',
                    'data'      =>  $data,
                    'message'   =>  'Insert Ebook Succeeded'
                ], 200);

            } else {
                return response()->json([
                    'status'    =>  'Failed',
                    'message'   =>  'Publisher Invalid'
                ], 500);
            }
            
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
            $data = Ebook::where('id', $id)->with(array('ebookCategory', 'ebookPublisher' => function($query){
                        $query->select('id','name', 'email');
                    }))->first();

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
            $data = Ebook::where('id', $id)->with(array('ebookCategory', 'ebookPublisher' => function($query){
                        $query->select('id','name', 'email');
                    }))->first();

            if($request->input('id_category')){
                $data->id_category  = $request->input('id_category');
            }
            if($request->input('id_publisher')){
                $data->id_publisher = $request->input('id_publisher');
            }
            if($request->input('name')){
                $data->name         = $request->input('name');
            }
            if($request->input('slug')){
                $data->slug         = $request->input('slug');
            }
            if($request->input('type')){
                $data->type         = $request->input('type');
            }
            if($request->input('price')){
                $data->price        = $request->input('price');
            }
            if($request->input('description')){
                $data->description  = $request->input('description');
            }
            if($request->file('content_file')){
                CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/document/ebook'.$data->content_file);
                $data->content_file = CloudKilatHelper::put($request->file('content_file'), '/document/ebook', 'file', Str::random(3));
            }
            if($request->file('front_cover')){
                CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/document/ebook'.$data->front_cover);
                $data->front_cover  = CloudKilatHelper::put($request->file('front_cover'), '/photos/ebook', 'image', Str::random(3));
            }
            if($request->file('back_cover')){
                CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/document/ebook'.$data->back_cover);
                $data->back_cover   = CloudKilatHelper::put($request->file('back_cover'), '/photos/ebook', 'image', Str::random(3));
            }
            
            $data->save();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Ebook Succeeded'
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
            $data           = Ebook::findOrFail($id);
            $message        = 'Ebook Deleted';

            if($data->is_deleted == Ebook::EBOOK_DELETED_STATUS["DELETED"]){
                $message    = 'Ebook Already Deleted';
            } else {
                $data->is_deleted    = Ebook::EBOOK_DELETED_STATUS["DELETED"];
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

    public function getAllFreeEbook(Request $request)
    {
        try {
            if($request->get('search')){
                $query  = $request->get('search');
                $data   = Ebook::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->orWhere('slug','LIKE','%'.$query.'%');
                })
                ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["FREE"])
                ->paginate(10);
            } else {
                $data = Ebook::with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["FREE"])
                ->paginate(10);
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

    public function getAllPaidEbook(Request $request)
    {
        try {
            if($request->get('search')){
                $query  = $request->get('search');
                $data   = Ebook::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->orWhere('slug','LIKE','%'.$query.'%');
                })
                ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["PAID"])
                ->paginate(10);
            } else {
                $data = Ebook::with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["PAID"])
                ->paginate(10);
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
}
