<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Resources\PageCollection as PageResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;

class PagesController extends Controller
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
          
          $post = new Post();
          
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });
          
          
          $pages = $post->where('post_type', 'page')
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
          
          $pages = $pages->paginate($perPage);
                        
          
          return response()->json(new PageResourceCollection($pages));
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
        $post_slug = post_unique_slug('post', $request->page_title);
        $author_id = get_roles_details_by_role_slug('administrator');

        if(isset($request->description) && !empty($request->description)){
          $desc = $request->description;
        }

        $post->post_author_id = $author_id->id;
        $post->post_content = string_encode($desc);
        $post->post_title = strip_tags($request->page_title);
        $post->post_slug = $post_slug;
        $post->parent_id = 0;
        $post->post_status = $request->status;
        $post->post_type = 'page';

        if($post->save()){  
          return response()->json(__('api.middleware.created_successfully', array('attribute' => 'page')), 200);
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
        'page_title' => 'required',
        'status' => 'required|integer|between:0,1'
      ];
      
      $customMessages = [
        'page_title.required' => __('admin.pages.field_validation.title_field_required_msg'),
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
          return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'page')), 200);
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}
