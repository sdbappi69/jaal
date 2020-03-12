<?php

namespace shopist\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TestimonialCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'links' => [
                   "first" => $this->url( $this->currentPage() ),
                   "last" => $this->url( $this->lastPage() ),
                   "prev" => $this->previousPageUrl(),
                   "next" => $this->nextPageUrl(),
                   'url_range' => $this->getUrlRange($this->currentPage(), $this->lastPage())
                ],
                'meta' => [
                  'count' => $this->count(),
                  'per_page' => $this->perPage(),
                  'current_page' => $this->currentPage(),
                  'total_pages' => $this->lastPage(),
                    
                ]
            ],
            'extra' => [
              'meta' => [
                'api_version' => '1.0',
                'platform' => 'shopist pro',
                'return type' => 'testimonial'  
              ]  
            ]
        ];
    }
}
