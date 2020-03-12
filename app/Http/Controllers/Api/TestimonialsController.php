<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostExtra;
use App\Http\Resources\TestimonialCollection as TestimonialResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;
use Illuminate\Support\Facades\DB;

class TestimonialsController extends Controller
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
           
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });
          
          
          $testimonials = DB::table('posts')
                        ->where('posts.post_type', 'testimonial')
                        ->orderBy($orderby, $order);

          if(!empty($search)){
            $testimonials->where('posts.post_title', 'like', '%' . $search . '%');
          }
          
          if(!empty($handle)){
            $testimonials->where('posts.post_slug', $handle);
          }
          
          if(isset($status)){
            $testimonials->where('posts.post_status', $status);
          }
          
          if(!empty($after) && !empty($before)){
            $testimonials->whereBetween('posts.created_at', array($after, $before.' 23:59:59'));
          }
          elseif(!empty($after) && empty($before)){
            $testimonials->where('posts.created_at', '>=', $after.' 00:00:00');
          }
          elseif(empty($after) && !empty($before)){
            $testimonials->where('posts.created_at', '<=', $before.' 23:59:59');
          }

          $testimonials->join('post_extras', 'posts.id', '=', 'post_extras.post_id');
          $testimonials->select( 'posts.*', DB::raw("max(CASE WHEN post_extras.key_name = '_testimonial_client_name' THEN post_extras.key_value END) as client_name"), DB::raw("max(CASE WHEN post_extras.key_name = '_testimonial_job_title' THEN post_extras.key_value END) as job_title"), DB::raw("max(CASE WHEN post_extras.key_name = '_testimonial_company_name' THEN post_extras.key_value END) as company_name"), DB::raw("max(CASE WHEN post_extras.key_name = '_testimonial_url' THEN post_extras.key_value END) as url"));
          $testimonials->groupBy('posts.id');
          
          $get_testimonials = $testimonials->paginate($perPage);     
          
          
          return response()->json(new TestimonialResourceCollection($get_testimonials));
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
        $image = '';
        $client_name = '';
        $job_title = '';
        $company_name = '';
        $client_url = '';

        $post_slug = post_unique_slug('post', $request->title);
        $author_id = get_roles_details_by_role_slug('administrator');

        if(isset($request->description) && !empty($request->description)){
          $desc = $request->description;
        }

        if(isset($request->image) && !empty($request->image)){
          $image = $request->image;
        }

        if(isset($request->client_name) && !empty($request->client_name)){
          $client_name = $request->client_name;
        }

        if(isset($request->job_title) && !empty($request->job_title)){
          $job_title = $request->job_title;
        }

        if(isset($request->company_name) && !empty($request->company_name)){
          $company_name = $request->company_name;
        }

        if(isset($request->client_url) && !empty($request->client_url)){
          $client_url = $request->client_url;
        }

        $post->post_author_id = $author_id->id;
        $post->post_content = string_encode($desc);
        $post->post_title = strip_tags($request->title);
        $post->post_slug = $post_slug;
        $post->parent_id = 0;
        $post->post_status = $request->status;
        $post->image_url = $request->image;
        $post->post_type = 'testimonial';

        if($post->save()){
          if(PostExtra::insert(
            array(
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_testimonial_client_name',
                        'key_value'     =>  strip_tags($client_name),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_testimonial_job_title',
                        'key_value'     =>  strip_tags($job_title),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_testimonial_company_name',
                        'key_value'     =>  strip_tags($company_name),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_testimonial_url',
                        'key_value'     =>  strip_tags($client_url),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  )
            )
        )){
            return response()->json(__('api.middleware.created_successfully', array('attribute' => 'testimonial')), 200);
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
            $update_data['post_title'] = strip_tags($get_data['title']);
          }

          if(isset($get_data['description']) && !empty($get_data['description'])){
            $update_data['post_content'] = string_encode($get_data['description']);
          }

          if(isset($get_data['image']) && !empty($get_data['image'])){
            $update_data['image_url'] = $get_data['image'];
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

        if(isset($get_data['client_name']) && !empty($get_data['client_name'])){
          $data_client_name = array(
            'key_value' => strip_tags($get_data['client_name'])
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_testimonial_client_name'])->update($data_client_name);
          $is_updated = true;
        }

        if(isset($get_data['job_title']) && !empty($get_data['job_title'])){
          $data_job_title = array(
            'key_value' => strip_tags($get_data['job_title'])
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_testimonial_job_title'])->update($data_job_title);
          $is_updated = true;
        }

        if(isset($get_data['company_name']) && !empty($get_data['company_name'])){
          $data_company_name = array(
            'key_value' => strip_tags($get_data['company_name'])
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_testimonial_company_name'])->update($data_company_name);
          $is_updated = true;
        }

        if(isset($get_data['client_url']) && !empty($get_data['client_url'])){
          $data_client_url = array(
            'key_value' => strip_tags($get_data['client_url'])
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_testimonial_url'])->update($data_client_url);
          $is_updated = true;
        }


        if($is_updated){
          return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'testimonial')), 200);
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
        'status' => 'required|integer|between:0,1'
      ];
      
      $customMessages = [
        'title.required' => __('admin.pages.field_validation.title_field_required_msg'),
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

        if(Post::where('id', $id)->delete()){
          if(PostExtra::where('post_id', $id)->delete()){
            return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'testimonial')), 200);
          }
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}