<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function response($message, $data, $code, $status) {
        try {
            return response()->json([
                "status" => $status ? "Error" : "error",
                "message" => $message,
                "data" => $data,
            ], $code);
        } catch (\Throwable $th) {
            return response()->json([
                "status" => $status ? "Error" : "error",
                "message" => $message,
            ], $code);
        }
    }
}

