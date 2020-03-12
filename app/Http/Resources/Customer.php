<?php

namespace APP\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Customer extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'profile_img' => $this->profile_img,
            'status' => $status,  
            'profile_details' => $this->profile_details,
            'access_list' => $this->access_list,
            'role_id' => $this->role_id,
            'role_handle' => $this->role_handle,
            'role_name' => $this->role_name,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s')
        ];
    }
}