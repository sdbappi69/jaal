<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Resources\ProductCollection as ProductResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;

class ProductsController extends Controller
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
          $exclude = '';
          $include = '';
          $type = '';
          $sku = '';
          $min_price = '';
          $max_price = '';
          $stock_status = '';
          
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

          if(isset($request['exclude']) && !empty($request['exclude'])){
            $exclude = $request['exclude'];
          }

          if(isset($request['include']) && !empty($request['include'])){
            $include = $request['include'];
          }

          if(isset($request['type']) && !empty($request['type'])){
            $type = $request['type'];
          }

          if(isset($request['sku']) && !empty($request['sku'])){
            $sku = $request['sku'];
          }

          if(isset($request['min_price']) && !empty($request['min_price'])){
            $min_price = $request['min_price'];
          }

          if(isset($request['max_price']) && !empty($request['max_price'])){
            $max_price = $request['max_price'];
          }

          if(isset($request['stock_status']) && !empty($request['stock_status'])){
            $stock_status = $request['stock_status'];
          }

          //check is array
          if(!empty($exclude)){
            if(!is_array($exclude)){
              return response()->json(__('api.middleware.bad_parameter'), 400);
            }
          }

          if(!empty($include)){
            if(!is_array($include)){
              return response()->json(__('api.middleware.bad_parameter'), 400);
            }
          }
          
          $product = new Product();
          
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });
          
          
          $products = $product->orderBy($orderby, $order);
          
          if(!empty($search)){
            $products->where('title', 'like', '%' . $search . '%');
          }
          
          if(!empty($handle)){
            $products->where('slug', $handle);
          }
          
          if(isset($status)){
            $products->where('status', $status);
          }
          
          if(!empty($after) && !empty($before)){
            $products->whereBetween('created_at', array($after, $before.' 23:59:59'));
          }
          elseif(!empty($after) && empty($before)){
            $products->where('created_at', '>=', $after.' 00:00:00');
          }
          elseif(empty($after) && !empty($before)){
            $products->where('created_at', '<=', $before.' 23:59:59');
          }

          if(!empty($exclude)){
            $products->whereNotIn('id', $exclude);
          }

          if(!empty($include)){
            $products->whereIn('id', $include);
          }

          if(!empty($type)){
            $products->where('type', $type);
          }

          if(!empty($sku)){
            $products->where('sku', $sku);
          }

          if(!empty($min_price) && !empty($max_price)){
            $products->whereBetween('price', array($min_price, $max_price));
          }
          elseif(!empty($min_price) && empty($max_price)){
            $products->where('price', '>=', $min_price);
          }
          elseif(empty($min_price) && !empty($max_price)){
            $products->where('price', '<=', $max_price);
          }

          if(!empty($stock_status)){
            $products->where('stock_availability', $stock_status);
          }

          $products = $products->paginate($perPage);
          return response()->json(new ProductResourceCollection($products));
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

        $product = new Product;
        $regular_price = '';
        $description = '';
        $image_url = '';
        $sku = '';

        $product_slug = create_unique_slug('product', $request->title);
        $author_id = get_roles_details_by_role_slug('administrator');

        if(isset($request->regular_price) && !empty($request->regular_price)){
          $regular_price = $request->regular_price;
        }

        if(isset($request->description) && !empty($request->description)){
          $description = $request->description;
        }

        if(isset($request->image_url) && !empty($request->image_url)){
          $image_url = $request->image_url;
        }

        if(isset($request->sku) && !empty($request->sku)){
          $sku = $request->sku;
        }

        $product->author_id = $author_id->id;
        $product->content = string_encode($description);
        $product->title = strip_tags($request->title);
        $product->slug = $product_slug;
        $product->status = $request->status;
        $product->sku = $sku;
        $product->regular_price = $regular_price;
        $product->sale_price = '';
        $product->price = $regular_price;
        $product->stock_qty = '';
        $product->stock_availability = 'in_stock';
        $product->type = $request->type;
        $product->image_url = $image_url;

        if($product->save()){  
          return response()->json(__('api.middleware.created_successfully', array('attribute' => 'product')), 200);
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
          if(isset($get_data['title']) && !empty($get_data['title'])){
            $update_data['title'] = strip_tags($get_data['title']);
          }

          if(isset($get_data['description']) && !empty($get_data['description'])){
            $update_data['content'] = string_encode($get_data['description']);
          }

          if(isset($get_data['type']) && !empty($get_data['type'])){
            $update_data['type'] = $get_data['type'];
          }

          if(isset($get_data['sku']) && !empty($get_data['sku'])){
            $update_data['sku'] = $get_data['sku'];
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
              $update_data['status'] = $get_data['status'];
            }
          }

          if(isset($get_data['regular_price']) && !empty($get_data['regular_price'])){
            $update_data['regular_price'] = $get_data['regular_price'];
            $update_data['price'] = $get_data['regular_price'];
          }

          if(isset($get_data['image_url']) && !empty($get_data['image_url'])){
            $update_data['image_url'] = $get_data['image_url'];
          }
        }
        
        if(is_array($update_data) && count($update_data) > 0){
          if( Product::where('id', $id)->update($update_data)){
            return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'product')), 200);
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
        'title' => 'required',
        'type' => 'required',
        'status' => 'required|integer|between:0,1'
      ];

      $customMessages = [
        'title.required' => __('api.field_validation.products.title_field_required_msg'),
        'status.required' => __('api.field_validation.products.status_field_required_msg'),
        'type.required' => __('api.field_validation.products.type_field_required_msg')

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

        if(Product::where('id', $id)->delete()){
          return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'product')), 200);
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}