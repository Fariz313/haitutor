<?php

namespace App\Http\Controllers;

use App\Menu;
use App\PrimaryMenu;
use App\SubMenu;
use Illuminate\Http\Request;

class MenuController extends Controller
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
        try {
            $data                   = new Menu();
            $data->name             = $request->input('name');
            $data->action_url       = $request->input('action_url');
            $data->action_method    = $request->input('action_method');

            if($request->input('icon')){
                $data->icon         = $request->input('icon');
            } else {
                $data->icon         = 'fa fa-user-o';
            }
            if($request->input('is_menu')){
                $data->is_menu      = $request->input('is_menu');
            }
            $data->save();

            if($request->input('id_parent_menu')){
                $subMenu                    = new SubMenu();
                $subMenu->id_parent_menu    = $request->input('id_parent_menu');
                $subMenu->id_child_menu     = $data->id;
                $subMenu->save();
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Insert Menu Succeeded'
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
        try {
            $data                   = Menu::findOrFail($id);
            if($request->input('name')){
                $data->name         = $request->input('name');
            }
            if($request->input('action_url')){
                $data->action_url   = $request->input('action_url');
            }
            if($request->input('action_method')){
                $data->action_method= $request->input('action_method');
            }
            if($request->input('icon')){
                $data->icon         = $request->input('icon');
            }
            if($request->input('is_menu')){
                $data->is_menu      = $request->input('is_menu');
            }
            $data->save();

            if($request->input('id_parent_menu')){
                $subMenu                    = SubMenu::where('id_child_menu', $id)->first();
                if($request->input('id_parent_menu') == 0){
                    $subMenu->delete();
                } else {
                    $subMenu->id_parent_menu    = $request->input('id_parent_menu');
                    $subMenu->save();
                }
            }

            return response()->json([
                'status'    =>  'Success',
                'data'      =>  $data,
                'message'   =>  'Update Menu Succeeded'
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
            $data           = Menu::findOrFail($id);
            $message        = 'Menu Deleted';

            if($data->is_deleted == Menu::STATUS_MENU_DELETED["DELETED"]){
                $message    = 'Menu Already Deleted';
            } else {
                $subMenu             = SubMenu::where('id_child_menu', $id)->first();
                $subMenu->delete();

                $data->is_deleted    = Menu::STATUS_MENU_DELETED["DELETED"];
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

    public function getPrimaryMenu(){
        try {
            $data = PrimaryMenu::with(array('subMenu' => function($query){
                $query->select('menu.*','sub_menu.*')->join("menu", "sub_menu.id_child_menu", "=", "menu.id");
            }))->where('is_deleted', Menu::STATUS_MENU_DELETED["ACTIVE"])->get();
            
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
