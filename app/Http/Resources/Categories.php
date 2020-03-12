<?php

namespace shopist\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Categories extends JsonResource
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
        $createdAt = Carbon::parse($this->created_date);
        $modifiedAt = Carbon::parse($this->updated_date);
        $status = 'enable';
        
        if($this->status == 0){
          $status = 'disable';
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name, 
            'handle' => $this->handle,
            'type' => $this->type,
            'parent' => $this->parent,
            'status' => $status,  
            'description' => $this->description,
            'img_url' => $this->img_url,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s')
        ];
    }
}
