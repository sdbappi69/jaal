<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use App\Library\GetFunction;
use Illuminate\Support\Carbon;

class ReportsController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $slug)
    {
        if( $request->isMethod('get')){
            $array = array('reports_slug' => $slug);
            $validator = $this->getValidator($array); 
            
            if ($validator->fails()) {
                return response()->json(__('api.middleware.bad_parameter'), 400);
            }

            $get_function = new GetFunction();
            $reports_data = array();
            $current_date_obj = Carbon::now(); 
            $current_date = $current_date_obj->format('Y-m-d'); 

            if($slug == 'gross-sales-by-product-title'){
                $start_date = $current_date;
                $end_date = $current_date;

                if(isset($request->start_date) && !empty($request->start_date)){
                    $start_date = $request->start_date;  
                }

                if(isset($request->end_date) && !empty($request->end_date)){
                    $end_date = $request->end_date;  
                }
                
                $productsTitleOrders = $get_function->get_orders_by_date_range($start_date, $end_date);
                $reports_data = $get_function->get_reports_gross_products_title_data( $productsTitleOrders );
            }
            else if($slug == 'sales-by-month'){
                $yearOrders = $get_function->get_orders_by_year();
                $get_order_details = $get_function->get_order_by_specific_date_range($yearOrders);
                $reports_data =  $get_function->get_report_data_order_details_by_month($get_order_details);
            }
            else if($slug == 'sales-by-last-7-days'){
                $carbonObject = new Carbon();
                $last7DaysOrders = $get_function->get_orders_by_date_range($carbonObject->parse($carbonObject->today())->subWeeks(1)->toDateString(), $carbonObject->today()->toDateString());
                $reports_data  = $get_function->get_order_by_specific_date_range($last7DaysOrders);
            }
            else if($slug == 'sales-by-custom-days'){
                $start_date = $current_date;
                $end_date = $current_date;

                if(isset($request->start_date) && !empty($request->start_date)){
                    $start_date = $request->start_date;  
                }

                if(isset($request->end_date) && !empty($request->end_date)){
                    $end_date = $request->end_date;  
                }

                $customDaysOrders = $get_function->get_orders_by_date_range($start_date, $end_date);
                $reports_data = $get_function->get_order_by_specific_date_range( $customDaysOrders );
            }
            else if($slug == 'sales-by-payment-method'){
                $start_date = $current_date;
                $end_date = $current_date;

                if(isset($request->start_date) && !empty($request->start_date)){
                    $start_date = $request->start_date;  
                }

                if(isset($request->end_date) && !empty($request->end_date)){
                    $end_date = $request->end_date;  
                }

                $getOrders = $get_function->get_orders_by_date_range($start_date, $end_date);
                $reports_data = $get_function->get_reports_payment_method_data( $getOrders );
            }
            else if($slug == 'coupons-totals'){
                $get_coupons = $get_function->get_reports_coupons_totals();
                $reports_data = $get_coupons;
            }
            else if($slug == 'users-totals'){
                $get_users = $get_function->get_reports_role_based_user_totals();
                $reports_data = $get_users;
            }
            else if($slug == 'orders-totals'){
                $get_orders_totals = $get_function->get_reports_orders_totals();
                $reports_data = $get_orders_totals;
            }
            else if($slug == 'products-totals'){
                $get_products_totals = $get_function->get_reports_products_totals();
                $reports_data = $get_products_totals;
            }
            else if($slug == 'todays-orders'){
                $get_todays_orders = $get_function->get_reports_todays_orders();
                $reports_data = $get_todays_orders;
            }
            else if($slug == 'todays-totals-sale'){
                $get_todays_order_sale = $get_function->get_reports_todays_orders_totals();
                $reports_data = $get_todays_order_sale;
            }

            return response()->json($reports_data);
        }

        return response()->json(__('api.middleware.forbidden', array('attribute' => $request->method())), 403);
    }

    /**
     * Gets a new validator instance with the defined rules.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Support\Facades\Validator
     */
    protected function getValidator($slug)
    {
      $rules = [
        'reports_slug' =>  [
            'required',
            Rule::in(['gross-sales-by-product-title', 'sales-by-month', 'sales-by-last-7-days', 'sales-by-custom-days', 'sales-by-payment-method', 'coupons-totals', 'users-totals', 'orders-totals', 'products-totals', 'todays-orders', 'todays-totals-sale'])
          ]
      ];
      
      return Validator::make($slug, $rules);
    }
}