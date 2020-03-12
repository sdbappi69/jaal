<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class Order extends JsonResource
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
        
        return [
            'id' => $this->id,
            'customer' => $this->customer, 
            'date_created' => $createdAt->format('Y-m-d:h:i:s'),
            'date_modified' => $modifiedAt->format('Y-m-d:h:i:s'),
            'currency' => $this->currency, 
            'customer_ip_address' => $this->customer_ip_address,
            'shipping_cost' => $this->shipping_cost, 
            'shipping_method' => $this->shipping_method, 
            'payment_method' => $this->payment_method, 
            'payment_method_title' => $this->payment_method_title, 
            'tax' => $this->tax, 
            'total' => $this->total, 
            'note' => $this->note, 
            'status' => $this->status, 
            'discount' => $this->discount,
            'coupon_code' => $this->coupon_code,
            'is_order_coupon_applyed' => $this->is_order_coupon_applyed,
            'process_key' => $this->process_key, 
            'billing' => [
                'title' => $this->billing_title, 
                'first_name' => $this->billing_first_name, 
                'last_name' => $this->billing_last_name, 
                'company' => $this->billing_company, 
                'address_1' => $this->billing_address_1,
                'address_2' => $this->billing_address_2, 
                'city' => $this->billing_city,
                'postcode' => $this->billing_postcode, 
                'country' => $this->billing_country, 
                'email' => $this->billing_email,
                'phone' => $this->billing_phone, 
                'fax' => $this->billing_fax
            ],

            'shipping' => [
                'title' => $this->shipping_title, 
                'first_name' => $this->shipping_first_name, 
                'last_name' => $this->shipping_last_name, 
                'company' => $this->shipping_company, 
                'address_1' => $this->shipping_address_1,
                'address_2' => $this->shipping_address_2, 
                'city' => $this->shipping_city,
                'postcode' => $this->shipping_postcode, 
                'country' => $this->shipping_country, 
                'email' => $this->shipping_email,
                'phone' => $this->shipping_phone, 
                'fax' => $this->shipping_fax
            ],

            'line_items' => json_decode($this->items)
            
        ];
    }
}
