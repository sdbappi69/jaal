<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Request;
use Illuminate\Support\Facades\Lang;
use Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Input;
use App\Models\Comment;

class UserCommentsController extends Controller
{
  public function saveUserComments(){
    if( Request::isMethod('post') && Session::token() == Request::Input('_token') ){
      
      if(Session::has('shopist_frontend_user_id')){
        $input = Request::all();
        
        $rules =  [
                    'selected_rating_value'   => 'required',
                    'product_review_content'  => 'required',
        ];
        
        $messages = [
                    'selected_rating_value.required'  => Lang::get('validation.select_rating'),
                    'product_review_content.required' => Lang::get('validation.write_review')
        ];

        $validator = Validator:: make($input, $rules, $messages);
        
        if($validator->fails()){
          return redirect()-> back()
          ->withInput()
          ->withErrors( $validator );
        }
        else{
          $comments   =  new Comment;
          
          $comments->user_id     =    Session::get('shopist_frontend_user_id');
          $comments->content     =    Request::Input('product_review_content');
          $comments->rating      =    Request::Input('selected_rating_value');
          $comments->object_id   =    Request::Input('object_id');
          $comments->target      =    Request::Input('comments_target');
          $comments->status      =    0;
          
          if($comments->save()){
            Session::flash('success-message', Lang::get('frontend.comments_saved_msg') );
            return redirect()-> back()
                             ->withInput(); 
          }
        }
      }
      else{
        Session::flash('error-message', Lang::get('frontend.without_login_comments_msg') );
        return redirect()-> back()
                         ->withInput();
      }
    }
  }
}