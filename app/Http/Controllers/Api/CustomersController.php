<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerCollection as CustomerResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;
use App\Models\RoleUser;
use App\Models\UsersDetail;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->isMethod('get')){
          $currentPage = 1;
          $perPage = 15;
          $search = '';
          $order = 'desc';
          $orderby = 'id';
          $status;
          $email = '';
          $role = 'site-user';
          
          if(isset($request['page']) && !empty($request['page'])){
            $currentPage = $request['page'];
          }
          
          if(isset($request['per_page']) && !empty($request['per_page'])){
            $perPage = $request['per_page'];
          }
          
          if(isset($request['search']) && !empty($request['search'])){
            $search = $request['search'];
          }
          
          if(isset($request['order']) && !empty($request['order'])){
            $order = $request['order'];
          }
          
          if(isset($request['orderby']) && !empty($request['orderby'])){
            $orderby = $request['orderby'];
          }

          if(isset($request['status'])){
            $status = $request['status'];
          }
          
          if(isset($request['email']) && !empty($request['email'])){
            $email = $request['email'];
          }

          if(isset($request['role']) && !empty($request['role'])){
            $role = $request['role'];
          }
          
          
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });

          
          $user = DB::table('users'); 
          $users = $user->orderBy($orderby, $order);
          
          if(!empty($search)){
            $users->where('users.name', 'like', '%' . $search . '%');
          }
          
          if(isset($status)){
            $users->where('users.user_status', $status);
          }

          if(!empty($email)){
            $users->where('users.email', $email);
          }

          if(!empty($role) && $role != 'all'){
            $users->where('roles.slug', $role);
          }

          $users->join('role_user', 'users.id', '=', 'role_user.user_id');
          $users->join('roles', 'role_user.role_id', '=', 'roles.id');
          $users->leftJoin('users_details', 'users.id', '=', 'users_details.user_id');
          $users->leftJoin('user_role_permissions', 'roles.id', '=', 'user_role_permissions.role_id');
          $users->select(DB::raw('users.id as id'), DB::raw('users.display_name as name'), DB::raw('users.name as username'), DB::raw('users.email as email'), DB::raw('users.user_photo_url as profile_img'), DB::raw('users.user_status as status'), DB::raw('users_details.details as profile_details'), DB::raw('user_role_permissions.permissions as access_list'), DB::raw('roles.id as role_id'), DB::raw('roles.slug as role_handle'), DB::raw('roles.role_name as role_name'), DB::raw('users.created_at as created_date'), DB::raw('users.updated_at as updated_date'));
          
          $users = $users->paginate($perPage);
          return response()->json(new CustomerResourceCollection($users));
        }
        
        return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }

    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      if( $request->isMethod('post')){
        $validator = $this->getValidator($request);   
          
        if ($validator->fails()) {
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        if( (array_key_exists('billing', $request->all()) && !array_key_exists('shipping', $request->all())) ||  (array_key_exists('shipping', $request->all()) && !array_key_exists('billing', $request->all())) ){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }
        
        if(array_key_exists('billing', $request->all())){
          if( (!array_key_exists('title', $request->billing)) || (!array_key_exists('company_name', $request->billing)) || (!array_key_exists('first_name', $request->billing)) || (!array_key_exists('last_name', $request->billing)) || (!array_key_exists('email', $request->billing)) || (!array_key_exists('phone_number', $request->billing)) ||  (!array_key_exists('country', $request->billing)) || (!array_key_exists('address_line_1', $request->billing)) || (!array_key_exists('address_line_2', $request->billing)) || (!array_key_exists('town_or_city', $request->billing)) || (!array_key_exists('zip_or_postal_code', $request->billing)) || (!array_key_exists('fax_number', $request->billing)) ){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
        }
        
        if(array_key_exists('shipping', $request->all())){
          if( (!array_key_exists('title', $request->shipping)) || (!array_key_exists('company_name', $request->shipping)) || (!array_key_exists('first_name', $request->shipping)) || (!array_key_exists('last_name', $request->shipping)) || (!array_key_exists('email', $request->shipping)) || (!array_key_exists('phone_number', $request->shipping)) ||  (!array_key_exists('country', $request->shipping)) || (!array_key_exists('address_line_1', $request->shipping)) || (!array_key_exists('address_line_2', $request->shipping)) || (!array_key_exists('town_or_city', $request->shipping)) || (!array_key_exists('zip_or_postal_code', $request->shipping)) || (!array_key_exists('fax_number', $request->shipping)) ){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
        }
        
        $User = new User;
        $Role = new Role;
        $Roleuser = new RoleUser;

        $get_role = Role::where(['slug' => 'site-user'])->first();
        if(empty($get_role) || $get_role->count() == 0){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        $billing_and_shipping_data = array();

        //billing check for customer
        if(array_key_exists('billing', $request->all())){
          $rules =  [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'country' => 'required',
            'address_line_1' => 'required',
            'town_or_city' => 'required',
            'zip_or_postal_code' => 'required'
          ];
          
          $validator = Validator:: make($request->billing, $rules);

          if($validator->fails()){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
          else{
            $billing_and_shipping_data['account_bill_title'] = $request->billing['title'];
            $billing_and_shipping_data['account_bill_company_name'] = $request->billing['company_name'];
            $billing_and_shipping_data['account_bill_first_name'] = $request->billing['first_name'];
            $billing_and_shipping_data['account_bill_last_name'] = $request->billing['last_name'];
            $billing_and_shipping_data['account_bill_email_address'] = $request->billing['email'];
            $billing_and_shipping_data['account_bill_phone_number'] = $request->billing['phone_number'];
            $billing_and_shipping_data['account_bill_select_country'] = $request->billing['country'];
            $billing_and_shipping_data['account_bill_adddress_line_1'] = $request->billing['address_line_1'];
            $billing_and_shipping_data['account_bill_adddress_line_2'] = $request->billing['address_line_2'];
            $billing_and_shipping_data['account_bill_town_or_city'] = $request->billing['town_or_city'];
            $billing_and_shipping_data['account_bill_zip_or_postal_code'] = $request->billing['zip_or_postal_code'];
            $billing_and_shipping_data['account_bill_fax_number'] = $request->billing['fax_number']; 
          }
        }

        //shipping check for customer
        if(array_key_exists('shipping', $request->all())){
          $rules =  [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'country' => 'required',
            'address_line_1' => 'required',
            'town_or_city' => 'required',
            'zip_or_postal_code' => 'required'
          ];

          $validator = Validator:: make($request->shipping, $rules);

          if($validator->fails()){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
          else{
            $billing_and_shipping_data['account_shipping_title'] = $request->shipping['title'];
            $billing_and_shipping_data['account_shipping_company_name'] = $request->shipping['company_name'];
            $billing_and_shipping_data['account_shipping_first_name'] = $request->shipping['first_name'];
            $billing_and_shipping_data['account_shipping_last_name'] = $request->shipping['last_name'];
            $billing_and_shipping_data['account_shipping_email_address'] = $request->shipping['email'];
            $billing_and_shipping_data['account_shipping_phone_number'] = $request->shipping['phone_number'];
            $billing_and_shipping_data['account_shipping_select_country'] = $request->shipping['country'];
            $billing_and_shipping_data['account_shipping_adddress_line_1'] = $request->shipping['address_line_1'];
            $billing_and_shipping_data['account_shipping_adddress_line_2'] = $request->shipping['address_line_2'];
            $billing_and_shipping_data['account_shipping_town_or_city'] = $request->shipping['town_or_city'];
            $billing_and_shipping_data['account_shipping_zip_or_postal_code'] = $request->shipping['zip_or_postal_code'];
            $billing_and_shipping_data['account_shipping_fax_number'] = $request->shipping['fax_number']; 
          }
        }

        $User->display_name = $request->display_name;
        $User->name = $request->user_name;
        $User->email = $request->email;
        $User->password = bcrypt( trim('customers') );
        $User->user_photo_url = '';
        $User->user_status = $request->status;
        $User->secret_key = bcrypt( trim($request->secret_key) );

        if($User->save()){  
          $Roleuser->user_id = $User->id;
          $Roleuser->role_id = $get_role->id;

          if($Roleuser->save()){
            if(is_array($billing_and_shipping_data) && count($billing_and_shipping_data) > 0){
              $user_details = new UsersDetail;
              $account_data_details = array('address_details' => $billing_and_shipping_data, 'wishlists_details' => '');

              $user_details->user_id = $User->id;
              $user_details->details = json_encode( $account_data_details );

              if($user_details->save()){
                return response()->json(__('api.middleware.created_successfully', array('attribute' => 'customer')), 200);
              }
            }  

            return response()->json(__('api.middleware.created_successfully', array('attribute' => 'customer')), 200);
          }
        }
      }
      
      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
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
      if( $request->isMethod('PUT') || $request->isMethod('PATCH') ){
        if(!preg_match('/^\d+$/', $id) ){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        if($id == 0){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        $get_data = $request->all();

        if(is_array($get_data) && count($get_data) > 0){
          unset($get_data['api_token']);
        }

        if(is_array($get_data) && count($get_data) == 0){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        $update_data = array();

        if(is_array($get_data) && count($get_data) > 0){
          $get_user_data = User::find($id);
          if(empty($get_user_data)){
            return response()->json(__('api.middleware.bad_parameter'), 400);
          }
          
          if(isset($get_data['display_name']) && !empty($get_data['display_name'])){
            $update_data['display_name'] = strip_tags($get_data['display_name']);
          }

          if(array_key_exists('user_name', $get_data)){
            if($get_data['user_name'] != $get_user_data->name){
              $rules =  [
                'user_name' => 'unique:users,name'
              ];
  
              $validator = Validator:: make($get_data, $rules);
  
              if($validator->fails()){
                return response()->json(__('api.middleware.bad_parameter'), 400);  
              }
              else{
                $update_data['name'] = $get_data['user_name'];
              }
            }
            else{
              $update_data['name'] = $get_data['user_name'];
            }
          }

          if(array_key_exists('email', $get_data)){
            if($get_data['email'] != $get_user_data->email){
              $rules =  [
                'email' => 'email|unique:users,email'
              ];
  
              $validator = Validator:: make($get_data, $rules);
  
              if($validator->fails()){
                return response()->json(__('api.middleware.bad_parameter'), 400);  
              }
              else{
                $update_data['email'] = $get_data['email'];
              }
            }
            else{
              $update_data['email'] = $get_data['email'];
            }
          }

          if(array_key_exists('status', $get_data)){
            $rules =  [
              'status' => 'integer|between:0,1'
            ];

            $validator = Validator:: make($get_data, $rules);

            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
            else{
              $update_data['user_status'] = $get_data['status'];
            }
          }
        }
        
        if(is_array($update_data) && count($update_data) > 0){
          if( User::where('id', $id)->update($update_data)){
            return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'customer')), 200);
          }

          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
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
        'display_name' => 'required',
        'user_name' => 'required|unique:users,name',
        'email' => 'required|email|unique:users,email',
        'status' => 'required|integer|between:0,1',
        'secret_key' => 'required'
      ];
      
      $customMessages = [
        'display_name.required' => __('api.field_validation.customers.display_name_field_required_msg'),
        'user_name.required' => __('api.field_validation.customers.user_name_field_required_msg'),
        'email.required' => __('api.field_validation.customers.email_field_required_msg'),
        'status.required' => __('api.field_validation.customers.status_field_required_msg'),
        'display_name.required' => __('api.field_validation.customers.secret_key_field_required_msg')
      ];

      return Validator::make($request->all(), $rules, $customMessages);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
      if( $request->isMethod('DELETE')){
        if(!preg_match('/^\d+$/', $id) ){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        if($id == 0){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        if(User::where('id', $id)->delete()){
          if(RoleUser::where('user_id', $id)->delete()){
            UsersDetail::where('user_id', $id)->delete();
            return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'customer')), 200);
          }
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}