<?php

namespace App\Http\Controllers;

use App\Helpers\GoogleCloudStorageHelper;
use App\Answer;
use App\AnswerDoc;
use App\Question;
use App\QuestionDoc;
use App\RoomAsk;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JWTAuth;

class QuickAskController extends Controller
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
                $data   = Question::where(function ($where) use ($query){
                    $where->where('name','LIKE','%'.$query.'%')
                    ->where('is_deleted', Question::QUESTION_DELETED_STATUS["ACTIVE"]);
                })->with('documents')->paginate(10);
            } else {
                $data = Question::where('is_deleted', Question::QUESTION_DELETED_STATUS["ACTIVE"])->with('documents')->paginate(10);
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

            $data               = new Question();
            $data->id_user      = JWTAuth::parseToken()->authenticate()->id;
            $data->message      = $request->input('message');
            $data->expired_at   = date('Y-m-d H:i:s', strtotime($data->created_at . '+' . Question::EXPIRED_DAYS . ' days'));
            $data->save();

            if($request->file('document_1')){
                $dataDoc                = new QuestionDoc();
                $dataDoc->id_question   = $data->id;
                $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_1'), '/photos/question', 'image', Str::random(3));
                $dataDoc->save();
            }
            if($request->file('document_2')){
                $dataDoc                = new QuestionDoc();
                $dataDoc->id_question   = $data->id;
                $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_2'), '/photos/question', 'image', Str::random(3));
                $dataDoc->save();
            }
            if($request->file('document_3')){
                $dataDoc                = new QuestionDoc();
                $dataDoc->id_question   = $data->id;
                $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_3'), '/photos/question', 'image', Str::random(3));
                $dataDoc->save();
            }

            $data = Question::where('id', $data->id)->with('documents')->first();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Insert Question Succeeded'
            ], 200);
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
            $data = Question::where('id', $id)->with('documents')->first();

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
            $data           = Question::findOrFail($id);

            if($request->input('name')){
                $data->name     = $request->input('name');
            }
            $data->save();


            $existingDoc = $request->input('id_document');
            if($existingDoc){
                // Delete Unused Document
                if(count(json_decode($existingDoc)) != 0){
                    $deletedDoc     = QuestionDoc::where('id_question', $id)->whereNotIn('id', json_decode($request->input('id_document')))->get();
                } else {
                    $deletedDoc     = QuestionDoc::where('id_question', $id)->get();
                }

                foreach($deletedDoc as $doc){
                    $document = QuestionDoc::findOrFail($doc->id);
                    GoogleCloudStorageHelper::delete('/photos/question/'.$doc->content);
                    $document->delete();
                }

                // Insert New Document
                if($request->file('document_1')){
                    $dataDoc                = new QuestionDoc();
                    $dataDoc->id_question   = $data->id;
                    GoogleCloudStorageHelper::delete('/photos/question/'.$data->front_cover);
                    $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_1'), '/photos/question', 'image', Str::random(3));
                    $dataDoc->save();
                }
                if($request->file('document_2')){
                    $dataDoc                = new QuestionDoc();
                    $dataDoc->id_question   = $data->id;
                    $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_2'), '/photos/question', 'image', Str::random(3));
                    $dataDoc->save();
                }
                if($request->file('document_3')){
                    $dataDoc                = new QuestionDoc();
                    $dataDoc->id_question   = $data->id;
                    $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_3'), '/photos/question', 'image', Str::random(3));
                    $dataDoc->save();
                }
            }

            $data = Question::where('id', $data->id)->with('documents')->first();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Question Succeeded'
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
        //
    }

    public function answerQuestion(Request $request){
        try {

            $user       = JWTAuth::parseToken()->authenticate();
            if($request->input('id_room')){
                // For Response Answer (Student)
                $room       = RoomAsk::findOrFail($request->input('id_room'));
            } else {
                // For Answer the Question (Tutor)
                $room       = RoomAsk::where('id_question', $request->input('id_question'))->where('id_answerer', $user->id)->first();

                if($room == null){
                    $room               = new RoomAsk();
                    $room->id_question  = $request->input('id_question');
                    $room->id_answerer  = $user->id;
                    $room->save();
                }
            }

            $data               = new Answer();
            $data->id_room      = $room->id;
            $data->id_user      = $user->id;
            $data->message      = $request->input('message');
            $room->id_user      = JWTAuth::parseToken()->authenticate()->id;
            $data->save();

            if($request->file('document_1')){
                $dataDoc                = new AnswerDoc();
                $dataDoc->id_answer     = $data->id;
                $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_1'), '/photos/answer', 'image', Str::random(3));
                $dataDoc->save();
            }
            if($request->file('document_2')){
                $dataDoc                = new AnswerDoc();
                $dataDoc->id_answer     = $data->id;
                $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_2'), '/photos/answer', 'image', Str::random(3));
                $dataDoc->save();
            }
            if($request->file('document_3')){
                $dataDoc                = new AnswerDoc();
                $dataDoc->id_answer     = $data->id;
                $dataDoc->content       = GoogleCloudStorageHelper::put($request->file('document_3'), '/photos/answer', 'image', Str::random(3));
                $dataDoc->save();
            }

            $data = Answer::where('id', $data->id)->with('documents')->first();

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Insert Answer Succeeded'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status"   => "Failed",
                "message"  => $e->getMessage()
            ], 500);
        }

    }
}
