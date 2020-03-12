<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Review extends JsonResource
{
    public $preserveKeys = true;
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $createdAt = Carbon::parse($this->created_at);
        $modifiedAt = Carbon::parse($this->updated_at);
        $status = 'enable';
        
        if($this->status == 0){
          $status = 'disable';
        }
        
        $get_user = get_user_details( $this->user_id );
        return [
            'id' => $this->id,
            'reviewer' => $get_user['user_display_name'],
            'reviewer_role' => $get_user['user_role'],  
            'reviewer_email' => $get_user['user_email'],
            'reviewer_avatar_url' => $get_user['user_photo_url'],
            'reviewer_id' => $this->user_id,
            'review' => $this->content,  
            'rating' => $this->rating, 
            'title' => get_product_title( $this->object_id ), 
            'object_id' => $this->object_id,  
            'status' => $status,
            'type' => $this->target,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s')
        ];
    }
}
