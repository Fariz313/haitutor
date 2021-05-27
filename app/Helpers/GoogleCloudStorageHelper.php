<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use App;

class GoogleCloudStorageHelper
{
    public static function put($file_request, $dir, $file_type = "other", $user_id = null)
    {
        try {

            if ($file_type == "image") {
                $manager = new ImageManager();

                // // REDUCE FILE
                $img = $manager->make($file_request)->resize(800, 800, function ($constraint) {$constraint->aspectRatio();});
                $img->stream();
                // // END REDUCE FILE

                // CREATING FILENAME & DIRECTORY
                $filename = md5(uniqid(rand(), true)) . '.' . $file_request->getClientOriginalExtension();
                // $filename = $user_id.'_'.$file_request->getClientOriginalName().'_'.Str::random(3).'.'.$file_request->getClientOriginalExtension();
                $directory = $dir .'/'. $filename;
                // END CREATING FILENAME & DIRECTORY

                // UPLOAD TO GCS
                Storage::disk('gcs')->put($directory, $img);
                Storage::disk("gcs")->setVisibility($directory, "public");
                // END UPLOAD TO GCS

                return $filename;
            } else if ($file_type == "file") {
                // CREATING FILENAME & DIRECTORY
                $filename = md5(uniqid(rand(), true)) . '.' . $file_request->getClientOriginalExtension();
                // $filename = $user_id.'_'.$file_request->getClientOriginalName().'_'.Str::random(3).'.'.$file_request->getClientOriginalExtension();
                $directory = $dir .'/'. $filename;
                // END CREATING FILENAME & DIRECTORY

                // UPLOAD TO GCS
                Storage::disk('gcs')->put($directory, file_get_contents($file_request));
                Storage::disk("gcs")->setVisibility($directory, "public");

                // END UPLOAD TO GCS

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

    public static function delete($file_path) {
        try {
            if($file_path != null || $file_path == ''){
                // DELETE FROM GCS
                $cloud_kilat_delete = Storage::disk('gcs')->delete($file_path);
                // END DELETE FROM GCS

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

    public static function getSignedUrl($file_path, $duration = 3600)
    {
        try {
            if ($file_path != null || $file_path == '') {
                return Storage::disk("gcs")
                                ->getAdapter()
                                ->getBucket()
                                ->object($file_path)
                                ->signedUrl(new \DateTime("+".$duration." seconds"));
            } else {
                return "Empty Url";
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}

