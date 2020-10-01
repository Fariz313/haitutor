<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $headers = [
            'Content-type' => 'application/json',
            'Authorization'=> 'key=AAAAq5PEITQ:APA91bE9Z7KmH5BDUi_fQJ8KCId7g0hdfrW8tEVmhRHwR4l7AtVwKFiNKJc3oklbkcSAFRvFqipPPKKwarYwICVcHCti0_QdeDbduDcHX6_3KpuqgeMc4C6l5-4Kw0UNolt1SViVXFCh',
        ];

        $body = [
            'data' => [
                "title" => "Notif Penting A",
                "message" => "Harusnya Tetap Bisa",
                "user_id" => 19,
                "tutor_id" => 30,
            ],
            'to' => "eJLGS7DcQgab5nSaXaLI0P:APA91bGbcPEVfSdgbCU1iiqfq0YZDR_0iuyJ6lVeQwsBfojT2bj-_h6ksvPJOwIJ1W8HuSySANRltHUXZZ7YvNiDMEIlHrK8FuMYHjNHcX4zrztf3EfaABM0UESILgsaGbEr3sGxyiCZ"
        ];

        $response = Http::withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send', $body);

        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
