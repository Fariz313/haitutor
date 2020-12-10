<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Article;
use App\Helpers\CloudKilatHelper;
use App\Helpers\GoogleCloudStorageHelper;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class ArticleController extends Controller {

    public function getAll(Request $request){

         try{

            if($request->get('search')){
                $query = $request->get('search');
                $data = Article::where(function ($where) use ($query){
                    $where->where('title','LIKE','%'.$query.'%')
                        ->orWhere('content','LIKE','%'.$query.'%');
                } )->paginate(10);
            }else{
                $data = Article::paginate(10);
            }

            return response()->json([
                'status'    =>  'success',
                'data'      =>  $data,
                'message'   =>  'Get Data Success'
            ]);

         }catch(\Throwable $e){

              return response()->json([
               "status"=>"gagal",
               "error"=>$e
               ],500);
         }
    }

    public function getOne($id)
    {
        try {
            $data   =   Article::findOrFail($id);

            return response()->json([
                'status'    =>  'success',
                'message'   =>  'Get Data Success',
                'data'      =>  $data
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'    =>  'failed',
                'data'      =>  'No Data Picked',
                'message'   =>  'Get Data Failed'
            ]);
        }
    }

    public function store(Request $request)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'content'   => 'required|string',
				'title'	    => 'required|string',
				'image'	    => 'required|file',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                  = new Article();
            $data->title           = $request->input('title');
            $data->content         = $request->input('content');
            // $data->image           = CloudKilatHelper::put($request->file('image'), '/photos/article', 'image', Str::random(3));
            $data->image           = GoogleCloudStorageHelper::put($request->file('image'), '/photos/article', 'image', Str::random(3));
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Article added successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try{
    		$validator = Validator::make($request->all(), [
    			'content'   => 'string',
				'title'	    => 'string',
				'image'	    => 'file',
    		]);

    		if($validator->fails()){
    			return response()->json([
                    'status'    =>'failed validate',
                    'error'     =>$validator->errors()
                ],400);
    		}

            $data                   = Article::findOrFail($id);
            if ($request->input('content')) {
                $data->content         = $request->input('content');
            }
            if ($request->input('title')) {
                $data->title        = $request->input('title');
            }
            if ($request->input('image')) {

                // CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/photos/article'.$data->image);
                GoogleCloudStorageHelper::delete('/photos/article'.$data->image);
                // $data->image           = CloudKilatHelper::put($request->file('image'), '/photos/article', 'image', Str::random(3));
                $data->image           = GoogleCloudStorageHelper::put($request->file('image'), '/photos/article', 'image', Str::random(3));

            }
	        $data->save();

    		return response()->json([
    			'status'	=> 'success',
                'message'	=> 'Article updated successfully',
                'data'      => $data
    		], 201);

        } catch(\Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        try{

            $article   = Article::findOrFail($id);

            // Delete from s3
            // CloudKilatHelper::delete(CloudKilatHelper::getEnvironment().'/photos/article'.$article->image);
            GoogleCloudStorageHelper::delete('/photos/article'.$article->image);

            $delete = Article::findOrFail($id)->delete();

            if($delete){
              return response([
              	"status"	=> "success",
                  "message"   => "Article deleted successfully"
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
