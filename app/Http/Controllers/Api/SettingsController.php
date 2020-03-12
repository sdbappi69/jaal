<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Carbon;
use App\Http\Controllers\OptionController; 
use App\Http\Resources\ReportCollection as ReportResourceCollection;

class SettingsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if( $request->isMethod('get')){
            $settings = array();
            $option_obj = new OptionController();
            $get_general_settings = $option_obj->getSettingsData();
            $get_apperance_settings = $option_obj->getAppearanceData();
            $get_menu_settings = $option_obj->getMenuData();
            
            $settings = [
                'general' => $get_general_settings['general_settings'],
                'apperance' => $get_apperance_settings,
                'menu' => $get_menu_settings   
            ];

            return response()->json($settings);
        }

        return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}