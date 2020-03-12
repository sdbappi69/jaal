<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Library\CommonFunction;
use Validator;
use Session;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Str;

class RestApiTokenController extends Controller
{
    public $classCommonFunction;
    
    public function __construct(){
      $this->middleware('verifyLoginPage');
      $this->middleware('admin');
      $this->middleware('sufficientPermission');
      
      $this->classCommonFunction = new CommonFunction();
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $data = array();
      $data = $this->classCommonFunction->commonDataForAllPages();
      $search = $request->get('search');
      $user   = $request->get('user') != '' ? $request->get('user') : -1;
      
      $tokens = new ApiToken();
      if ($user != -1)
      $tokens = $tokens->where('user_id', $user);
      $tokens = $tokens->where('title', 'like', '%' . $search . '%')
                ->latest()
                ->paginate(15)
                ->withPath('?search=' . $search . '&user=' . $user);
      
      $data['tokens'] = compact('tokens');
      $data['users'] = User::where('user_status', 1)->get()->toArray();
      $data['route_name'] = \Request::route()->getName();
      $is_vendor = is_vendor_login(); 
      $sidebar['is_vendor_login'] = $is_vendor;
      $data['sidebar_data'] = $sidebar;

      
      return view('pages.admin.rest-api-token.index', $data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $get_default = $this->classCommonFunction->commonDataForAllPages();
      $get_default['users'] = User::where('user_status', 1)->get()->toArray(); 
      $get_default['route_name'] = \Request::route()->getName();
      $is_vendor = is_vendor_login(); 
      $sidebar['is_vendor_login'] = $is_vendor;
      $get_default['sidebar_data'] = $sidebar;
      
      return view('pages.admin.rest-api-token.create', $get_default);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      if( $request->isMethod('post') && Session::token() == $request->_token ){
        try {
            $validator = $this->getValidator($request);

            if ($validator->fails()) {
              return redirect()-> back()
              ->withInput()
              ->withErrors( $validator );
            }
            
            $get_token = ApiToken::where('user_id', $request->inputSelectUser)->get()->count();
            
            if($get_token > 0){
              Session::flash('error-message', __('admin.restapitoken.field_validation.user_unique_msg') );
              return redirect()-> back();
            }

            $apiToken = new ApiToken;
            $token    = Str::random(60);

            $apiToken->title  = $request->inputAPILabel;
            $apiToken->user_id  = $request->inputSelectUser;
            $apiToken->permissions  = $request->rest_api_permissions_type;
            $apiToken->token  = $token;

            if($apiToken->save()){  
              Session::flash('success-message', __('admin.post_message.saved', ['attribute' => __('admin.restapitoken.label')]) );
              return redirect()->route('rest-api-token.index');
            }
        } catch (Exception $exception) {
            Session::flash('error-message', __('admin.post_message.post_error_msg') );
            return redirect()-> back();
        }
      }
      
      return redirect()-> back(); 
    }

    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $get_default = $this->classCommonFunction->commonDataForAllPages();
      $get_default['users'] = User::where('user_status', 1)->get()->toArray();
      $get_default['token_data'] = ApiToken::find($id);
      $get_default['route_name'] = \Request::route()->getName();
      $is_vendor = is_vendor_login(); 
      $sidebar['is_vendor_login'] = $is_vendor;
      $get_default['sidebar_data'] = $sidebar;
      
      return view('pages.admin.rest-api-token.edit', $get_default);
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
      if( $request->isMethod('PUT') && Session::token() == $request->_token ){
        try {
          $validator = $this->getValidator($request);
          
          if ($validator->fails()) {
            return redirect()-> back()
            ->withInput()
            ->withErrors( $validator );
          }
          
          $get_token_user = ApiToken::where('user_id', $request->inputSelectUser)->first();
          $get_token_id   = ApiToken::where('id', $id)->first();
          
          if(!empty($get_token_user) && $get_token_user->count() > 0 && !empty($get_token_id) && $get_token_user->user_id != $get_token_id->user_id){
            Session::flash('error-message', __('admin.restapitoken.field_validation.user_unique_msg') );
            return redirect()-> back();
          }
          
          $data = array(
            'title' => $request->inputAPILabel,
            'user_id' => $request->inputSelectUser,
            'permissions' => $request->rest_api_permissions_type
          );
          
          if( ApiToken::where('id', $id)->update($data)){
            Session::flash('success-message', __('admin.post_message.updated', ['attribute' => __('admin.restapitoken.label')]) );
            return redirect()->route('rest-api-token.index');
          }
            
        } catch (Exception $exception) {
            Session::flash('error-message', __('admin.post_message.post_error_msg') );
            return redirect()-> back();
        }
      }
      
      return redirect()-> back(); 
    }
    
    /**
     * Gets a new validator instance with the defined rules.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Support\Facades\Validator
     */
    protected function getValidator($request)
    {
      $rules = [
        'inputAPILabel' => 'required',
        'inputSelectUser' => 'required',
        'rest_api_permissions_type' => 'required'
      ];
      
      $customMessages = [
        'inputAPILabel.required' => __('admin.restapitoken.field_validation.title_field_required_msg'),
        'inputSelectUser.required' => __('admin.restapitoken.field_validation.user_field_required_msg'),
        'rest_api_permissions_type.required' => __('admin.restapitoken.field_validation.permissions_field_required_msg')  
      ];

      return Validator::make($request->all(), $rules, $customMessages);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      if(Session::token() == \Request::get('_token') ){
        if(ApiToken::where('id', $id)->delete()){
          Session::flash('success-message', __('admin.post_message.deleted', ['attribute' => __('admin.restapitoken.label')]) );
          return redirect()->route('rest-api-token.index');
        }
      }
      
      return redirect()-> back(); 
    }
}