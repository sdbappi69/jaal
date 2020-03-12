<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Http\Resources\ReviewCollection as ReviewResourceCollection;
use Illuminate\Pagination\Paginator;
use Validator;
use Illuminate\Validation\Rule;

class ReviewsController extends Controller
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
          $order = 'desc';
          $orderby = 'id';
          $status;
          $reviewer_id;
          $product_id;
          $target = '';
          
          if(!isset($request['target']) && empty($request['target'])){
            return response()->json(__('api.middleware.bad_parameter'), 400);
          }

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
          
          if(isset($request['status'])){
            $status = $request['status'];
          }

          if(isset($request['reviewer_id'])){
            $reviewer_id = $request['reviewer_id'];
          }

          if(isset($request['product_id'])){
            $product_id = $request['product_id'];
          }

          if(isset($request['target']) && !empty($request['target'])){
            $target = $request['target'];
          }
          
          
          $comment = new Comment();
          
          Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
          });
          
          
          $comments = $comment->where('target', $target)
                      ->orderBy($orderby, $order);
          
          if(!empty($after) && !empty($before)){
            $pages->whereBetween('created_at', array($after, $before.' 23:59:59'));
          }
          elseif(!empty($after) && empty($before)){
            $pages->where('created_at', '>=', $after.' 00:00:00');
          }
          elseif(empty($after) && !empty($before)){
            $pages->where('created_at', '<=', $before.' 23:59:59');
          }

          if(isset($status)){
            $comments->where('status', $status);
          }

          if(isset($reviewer_id)){
            $comments->where('user_id', $reviewer_id);
          }

          if(isset($product_id)){
            $comments->where('object_id', $product_id);
          }
          
          $comments = $comments->paginate($perPage);

          return response()->json(new ReviewResourceCollection($comments));
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

        $comment = new Comment;
  
        $comment->user_id = $request->reviewer_id;
        $comment->content = $request->review;
        $comment->rating = $request->rating;
        $comment->object_id = $request->object_id;
        $comment->target = $request->target;
        $comment->status = 0;

        if($comment->save()){  
          return response()->json(__('api.middleware.created_successfully', array('attribute' => 'review')), 200);
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
          if(array_key_exists('object_id', $get_data)){
            $rules =  [
              'object_id' => 'integer'
            ];

            $validator = Validator:: make($get_data, $rules);

            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
            else{
              $update_data['object_id'] = $get_data['object_id'];
            }
          }

          if(array_key_exists('reviewer_id', $get_data)){
            $rules =  [
              'reviewer_id' => 'integer'
            ];

            $validator = Validator:: make($get_data, $rules);

            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
            else{
              $update_data['user_id'] = $get_data['reviewer_id'];
            }
          }

          if(isset($get_data['review']) && !empty($get_data['review'])){
            $update_data['content'] = $get_data['review'];
          }

          if(array_key_exists('rating', $get_data)){
            $rules =  [
              'rating' => 'integer|between:1,5'
            ];

            $validator = Validator:: make($get_data, $rules);

            if($validator->fails()){
              return response()->json(__('api.middleware.bad_parameter'), 400);  
            }
            else{
              $update_data['rating'] = $get_data['rating'];
            }
          }
        }
        
        if(is_array($update_data) && count($update_data) > 0){
          if( Comment::where('id', $id)->update($update_data)){
            return response()->json(__('api.middleware.updated_successfully', array('attribute' => 'comment')), 200);
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
        'object_id' => 'required|integer',
        'reviewer_id' => 'required|integer',
        'review' => 'required',
        'rating' => 'required|integer|between:1,5',
        'target' => [
          'required',
          Rule::in(['product', 'blog'])
        ]
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

        if(Comment::where('id', $id)->delete()){
          return response()->json(__('api.middleware.delete_successfully', array('attribute' => 'review')), 200);
        }

        return response()->json(__('api.middleware.bad_parameter'), 400);
      }

      return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }
}