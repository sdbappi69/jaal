<?php

namespace shopist\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Testimonial extends JsonResource
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
        
        if($this->post_status == 0){
          $status = 'disable';
        }
        
        return [
            'id' => $this->id,
            'author' => $this->post_author_id, 
            'content' => $this->post_content,
            'title' => $this->post_title,
            'handle' => $this->post_slug,
            'parent_id' => $this->parent_id,  
            'status' => $status,  
            'image_url' => $this->image_url,  
            'type' => $this->post_type,
            'client_name' => $this->client_name,
            'job_title' => $this->job_title,
            'company_name' => $this->company_name,
            'client_url' => $this->url,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s'), 
        ];
    }
}
