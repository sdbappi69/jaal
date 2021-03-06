<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostExtra;
use App\Http\Resources\BlogCollection as BlogResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;

class BlogsController extends Controller
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
          
          
          $pages = $post->where('post_type', 'post-blog')
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
                        
          
          return response()->json(new BlogResourceCollection($pages));
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
        $image_url = '';
        $seo_title = '';
        $seo_meta_tag_description = '';
        $seo_meta_tag_keywords = '';
        $maximum_number_characters = 200;
        $allow_comments = 'no';
        $seo_slug = '';

        $post_slug = post_unique_slug('post', $request->post_title);
        $author_id = get_roles_details_by_role_slug('administrator');

        if(isset($request->description) && !empty($request->description)){
          $desc = $request->description;
        }

        if(isset($request->image_url) && !empty($request->image_url)){
          $image_url = $request->image_url;
        }

        if(isset($request->seo_title) && !empty($request->seo_title)){
          $seo_slug = post_unique_slug('post_extras', $request->seo_title);
          $seo_title = $request->seo_title;
        }

        if(isset($request->seo_meta_tag_description) && !empty($request->seo_meta_tag_description)){
          $seo_meta_tag_description = $request->seo_meta_tag_description;
        }

        if(isset($request->seo_meta_tag_keywords) && !empty($request->seo_meta_tag_keywords)){
          $seo_meta_tag_keywords = $request->seo_meta_tag_keywords;
        }

        if(isset($request->maximum_number_characters) && !empty($request->maximum_number_characters)){
          $maximum_number_characters = $request->maximum_number_characters;
        }

        if(isset($request->allow_comments) && ($request->allow_comments == true || $request->allow_comments == 1 || $request->allow_comments == "1")){
          $allow_comments = 'yes';
        }
        

        $post->post_author_id = $author_id->id;
        $post->post_content = string_encode($desc);
        $post->post_title = strip_tags($request->post_title);
        $post->post_slug = $post_slug;
        $post->parent_id = 0;
        $post->post_status = $request->status;
        $post->image_url = $image_url;
        $post->post_type = 'post-blog';

        if($post->save()){  
          if(PostExtra::insert(
            array(
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_allow_max_number_characters_at_frontend',
                        'key_value'     =>  $maximum_number_characters,
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_allow_comments_at_frontend',
                        'key_value'     =>  $allow_comments,
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_blog_seo_title',
                        'key_value'     =>  strip_tags($seo_title),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_blog_seo_url',
                        'key_value'     =>  $seo_slug,
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_blog_seo_description',
                        'key_value'     =>  strip_tags($seo_meta_tag_description),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  ),
                  array(
                        'post_id'       =>  $post->id,
                        'key_name'      =>  '_blog_seo_keywords',
                        'key_value'     =>  strip_tags($seo_meta_tag_keywords),
                        'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                        'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                  )
            )
        )){
            return response()->json(__('api.middleware.created_successfully', array('attribute' => 'blog')), 200);
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
          if(isset($get_data['post_title']) && !empty($get_data['post_title'])){
            $update_data['post_title'] = strip_tags($get_data['post_title']);
          }

          if(isset($get_data['description']) && !empty($get_data['description'])){
            $update_data['post_content'] = string_encode($get_data['description']);
          }

          if(isset($get_data['image_url']) && !empty($get_data['image_url'])){
            $update_data['image_url'] = $get_data['image_url'];
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
          Post::where('id', $id)->update($update_data);
          $is_updated = true;
        }

        if(isset($get_data['seo_title']) && !empty($get_data['seo_title'])){
          $seo_slug = post_unique_slug('post_extras', $get_data['seo_title']);

          $data_seo_title = array(
            'key_value' => strip_tags($get_data['seo_title'])
          );

          $data_seo_title_slug = array(
            'key_value' => $seo_slug
          );

          
          PostExtra::where(['post_id' => $id, 'key_name' => '_blog_seo_title'])->update($data_seo_title);
          PostExtra::where(['post_id' => $id, 'key_name' => '_blog_seo_url'])->update($data_seo_title_slug);
          $is_updated = true;
        }

        if(isset($get_data['seo_meta_tag_description']) && !empty($get_data['seo_meta_tag_description'])){
          $data_seo_description = array(
            'key_value' => strip_tags($get_data['seo_meta_tag_description'])
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_blog_seo_description'])->update($data_seo_description);
          $is_updated = true;
        }

        if(isset($get_data['seo_meta_tag_keywords']) && !empty($get_data['seo_meta_tag_keywords'])){
          $data_seo_keywords = array(
            'key_value' => strip_tags($get_data['seo_meta_tag_keywords'])
          );

          PostExtra::where(['post_id' => $id, 'key_name' => '_blog_seo_keywords'])->update($data_seo_keywords);
          $is_updated = true;
        }

        if(isset($get_data['maximum_number_characters']) && !empty($get_data['maximum_number_characters'])){
          if(array_key_exists('maximum_number_characters', $get_data)){
            $rules =  [
              'maximum_number_characters' => 'integer'
            ];

            $validator = Validator:: make($get_data, $rules);

            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
            else{
              $allow_max_number_characters = array(
                'key_value' => $get_data['maximum_number_characters']
              );

              PostExtra::where(['post_id' => $id, 'key_name' => '_allow_max_number_characters_at_frontend'])->update($allow_max_number_characters);
              $is_updated = true;
            }
          }
        }

        if(isset($get_data['allow_comments'])){
          if(array_key_exists('allow_comments', $get_data)){
            $rules =  [
              'allow_comments' => 'boolean'
            ];

            $validator = Validator:: make($get_data, $rules);

            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
            else{
              $comments = 'no';

              if(($get_data['allow_comments'] == true) || ($get_data['allow_comments'] == 1) || ($get_data['allow_comments'] == "1")){
                $comments = 'yes';
              }
            
              $allow_comments = array(
                'key_value' => $comments
              );

              PostExtra::where(['post_id' => $id, 'key_name' => '_allow_comments_at_frontend'])->update($allow_comments);
              $is_updated = true;
            }
          }
        }

        if($is_updated){
          return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'blog')), 200);
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
        'post_title' => 'required',
        'status' => 'required|integer|between:0,1'
      ];

      if(isset($request->allow_comments)){
        $rules['allow_comments'] = 'boolean';
      }

      if(isset($request->maximum_number_characters)){
        $rules['maximum_number_characters'] = 'integer';
      }
      
      $customMessages = [
        'post_title.required' => __('admin.blog.field_validation.title_field_required_msg')
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
            return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'page')), 200);
          }
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}