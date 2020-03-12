<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Term;
use App\Models\TermExtra;
use App\Http\Resources\TermCollection as TermResourceCollection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Validator;

class TermsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->isMethod('get')){

          if(!isset($request['type']) || empty($request['type']) || $request['type'] != 'product_brands'){
            return response()->json(__('api.middleware.bad_parameter'), 400);
          }

          $currentPage = 1;
          $perPage = 15;
          $search = '';
          $after = '';
          $before = '';
          $order = 'desc';
          $orderby = 'id';
          $handle = '';
          $status;
          $type = '';
          
          if(isset($request['type']) && !empty($request['type'])){
            $type = $request['type'];
          }

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
          
          $term_data = null;
          
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });

          $terms = DB::table('terms')
                   ->where('terms.type', '=', $type)
                   ->orderBy($orderby, $order);

          if(!empty($search)){
            $get_terms = $terms->where('terms.name', 'like', '%' . $search . '%');
          }
          
          if(!empty($handle)){
            $get_terms = $terms->where('terms.slug', $handle);
          }
          
          if(isset($status)){
            $get_terms = $terms->where('terms.status', $status);
          }
          
          if(!empty($after) && !empty($before)){
            $get_terms = $terms->whereBetween('terms.created_at', array($after, $before.' 23:59:59'));
          }
          elseif(!empty($after) && empty($before)){
            $get_terms = $terms->where('terms.created_at', '>=', $after.' 00:00:00');
          }
          elseif(empty($after) && !empty($before)){
            $get_terms = $terms->where('terms.created_at', '<=', $before.' 23:59:59');
          }

          $get_terms = $terms->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id');
          
          if($request['type'] == 'product_brands'){
            $get_terms = $terms->select(DB::raw('terms.term_id as id'), 'terms.name', DB::raw('terms.slug as handle'), 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_brand_country_name' THEN term_extras.key_value END) as country_name"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_short_description' THEN term_extras.key_value END) as short_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_logo_img_url' THEN term_extras.key_value END) as image_url"), DB::raw('terms.created_at as created_date'), DB::raw('terms.updated_at as updated_date'));
          }

          $term_data = $get_terms->groupBy('terms.term_id') 
                                 ->paginate($perPage);

          return response()->json(new TermResourceCollection($term_data));
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

        if($request->type != 'product_brands'){
          return response()->json(__('api.middleware.bad_parameter'), 400);
        }

        $term = new Term;
        $term_slug = post_unique_slug('term', $request->title);
        
        $term->name = $request->title;
        $term->slug = $term_slug;
        $term->type = $request->type;
        $term->parent = 0;
        $term->status = $request->status;
        

        if($term->save()){
          if($request->type == 'product_brands'){
            $country_name = '';
            $desc = '';
            $image_url = '';

            if(isset($request->country_name) && !empty($request->country_name)){
              $country_name = $request->country_name; 
            }

            if(isset($request->description) && !empty($request->description)){
              $desc = $request->description; 
            }

            if(isset($request->image_url) && !empty($request->image_url)){
              $image_url = $request->image_url; 
            }

            if(TermExtra::insert(array(
              array(
                  'term_id'       =>  $term->id,
                  'key_name'      =>  '_brand_country_name',
                  'key_value'     =>  strip_tags($country_name),
                  'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                  'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                  'term_id'       =>  $term->id,
                  'key_name'      =>  '_brand_short_description',
                  'key_value'     =>  string_encode($desc),
                  'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                  'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              ),
              array(
                  'term_id'       =>  $term->id,
                  'key_name'      =>  '_brand_logo_img_url',
                  'key_value'     =>  $image_url,
                  'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                  'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
              )
            )
            )){
              return response()->json(__('api.middleware.created_successfully', array('attribute' => 'term')), 200);
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
          if(isset($get_data['title']) && !empty($get_data['title'])){
            $update_data['name'] = strip_tags($get_data['title']);
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
        }
        
        if(is_array($update_data) && count($update_data) > 0){
          if( Term::where('term_id', $id)->update($update_data)){

            if(isset($get_data['country_name']) && !empty($get_data['country_name'])){
              $brand_country_name = array(
                'key_value' => strip_tags($get_data['country_name'])
              );

              TermExtra::where(['term_id' => $id, 'key_name' => '_brand_country_name'])->update($brand_country_name);
            }

            if(isset($get_data['description']) && !empty($get_data['description'])){
              $brand_short_description = array(
                'key_value' => string_encode($get_data['description'])
              );

              TermExtra::where(['term_id' => $id, 'key_name' => '_brand_short_description'])->update($brand_short_description);
            }

            if(isset($get_data['image_url']) && !empty($get_data['image_url'])){
              $brand_logo_img_url = array(
                'key_value' => $get_data['image_url']
              );

              TermExtra::where(['term_id' => $id, 'key_name' => '_brand_logo_img_url'])->update($brand_logo_img_url);
            }

            return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'term')), 200);
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
        'title.required' => __('admin.pages.field_validation.title_field_required_msg'),
        'type.required' => __('admin.pages.field_validation.title_field_required_msg'),
        'status.required' => __('admin.pages.field_validation.status_field_required_msg')
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

        if(Term::where('term_id', $id)->delete()){
          if(TermExtra::where('term_id', $id)->delete()){
            return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'term')), 200);
            return redirect()->route('manufacturers.index');
          }
          
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}