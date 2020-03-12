<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\OrderCollection as OrderResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;
use App\Models\Post;
use App\Models\PostExtra;
use App\Models\Product;
use App\Models\OrdersItem;
use App\Models\OrderProduct;
use App\Library\CommonFunction;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
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
        $after = '';
        $before = '';
        $orderType = 'desc';
        $orderby = 'id';
        $status = '';
        $customer;
        $product;
        
        if(isset($request['page']) && !empty($request['page'])){
          $currentPage = $request['page'];
        }
        
        if(isset($request['per_page']) && !empty($request['per_page'])){
          $perPage = $request['per_page'];
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
        
        if(isset($request['status']) && !empty($request['status'])){
          $status = $request['status'];
        }
        
        if(isset($request['customer'])){
          $customer = $request['customer'];
        }

        if(isset($request['product'])){
          $product = $request['product'];
        }
        
        
        Paginator::currentPageResolver(function () use ($currentPage) {
          return $currentPage;
        });
        
        
        $order = DB::table('posts')->where(['posts.post_type' => 'shop_order']);

        if(!empty($after) && !empty($before)){
          $order->whereBetween('posts.created_at', array($after, $before.' 23:59:59'));
        }
        elseif(!empty($after) && empty($before)){
          $order->where('posts.created_at', '>=', $after.' 00:00:00');
        }
        elseif(empty($after) && !empty($before)){
          $order->where('posts.created_at', '<=', $before.' 23:59:59');
        }

        if(isset($customer)){
          $order->where('posts.post_author_id', $customer);
        }

        if(isset($product)){
          $order->where('order_products.product_id', $product);
          $order->join('order_products', 'posts.id', '=', 'order_products.order_id');
        }

        $order->join('orders_items', 'posts.id', '=', 'orders_items.order_id');

        $table1 = $order->select('posts.id', DB::raw('posts.post_author_id as customer'), DB::raw('posts.created_at as created_date'), DB::raw('posts.updated_at as updated_date'), DB::raw('orders_items.order_data as items'));

        $orders = DB::table('post_extras');

        if(!empty($status)){
          $orders->where('key_name', '_order_status');
          $orders->where('key_value', $status);
        }

        $orders->joinSub($table1, 'table1', function ($join) {
                      $join->on('post_extras.post_id', '=', 'table1.id');
                  })->select('table1.id', 'table1.customer', 'table1.created_date', 'table1.updated_date', 'table1.items', DB::raw("max(CASE WHEN post_extras.key_name = '_order_currency' THEN post_extras.key_value END) as currency"), DB::raw("max(CASE WHEN post_extras.key_name = '_customer_ip_address' THEN post_extras.key_value END) as customer_ip_address"), DB::raw("max(CASE WHEN post_extras.key_name = '_final_order_shipping_cost' THEN post_extras.key_value END) as shipping_cost"), DB::raw("max(CASE WHEN post_extras.key_name = '_order_shipping_method' THEN post_extras.key_value END) as shipping_method"), DB::raw("max(CASE WHEN post_extras.key_name = '_payment_method' THEN post_extras.key_value END) as payment_method"), DB::raw("max(CASE WHEN post_extras.key_name = '_payment_method_title' THEN post_extras.key_value END) as payment_method_title"), DB::raw("max(CASE WHEN post_extras.key_name = '_final_order_tax' THEN post_extras.key_value END) as tax"), DB::raw("max(CASE WHEN post_extras.key_name = '_final_order_total' THEN post_extras.key_value END) as total"), DB::raw("max(CASE WHEN post_extras.key_name = '_order_notes' THEN post_extras.key_value END) as note"), DB::raw("max(CASE WHEN post_extras.key_name = '_order_status' THEN post_extras.key_value END) as status"), DB::raw("max(CASE WHEN post_extras.key_name = '_final_order_discount' THEN post_extras.key_value END) as discount"), DB::raw("max(CASE WHEN post_extras.key_name = '_order_coupon_code' THEN post_extras.key_value END) as coupon_code"), DB::raw("max(CASE WHEN post_extras.key_name = '_is_order_coupon_applyed' THEN post_extras.key_value END) as is_order_coupon_applyed"), DB::raw("max(CASE WHEN post_extras.key_name = '_order_process_key' THEN post_extras.key_value END) as process_key"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_title' THEN post_extras.key_value END) as billing_title"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_first_name' THEN post_extras.key_value END) as billing_first_name"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_last_name' THEN post_extras.key_value END) as billing_last_name"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_company' THEN post_extras.key_value END) as billing_company"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_email' THEN post_extras.key_value END) as billing_email"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_phone' THEN post_extras.key_value END) as billing_phone"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_fax' THEN post_extras.key_value END) as billing_fax"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_country' THEN post_extras.key_value END) as billing_country"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_address_1' THEN post_extras.key_value END) as billing_address_1"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_address_2' THEN post_extras.key_value END) as billing_address_2"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_city' THEN post_extras.key_value END) as billing_city"), DB::raw("max(CASE WHEN post_extras.key_name = '_billing_postcode' THEN post_extras.key_value END) as billing_postcode"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_title' THEN post_extras.key_value END) as shipping_title"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_first_name' THEN post_extras.key_value END) as shipping_first_name"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_last_name' THEN post_extras.key_value END) as shipping_last_name"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_company' THEN post_extras.key_value END) as shipping_company"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_email' THEN post_extras.key_value END) as shipping_email"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_phone' THEN post_extras.key_value END) as shipping_phone"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_fax' THEN post_extras.key_value END) as shipping_fax"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_country' THEN post_extras.key_value END) as shipping_country"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_address_1' THEN post_extras.key_value END) as shipping_address_1"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_address_2' THEN post_extras.key_value END) as shipping_address_2"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_city' THEN post_extras.key_value END) as shipping_city"), DB::raw("max(CASE WHEN post_extras.key_name = '_shipping_postcode' THEN post_extras.key_value END) as shipping_postcode"))->groupBy('post_extras.post_id')->orderBy($orderby, $orderType);


        $orders = $orders->paginate($perPage);
        return response()->json(new OrderResourceCollection($orders));
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
       
        if(array_key_exists('billing', $request->all())){
          if( (!array_key_exists('first_name', $request->billing)) || (!array_key_exists('last_name', $request->billing)) || (!array_key_exists('email', $request->billing)) || (!array_key_exists('phone_number', $request->billing)) ||  (!array_key_exists('country', $request->billing)) || (!array_key_exists('address_1', $request->billing)) || (!array_key_exists('city', $request->billing)) || (!array_key_exists('postcode', $request->billing))){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
        }

        if(array_key_exists('shipping', $request->all())){
          if( (!array_key_exists('first_name', $request->shipping)) || (!array_key_exists('last_name', $request->shipping)) || (!array_key_exists('email', $request->shipping)) || (!array_key_exists('phone_number', $request->shipping)) ||  (!array_key_exists('country', $request->shipping)) || (!array_key_exists('address_1', $request->shipping)) || (!array_key_exists('city', $request->shipping)) || (!array_key_exists('postcode', $request->shipping))){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
        }
        
        //billing check for customer
        if(array_key_exists('billing', $request->all())){
          $rules =  [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'country' => 'required',
            'address_1' => 'required',
            'city' => 'required',
            'postcode' => 'required'
          ];
          
          $validator = Validator:: make($request->billing, $rules);

          if($validator->fails()){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
        }
        
        //billing check for customer
        if(array_key_exists('shipping', $request->all())){
          $rules =  [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'country' => 'required',
            'address_1' => 'required',
            'city' => 'required',
            'postcode' => 'required'
          ];
          
          $validator = Validator:: make($request->shipping, $rules);

          if($validator->fails()){
            return response()->json(__('api.middleware.bad_parameter'), 400);  
          }
        }
        
        if(is_array($request->line_items) && count($request->line_items) > 0){
          foreach($request->line_items as $item){
            $rules =  [
              'product_id' => 'required|integer',
              'name' => 'required',
              'quantity' => 'required|integer',
              'price' => 'required|numeric',
              'img_src' => 'required',
              'product_type' => 'required'
            ];

            if(isset($item['variation_id'])){
              $rules['variation_id'] = 'required|integer';
              $rules['options'] = 'required|array';
            }
            
            $validator = Validator:: make($item, $rules);
            
            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
          }
        }
       

        $post = new Post;
        $customer_id;
        $customer_ip_address = '';
        $customer_user_agent = '';
        $shipping_cost = 0;
        $shipping_method = '';
        $tax = 0;
        $notes = '';
        $discount = 0;
        $coupon_code = '';
        $is_coupon_applyed = false;
        $billing_title = '';
        $billing_comapny = '';
        $billing_address_line_2 = '';
        $billing_fax_number = '';
        $shipping_title = '';
        $shipping_comapny = '';
        $shipping_address_line_2 = '';
        $shipping_fax_number = '';
        $line_items = array();
        
        $author_id = get_roles_details_by_role_slug('administrator');
        $customer_id = $author_id->id;

        if(isset($request->customer_id)){
          $customer_id = $request->customer_id;
        }

        if(isset($request->customer_ip_address) && !empty($request->customer_ip_address)){
          $customer_ip_address = $request->customer_ip_address;
        }

        if(isset($request->customer_user_agent) && !empty($request->customer_user_agent)){
          $customer_user_agent = $request->customer_user_agent;
        }

        if(isset($request->shipping_cost)){
          $shipping_cost = $request->shipping_cost;
        }

        if(isset($request->shipping_method) && !empty($request->shipping_method)){
          $shipping_method = $request->shipping_method;
        }

        if(isset($request->tax) && !empty($request->tax)){
          $tax = $request->tax;
        }

        if(isset($request->notes) && !empty($request->notes)){
          $notes = $request->notes;
        }

        if(isset($request->discount)){
          $discount = $request->discount;
        }

        if(isset($request->coupon_code) && !empty($request->coupon_code)){
          $coupon_code = $request->coupon_code;
        }

        if($request->is_coupon_applyed){
          $is_coupon_applyed = $request->is_coupon_applyed;
        }

        if(isset($request->billing['title']) && !empty($request->billing['title'])){
          $billing_title = $request->billing['title'];
        }

        if(isset($request->billing['company']) && !empty($request->billing['company'])){
          $billing_comapny = $request->billing['company'];
        }

        if(isset($request->billing['address_2']) && !empty($request->billing['address_2'])){
          $billing_address_line_2 = $request->billing['address_2'];
        }

        if(isset($request->billing['fax']) && !empty($request->billing['fax'])){
          $billing_fax_number = $request->billing['fax'];
        }

        if(isset($request->shipping['title']) && !empty($request->shipping['title'])){
          $shipping_title = $request->shipping['title'];
        }

        if(isset($request->shipping['company']) && !empty($request->shipping['company'])){
          $shipping_comapny = $request->shipping['company'];
        }

        if(isset($request->shipping['address_2']) && !empty($request->shipping['address_2'])){
          $shipping_address_line_2 = $request->shipping['address_2'];
        }

        if(isset($request->shipping['fax']) && !empty($request->shipping['fax'])){
          $shipping_fax_number = $request->shipping['fax'];
        }

        if(isset($request->shipping['fax']) && !empty($request->shipping['fax'])){
          $shipping_fax_number = $request->shipping['fax'];
        }

        if(is_array($request->line_items)){
          $line_items = $request->line_items;
        }


        $post->post_author_id = $customer_id;
        $post->post_content = 'Customer Shop Order';
        $post->post_title = 'shop order';
        $post->post_slug = 'shop-order';  
        $post->parent_id = 0;
        $post->post_status = 1;
        $post->post_type = 'shop_order';

        if($post->save()){
          $order_array = array(
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_currency',
                    'key_value'     =>  $request->currency,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_customer_ip_address',
                    'key_value'     =>  $customer_ip_address,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_customer_user_agent',
                    'key_value'     =>  $customer_user_agent,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_customer_user',
                    'key_value'     =>  '',
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_shipping_cost',
                    'key_value'     =>  $shipping_cost,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_final_order_shipping_cost',
                    'key_value'     =>  get_product_price_html_by_filter($shipping_cost),
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),      
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_shipping_method',
                    'key_value'     =>  $shipping_method,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_payment_method',
                    'key_value'     =>  $request->payment_method,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_payment_method_title',
                    'key_value'     =>  $request->payment_method_title,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_tax',
                    'key_value'     =>  $tax,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_final_order_tax',
                    'key_value'     =>  get_product_price_html_by_filter($tax),
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),      
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_total',
                    'key_value'     =>  $request->total,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_final_order_total',
                    'key_value'     =>  get_product_price_html_by_filter($request->total),
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),      
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_notes',
                    'key_value'     =>  $notes,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
             array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_status',
                    'key_value'     =>  'on-hold',
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_discount',
                    'key_value'     =>  $discount,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_final_order_discount',
                    'key_value'     =>  get_product_price_html_by_filter($discount),
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),      
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_coupon_code',
                    'key_value'     =>  $coupon_code,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_is_order_coupon_applyed',
                    'key_value'     =>  $is_coupon_applyed,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_order_process_key',
                    'key_value'     =>  time().mt_rand().rand(),
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  )    
          );  

          $guest_address_array = array( 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_title',
                    'key_value'     =>  $billing_title,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_first_name',
                    'key_value'     =>  $request->first_name,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_last_name',
                    'key_value'     =>  $request->last_name,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_company',
                    'key_value'     =>  $billing_comapny,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),  
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_email',
                    'key_value'     =>  $request->email,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_phone',
                    'key_value'     =>  $request->phone_number,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_fax',
                    'key_value'     =>  $billing_fax_number,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),       
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_country',
                    'key_value'     =>  $request->country,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_address_1',
                    'key_value'     =>  $request->address_1,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_address_2',
                    'key_value'     =>  $billing_address_line_2,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_city',
                    'key_value'     =>  $request->city,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_billing_postcode',
                    'key_value'     =>   $request->postcode,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_title',
                    'key_value'     =>  $shipping_title,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_first_name',
                    'key_value'     =>  $request->first_name,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_last_name',
                    'key_value'     =>  $request->last_name,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_company',
                    'key_value'     =>  $shipping_comapny,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),  
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_email',
                    'key_value'     =>  $request->email,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_phone',
                    'key_value'     =>  $request->phone_number,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_fax',
                    'key_value'     =>  $shipping_fax_number,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),       
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_country',
                    'key_value'     =>  $request->country,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_address_1',
                    'key_value'     =>  $request->address_1,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_address_2',
                    'key_value'     =>  $shipping_address_line_2,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ), 
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_city',
                    'key_value'     =>   $request->city,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
            array(
                    'post_id'       =>  $post->id,
                    'key_name'      =>  '_shipping_postcode',
                    'key_value'     =>  $request->postcode,
                    'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                    'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),        
        ); 
        $order_post_meta_data = array_merge($order_array, $guest_address_array);
        if(PostExtra::insert($order_post_meta_data)){
          $orderItems = new OrdersItem;

          $orderItems->order_id = $post->id;
          $orderItems->order_data = json_encode( $line_items );

          if($orderItems->save()){
            $common_function = new CommonFunction();

            if(is_array($line_items) && count($line_items) > 0){
              foreach($line_items as $cart_items){
                $cart_items = (object)$cart_items;
                
                if(isset($cart_items->variation_id) && count($cart_items->options) > 0){
                  $variation_product_data = $common_function->get_variation_and_data_by_post_id( $cart_items->variation_id );
                  
                  if($variation_product_data['_variation_post_manage_stock'] == 1){
                    $current_qty = $variation_product_data['_variation_post_manage_stock_qty'] - $cart_items->quantity;
                    $new_manage_qty = array(
                                    'key_value'    =>  $current_qty
                    );
  
                    PostExtra::where(['post_id' => $cart_items->variation_id, 'key_name' => '_variation_post_manage_stock_qty'])->update($new_manage_qty);
                  }
                }
                else{
                  $product_data = $common_function->get_product_data_by_product_id( $cart_items->product_id );
                  
                  if($product_data['product_manage_stock'] == 'yes'){
                    $current_qty = $product_data['product_manage_stock_qty'] - $cart_items->quantity;
                    
                    $new_manage_qty = array(
                                      'stock_qty' =>  $current_qty
                    );
  
                    Product::where(['id' => $cart_items->product_id])->update($new_manage_qty);
                  }
                }

                OrderProduct::insert(array(
                  'order_id' => $post->id,
                  'product_id' => $cart_items->product_id,
                  'created_at' => date("y-m-d H:i:s", strtotime('now')),
                  'updated_at' => date("y-m-d H:i:s", strtotime('now'))
                ));
              }
            }

            return response()->json(__('api.middleware.created_successfully', array('attribute' => 'order')), 200);
          }
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
          if(isset($get_data['page_title']) && !empty($get_data['page_title'])){
            $update_data['post_title'] = strip_tags($get_data['page_title']);
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
        
        if(is_array($update_data) && count($update_data) > 0){
          if( Post::where('id', $id)->update($update_data)){
            return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'page')), 200);
          }
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
        'currency' => 'required',
        'payment_method' => 'required',
        'payment_method_title' => 'required',
        'total' => 'required|numeric',
        'billing' => 'required|array',
        'shipping' => 'required|array',
        'line_items' => 'required|array'
      ];
      
      return Validator::make($request->all(), $rules);
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
          return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'page')), 200);
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}
