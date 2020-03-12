<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Product extends JsonResource
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
        
        return [
            'id' => $this->id,
            'author' => $this->author_id, 
            'content' => $this->content,
            'title' => $this->title,
            'handle' => $this->slug,
            'status' => $status,
            'sku' =>$this->sku,
            'regular_price' =>$this->regular_price,
            'sale_price' =>$this->sale_price,
            'price' =>$this->price,
            'stock_qty' =>$this->stock_qty,
            'stock_availability' =>$this->stock_availability,
            'type' => $this->type,
            'image_url' => $this->image_url,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s')
        ];
    }
}
