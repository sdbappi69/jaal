<?php

namespace shopist\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Term extends JsonResource
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
        
        $data_array = array();
        $data_array = array (
            'id' => $this->id,
            'name' => $this->name, 
            'handle' => $this->handle,
            'type' => $this->type,
            'parent' => $this->parent,
            'status' => $status
        );

        if($this->type == 'product_brands'){
            $data_array['country_name'] = $this->country_name; 
            $data_array['short_description'] = $this->short_description;
            $data_array['image_url'] = $this->image_url;
        }

        $data_array['date_created'] = $createdAt->format('Y-m-d:h:i:s'); 
        $data_array['date_modified'] = $modifiedAt->format('Y-m-d:h:i:s');

        return $data_array;  
    }
}
