<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Page extends JsonResource
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
            'status' => $status,  
            'type' => $this->post_type,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s')
        ];
    }
}
