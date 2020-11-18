<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;
use App;

class CloudKilatHelper {
    public static function delete($file_path) {
        try {
            if($file_path != null || $file_path == ''){
                // DELETE FROM S3
                $cloud_kilat_delete = Storage::disk('cloud_kilat')->delete($file_path);
                // END DELETE FROM S3

                if($cloud_kilat_delete){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
         } catch (\Throwable $th) {
            return null;
        }
    }

    public static function put($file_request, $dir, $file_type = 'other', $user_id) {
        try {
            if($file_type == 'image'){
                $manager = new ImageManager();

                // // REDUCE FILE
                $img = $manager->make($file_request)->resize(800, 800, function ($constraint) {$constraint->aspectRatio();});
                $img->stream();
                // // END REDUCE FILE

                // CREATING FILENAME & DIRECTORY
                // $filename = md5(uniqid(rand(), true)) . '.' . $file_request->getClientOriginalExtension();
                $filename = $user_id.'_'.$file_request->getClientOriginalName().'_'.Str::random(3).'.'.$file_request->getClientOriginalExtension();
                $directory = self::getEnvironment().$dir .'/' . date('F') . date('Y') .'/'. $filename;
                // END CREATING FILENAME & DIRECTORY

                // UPLOAD TO S3
                Storage::disk('cloud_kilat')->put($directory, $img);
                // END UPLOAD TO S3

                return $filename;
            }else if($file_type == "file"){
               // CREATING FILENAME & DIRECTORY
                $filename = $user_id.'_'.$file_request->getClientOriginalName().'_'.Str::random(3).'.'.$file_request->getClientOriginalExtension();
               $directory = $dir .'/' . date('F') . date('Y') .'/'. $filename;
               // END CREATING FILENAME & DIRECTORY

               // UPLOAD TO S3
               Storage::disk('cloud_kilat')->put($directory, file_get_contents($file_request));
               // END UPLOAD TO S3

               return $filename;
            } else {
                // CREATING DIRECTORY
                $directory = $dir .'/' . date('F') . date('Y');
                // END CREATING DIRECTORY

                // UPLOAD TO S3
                $filename = $file_request->store($directory, 'cloud_kilat');
                // END UPLOAD TO S3

                return $filename;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public static function getEnvironment()
    {
        if (App::environment("local")) {
            return "development";
        } else if (App::environment("production")) {
            return "production";
        }
    }
}
