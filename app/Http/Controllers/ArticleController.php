<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Article;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;


class ArticleController extends Controller {
    
    public function getArticle(){
    
         try{
           
           $artikel_data = Article::all()->take(5);
           
           return response()->json([
               "status"=>"sukses",
               "data"=>$artikel_data
               ],200);
        
         }catch(\Throwable $e){
             
              return response()->json([
               "status"=>"gagal",
               "error"=>$e
               ],500);
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
            try{
                $photo = $request->file('image');
                $tujuan_upload = 'temp/article';
                $photo_name = Str::random(2).'_'.$photo->getClientOriginalName().'_'.Str::random(3).'.'.$photo->getClientOriginalExtension();
                $photo->move($tujuan_upload,$photo_name);
                $data->image = $photo_name;
                
            }catch(\throwable $e){
                return response()->json([
                    'status'	=> 'failed',
                    'message'	=> 'image not uploaded'
                ], 400);
            }
            $data->save();

    		return response()->json([
    			'status'	=> 'success',
    			'message'	=> 'Article added successfully'
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