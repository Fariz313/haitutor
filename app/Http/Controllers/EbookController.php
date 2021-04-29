<?php

namespace App\Http\Controllers;

use App\Ebook;
use App\EbookLibrary;
use App\User;
use App\Helpers\GoogleCloudStorageHelper;
use App\Role;
use App\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Helpers\LogApps;
use App\Helpers\ResponseHelper;
use JWTAuth;

class EbookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

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
                ->paginate($paginate);
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
            if($publisher->role == Role::ROLE["PUBLISHER"]){

                $validator = Validator::make($request->all(), [
                    'id_category'   => 'required|string',
                    'id_publisher'  => 'required|string',
                    'name'          => 'required|string',
                    'slug'          => 'required|string',
                    'type'          => 'required|string',
                    'price'         => 'required|string',
                    'content_file'  => 'required'
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

                if($request->input('item_code')){
                    $data->item_code    = $request->input('item_code');
                } else {
                    $data->item_code    = Str::random(10);
                }

                $data->name             = $request->input('name');
                $data->slug             = $request->input('slug');
                $data->type             = $request->input('type');
                $data->price            = $request->input('price');
                $data->jenjang          = $request->input('jenjang');
                $data->description      = $request->input('description');

                if($request->input('isbn')){
                    $data->isbn         = $request->input('isbn');
                }

                if($request->input('item_code')){
                    $data->item_code    = $request->input('item_code');
                }

                if($request->input('is_published') != null){
                    $data->is_published = $request->input('is_published');
                }

                $data->content_file     = GoogleCloudStorageHelper::put($request->file('content_file'), '/document/ebook', 'file', Str::random(3));

                if($request->file('front_cover')){
                    $data->front_cover  = GoogleCloudStorageHelper::put($request->file('front_cover'), '/photos/ebook', 'image', Str::random(3));
                }

                if($request->file('back_cover')){
                    $data->back_cover  = GoogleCloudStorageHelper::put($request->file('back_cover'), '/photos/ebook', 'image', Str::random(3));
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

            $user = JWTAuth::parseToken()->authenticate();
            $dataLibrary = EbookLibrary::where('id_user', $user->id)->where('id_ebook', $id)->first();
            if($dataLibrary != null){
                $data->is_in_library    = EbookLibrary::EBOOK_LIBRARY_STATUS["ACTIVE"];
            } else {
                $data->is_in_library    = EbookLibrary::EBOOK_LIBRARY_STATUS["NON_ACTIVE"];
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

            if($request->get('id_category')){
                $data->id_category  = $request->get('id_category');
            }
            if($request->get('id_publisher')){
                $data->id_publisher = $request->get('id_publisher');
            }
            if($request->get('isbn')){
                $data->isbn         = $request->get('isbn');
            }
            if($request->get('item_code')){
                $data->item_code    = $request->get('item_code');
            }
            if($request->get('name')){
                $data->name         = $request->get('name');
            }
            if($request->get('slug')){
                $data->slug         = $request->get('slug');
            }
            if($request->get('type') != null){
                $data->type         = $request->get('type');
            }
            if($request->get('is_published') != null){
                $data->is_published = $request->get('is_published');
            }
            if($request->get('jenjang') != null){
                $data->jenjang      = $request->get('jenjang');
            }
            if($request->get('price')){
                $data->price        = $request->get('price');
            }
            if($request->get('description')){
                $data->description  = $request->get('description');
            }

            $data->save();

            if($request->file('content_file')){
                GoogleCloudStorageHelper::delete('/document/ebook/'.$data->content_file);
                $data->content_file = GoogleCloudStorageHelper::put($request->file('content_file'), '/document/ebook', 'file', Str::random(3));
            }
            if($request->file('front_cover')){
                GoogleCloudStorageHelper::delete('/photos/ebook/'.$data->front_cover);
                $data->front_cover  = GoogleCloudStorageHelper::put($request->file('front_cover'), '/photos/ebook', 'image', Str::random(3));
            }
            if($request->file('back_cover')){
                GoogleCloudStorageHelper::delete('/photos/ebook/'.$data->back_cover);
                $data->back_cover   = GoogleCloudStorageHelper::put($request->file('back_cover'), '/photos/ebook', 'image', Str::random(3));
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

        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

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
                ->paginate($paginate);
            } else {
                $data = Ebook::with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["FREE"])
                ->paginate($paginate);
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

        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

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
                ->paginate($paginate);
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

    public function addEbooksToLibrary(Request $request, $idUser)
    {
        try {
            $newData = array();
            foreach($request->input('id_ebooks') as $idEbook){
                $tempData = EbookLibrary::where('id_user', $idUser)->where('id_ebook', $idEbook)->first();
                if($tempData == null){
                    $data           = new EbookLibrary();
                    $data->id_user  = $idUser;
                    $data->id_ebook = $idEbook;

                    $data->save();
                    array_push($newData, $data);
                }
            }

            if(count($newData) == 0){
                $status     = 'Failed';
                $message    = 'All Ebooks Failed to be Added';
            } else if (count($newData) == count($request->input('id_ebooks'))){
                $status     = 'Success';
                $message    = 'All Ebooks Succeeded to be Added';
            } else {
                $status     = 'Success';
                $message    = 'Some Ebooks Succeeded to be Added';
            }

            return response()->json([
                'status'    =>  $status,
                'data'      =>  $newData,
                'message'   =>  $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getAllEbookInStudentLibrary(Request $request, $idUser)
    {

        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

        try {
            $data = Ebook::select("ebook.*", 'ebook_library.status as library_status')
                        ->join("ebook_library", "ebook.id", "=", "ebook_library.id_ebook")
                        ->where('ebook.is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                        ->where('ebook_library.id_user', $idUser)
                        ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                            $query->select('id','name', 'email');
                        }))
                        ->groupBy('id_ebook')
                        ->orderBy('ebook.type','DESC')
                        ->paginate($paginate);

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

    public function deleteEbooksFromStudentLibrary(Request $request, $idUser)
    {
        try {
            $deletedData = array();
            foreach($request->input('id_ebooks') as $idEbook){
                $tempData = EbookLibrary::where('id_user', $idUser)->where('id_ebook', $idEbook)->first();
                if($tempData != null){
                    array_push($deletedData, $tempData);
                    $tempData->delete();
                }
            }

            if(count($deletedData) == 0){
                $status     = 'Failed';
                $message    = 'All Ebooks Failed to be Deleted';
            } else if (count($deletedData) == count($request->input('id_ebooks'))){
                $status     = 'Success';
                $message    = 'All Ebooks Succeeded to be Deleted';
            } else {
                $status     = 'Success';
                $message    = 'Some Ebooks Succeeded to be Deleted';
            }

            return response()->json([
                'status'    =>  $status,
                'data'      =>  $deletedData,
                'message'   =>  $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function getEbookPublished(Request $request)
    {
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

        try {
            if($request->get('search')){
                $query  = $request->get('search');
                $data   = Ebook::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->orWhere('slug','LIKE','%'.$query.'%');
                })->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('is_published', Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])
                ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->paginate(10);
            } else {
                $data = Ebook::where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('is_published', Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])
                ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->paginate($paginate);
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

    public function getAllPublishedEbookInStudentLibrary(Request $request, $idUser)
    {
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

        try {
            $data = Ebook::select("ebook.*", 'ebook_library.status as library_status')
                        ->join("ebook_library", "ebook.id", "=", "ebook_library.id_ebook")
                        ->where('ebook.is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                        ->where('ebook_library.id_user', $idUser)
                        ->where('is_published', Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])
                        ->where("status", EbookLibrary::EBOOK_LIBRARY_STATUS["ACTIVE"])
                        ->with(array('ebookCategory', 'ebookPublisher' => function($query){
                            $query->select('id','name', 'email');
                        }))
                        ->orderBy('ebook.type','DESC')
                        ->paginate($paginate);

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

    public function getAllUnpaidEbook(Request $request)
    {
        $paginate = 10;
        if($request->get('paginate')){
            $paginate = $request->get('paginate');
        }

        try {
            $idUser = JWTAuth::parseToken()->authenticate()->id;
            $dataLibrary = Ebook::select("ebook.*")
                        ->join("ebook_library", "ebook.id", "=", "ebook_library.id_ebook")
                        ->where('ebook.is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                        ->where('ebook_library.id_user', $idUser)
                        ->groupBy('id_ebook')
                        ->pluck('ebook.id')->toArray();

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
                ->whereNotIn('id', $dataLibrary)
                ->paginate($paginate);
            } else {
                $data = Ebook::with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["PAID"])
                ->whereNotIn('id', $dataLibrary)
                ->paginate($paginate);
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

    public function getRecommendedEbook(Request $request){
        try {
            $idUser = JWTAuth::parseToken()->authenticate()->id;
            $dataLibrary = Ebook::select("ebook.*")
                        ->join("ebook_library", "ebook.id", "=", "ebook_library.id_ebook")
                        ->where('ebook.is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                        ->where('ebook_library.id_user', $idUser)
                        ->groupBy('id_ebook')
                        ->pluck('ebook.id')->toArray();

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
                ->whereNotIn('id', $dataLibrary)
                ->paginate(4);
            } else {
                $data = Ebook::with(array('ebookCategory', 'ebookPublisher' => function($query){
                    $query->select('id','name', 'email');
                }))
                ->where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                ->where('type', Ebook::EBOOK_TYPE["PAID"])
                ->whereNotIn('id', $dataLibrary)
                ->paginate(4);
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

    public function getRatingEbook($idEbook)
    {
        try {

            $data = Rating::where("target_id", $idEbook)
                            ->with(array("sender" => function ($query) {
                                $query->select("id", "email", "name", "role", "photo");
                            }))
                            ->with(array("serviceable"))
                            ->paginate(10);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $th->getMessage()
            ], 500);
        }
    }

    public function recordEbookInterest(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $dataLog = [
                "USER"      => $user,
                "USER_IP"   => $request->ip(),
                "EBOOK"     => Ebook::findOrFail($request->input('id_ebook'))
            ];

            LogApps::ebookDetail($dataLog);

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $user,
                'message'   =>  'Ebook Interest Log Recorded'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }
    }

    public function setEbookLibraryStatus($ebookLibraryId)
    {
        try {
            $ebookLibrary = EbookLibrary::findOrFail($ebookLibraryId);

            if ($ebookLibrary->status == EbookLibrary::EBOOK_LIBRARY_STATUS["ACTIVE"]) {
                $ebookLibrary->status = EbookLibrary::EBOOK_LIBRARY_STATUS["NON_ACTIVE"];
            } else {
                $ebookLibrary->status = EbookLibrary::EBOOK_LIBRARY_STATUS["ACTIVE"];
            }

            $ebookLibrary->save();

            return ResponseHelper::response(
                "Berhasil mengubah status Library Ebook untuk User ini !",
                null,
                200,
                "Success"
            );

        } catch(\Illuminate\Database\Eloquent\ModelNotFoundException $notFoundException){
            return ResponseHelper::response(
                "Data tidak ditemukan",
                null,
                404,
                "Failed"
            );
        } catch (\Throwable $th) {
            return ResponseHelper::response(
                "Gagal mengubah Library Ebook untuk User ini, silahkan coba lagi",
                null,
                400,
                "Failed"
            );
        }
    }
}
