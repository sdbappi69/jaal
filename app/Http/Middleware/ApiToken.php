<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Route;

class ApiToken
{ 
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $header = $request->header('apitoken');
        
        if(!isset($header)){
          return response()->json(__('api.middleware.token_required'), 400);
        }
        
        $get_token_data = get_rest_api_data_by_token($header);
        
        if(empty($get_token_data)){
          return response()->json(__('api.middleware.unauthorized'), 401);
        }
        
        if(!empty($get_token_data)){
          if(is_array($get_token_data) && count($get_token_data) >0){
            if(isset($get_token_data['permissions']) && $get_token_data['permissions'] != 'read_write'){
              $method = array('GET' => 'read', 'POST' => 'write', 'PUT' => 'write', 'PATCH' => 'write', 'DELETE' => 'write');    
              
              if(is_array($method)){
                if($method[$request->method()] != $get_token_data['permissions']){
                  return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
                }
              }
            }
          }
        }

        $userAgent = request()->header('User-Agent');
        if(!empty($userAgent)){
          return response()->json(__('api.middleware.bad_request'), 400);
        }

        return $next($request);
    }
}
