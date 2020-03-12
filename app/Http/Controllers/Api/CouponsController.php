<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostExtra;
use App\Http\Resources\CouponCollection as CouponResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;

class CouponsController extends Controller
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
          $after = '';
          $before = '';
          $order = 'desc';
          $orderby = 'id';
          $handle = '';
          $status;
          $code;
          
          if(isset($request['page']) && !empty($request['page'])){
            $currentPage = $request['page'];
          }
          
          if(isset($request['per_page']) && !empty($request['per_page'])){
            $perPage = $request['per_page'];
          }
          
          if(isset($request['search']) && !empty($request['search'])){
            $search = $request['search'];
          }
          
          if(isset($request['after']) && !empty($request['after'])){
            $after = $request['after'];
          }
          
          if(isset($request['before']) && !empty($request['before'])){
            $before = $request['before'];
          }
          
          if(isset($request['order']) && !empty($request['order'])){
            $order = $request['order'];
          }
          
          if(isset($request['orderby']) && !empty($request['orderby'])){
            $orderby = $request['orderby'];
          }
          
          if(isset($request['handle']) && !empty($request['handle'])){
            $handle = $request['handle'];
          }
          
          if(isset($request['status'])){
            $status = $request['status'];
          }

          if(isset($request['code']) && !empty($request['code'])){
            $code = $request['code'];
          }
          
          $post = new Post();
          
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });
          
          
          $pages = $post->where('post_type', 'user_coupon')
                        ->orderBy($orderby, $order);
          
          if(!empty($search)){
            $pages->where('post_title', 'like', '%' . $search . '%');
          }
          
          if(!empty($handle)){
            $pages->where('post_slug', $handle);
          }
          
          if(isset($status)){
            $pages->where('post_status', $status);
          }
          
          if(!empty($after) && !empty($before)){
            $pages->whereBetween('created_at', array($after, $before.' 23:59:59'));
          }
          elseif(!empty($after) && empty($before)){
            $pages->where('created_at', '>=', $after.' 00:00:00');
          }
          elseif(empty($after) && !empty($before)){
            $pages->where('created_at', '<=', $before.' 23:59:59');
          }

          if(!empty($code)){
            $pages->where('post_title', $code);
          }
          
          $pages = $pages->paginate($perPage);
                        
          
          return response()->json(new CouponResourceCollection($pages));
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

        $post = new Post;
        $desc = '';
        $post_slug = create_unique_slug('post', $request->code);
        $author_id = get_roles_details_by_role_slug('administrator');

        if(isset($request->description) && !empty($request->description)){
          $desc = $request->description;
        }

        $post->post_author_id = $author_id->id;
        $post->post_content = string_encode($desc);
        $post->post_title = strip_tags($request->code);
        $post->post_slug = $post_slug;
        $post->parent_id = 0;
        $post->post_status = $request->status;
        $post->post_type = 'user_coupon';

        if($post->save()){
          if(PostExtra::insert(array(
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_coupon_condition_type',
                    'key_value'     =>  $request->discount_type,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_coupon_amount',
                    'key_value'     =>  $request->amount,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_coupon_min_restriction_amount',
                    'key_value'     =>  '',
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_coupon_max_restriction_amount',
                    'key_value'     =>  '',
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_coupon_allow_role_name',
                    'key_value'     =>  'no_role',
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_usage_limit_per_coupon',
                    'key_value'     =>  '',
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),                      
              array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_usage_range_end_date',
                    'key_value'     =>  $request->end_date,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              )
          ))){
            return response()->json(__('api.middleware.created_successfully', array('attribute' => 'coupon')), 200);
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
          if(isset($get_data['code']) && !empty($get_data['code'])){
            $update_data['post_title'] = strip_tags($get_data['code']);
          }

          if(isset($get_data['description']) && !empty($get_data['description'])){
            $update_data['post_content'] = string_encode($get_data['description']);
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
              $update_data['post_status'] = $get_data['status'];
            }
          }
        }
        
        $is_updated = false;
        if(is_array($update_data) && count($update_data) > 0){
          if( Post::where('id', $id)->update($update_data)){
            $is_updated = true;
          }
        }

        if(isset($get_data['discount_type']) && !empty($get_data['discount_type'])){
          $discount_type = array(
            'key_value' => $get_data['discount_type']
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_coupon_condition_type'])->update($discount_type);
          $is_updated = true;
        }

        if(isset($get_data['amount']) && !empty($get_data['amount'])){
          $amount = array(
            'key_value' => $get_data['amount']
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_coupon_amount'])->update($amount);
          $is_updated = true;
        }

        if(array_key_exists('end_date', $get_data)){
          $rules =  [
            'end_date' => 'date_format:Y-m-d'
          ];

          $validator = Validator:: make($get_data, $rules);

          if($validator->fails()){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
          else{
            $end_date = array(
              'key_value' => $get_data['end_date']
            );
  
            PostExtra::where(['post_id' => $id, 'key_name' => '_usage_range_end_date'])->update($end_date);
            $is_updated = true;
          }
        }

        if($is_updated){
          return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'coupon')), 200);
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
        'code' => 'required',
        'discount_type' => 'required',
        'amount' => 'required',
        'end_date' => 'required|date_format:Y-m-d',
        'status' => 'required|integer|between:0,1'
      ];
      
      $customMessages = [
        'code.required' => __('api.field_validation.coupon.code_field_required_msg'),
        'status.required' => __('api.field_validation.coupon.status_field_required_msg'),
        'discount_type.required' => __('api.field_validation.coupon.type_field_required_msg'),
        'amount.required' => __('api.field_validation.coupon.amount_field_required_msg'),
        'end_date.required' => __('api.field_validation.coupon.end_date_field_required_msg')
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

        if(Post::where('id', $id)->delete()){
          if(PostExtra::where('post_id', $id)->delete()){
            return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'coupon')), 200);
          }
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}