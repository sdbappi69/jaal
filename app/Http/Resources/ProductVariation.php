<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ProductVariation extends JsonResource
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
        $role_based_pricing_enable = 'enable';
        
        if($this->status == 0){
          $status = 'disable';
        }

        if($this->role_based_pricing_enable == 0){
            $role_based_pricing_enable = 'disable';
          }
        
        return [
            'variation_id' => $this->variation_id,
            'author_id' => $this->author_id, 
            'parent_id' => $this->parent_id,
            'status' => $status,  
            'img_url' => $this->img_url,
            'SKU' => $this->sku,
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'price' => $this->price,
            'sale_price_start_date' => $this->sale_price_start_date,
            'sale_price_end_date' => $this->sale_price_end_date,
            'manage_stock' => $this->manage_stock,
            'manage_stock_qty' => $this->manage_stock_qty,
            'back_to_order' => $this->back_to_order,
            'stock_availability' => $this->stock_availability,
            'enable_tax' => $this->enable_tax,
            'attributes' => json_decode($this->attributes),
            'role_based_pricing_enable' => $role_based_pricing_enable,
            'role_pricing' => unserialize($this->role_pricing),
            'downloadable_product_data' => $this->enable_tax,
            'downloadable_limit' => $this->downloadable_limit,
            'download_expiry' => $this->download_expiry,
            'type' => $this->type,
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s')
        ];
    }
}
