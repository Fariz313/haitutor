<?php
namespace App\Helpers;

use App;
use Illuminate\Support\Facades\Http;

class ApiDataHelper {

    const Environment = [
        "DEVELOPMENT"   => 'development',
        "PRODUCTION"    => 'production'
    ];

    public static function getApi($action_url, $action_method) {
        $body = [
            "action_url" => $action_url,
            "action_method" => $action_method,
        ];

        $response = Http::get('http://haitutor.id/backend-educhat/api/list/allowed', $body);
        return json_decode($response);
    }

    public static function getPrimaryMenu($role) {
        $response = Http::get('http://haitutor.id/backend-educhat/api/menu/role/' . $role);
        return json_decode($response);
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
