<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\ProductsController;
use Validator;
use Request;
use Session;
use App\Models\Option;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;
use App\Models\ManageLanguage;
use App\Library\GetFunction;
use App\Library\CommonFunction;
use App\Models\CustomCurrencyValue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SettingsController extends Controller
{
  public $option;
  public $classGetFunction;
  public $classCommonFunction;
  public $product;
  
  public function __construct(){
    $this->option   =  new OptionController();
    $this->classGetFunction   =   new GetFunction();
    $this->classCommonFunction  =   new CommonFunction();
    $this->product = new ProductsController();
  }
  
  /**
   * 
   * General settings content
   *
   * @param null
   * @return response view
   */
  public function settingsGeneralContent(){
    $data = array();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['user_role_list_data'] = get_available_user_roles();
    $data['settings_data']   =   $this->option->getSettingsData();

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.settings.general-content', $data);
  }
  
  /**
   * 
   * Languages settings content
   *
   * @param null
   * @return response view
   */
  public function settingsLanguagesContent(){
    $data = array();
    $get_avaliable_lang = array();     
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $get_avaliable_lang = get_available_languages_data();
    $data['lang_data'] = $get_avaliable_lang;

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.settings.languages-content', $data);
  }
  
  /**
   * 
   * Languages settings Update content
   *
   * @param null
   * @return response view
   */
  public function settingsLanguagesUpdateContent( $params ){
    $data = array();
    $get_avaliable_lang = array();     
    $get_lang_by_id = array();     
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $get_avaliable_lang = get_available_languages_data();
    $data['lang_data'] = $get_avaliable_lang;
    $get_lang_by_id = ManageLanguage::where(['id' => $params])->get()->toArray();
    $data['lang_data_by_id'] = array_shift($get_lang_by_id);
    
    return view('pages.admin.settings.languages-content', $data);
  }
  
  /**
   * 
   * Settings appearance content
   *
   * @param null
   * @return response view
   */
  public function settingsAppearanceContent(){
    $data = array();
    $templates_details        =   array(); 
    $header_details           =   array();
    $home_details             =   array();
    $blog_details             =   array();
    $product_details          =   array(); 
    $single_product_details   =   array();
    $selected_tab             =   '';

    $unserialize_appearance_data  =   current_appearance_settings();

    //header dir
    $header_dir_list              =   \File::glob(base_path('resources/views/frontend-templates/header/*'), GLOB_ONLYDIR);

    if(count($header_dir_list) > 0){
      foreach($header_dir_list as $dir_name){
        array_push($header_details, basename($dir_name));
      }
    }

    //home dir
    $home_dir_list              =   \File::glob(base_path('resources/views/frontend-templates/home/*'), GLOB_ONLYDIR);

    if(count($home_dir_list) > 0){
    foreach($home_dir_list as $dir_name){
      array_push($home_details, basename($dir_name));
    }
    }

    //blog dir
    $blog_dir_list              =   \File::glob(base_path('resources/views/frontend-templates/blog/*'), GLOB_ONLYDIR);

    if(count($blog_dir_list) > 0){
    foreach($blog_dir_list as $dir_name){
      array_push($blog_details, basename($dir_name));
    }
    }

    //product dir
    $product_dir_list           =   \File::glob(base_path('resources/views/frontend-templates/product/*'), GLOB_ONLYDIR);

    if(count($product_dir_list) > 0){
    foreach($product_dir_list as $dir_name){
      array_push($product_details, basename($dir_name));
    }
    }

    //single product dir
    $single_product_dir_list    =   \File::glob(base_path('resources/views/frontend-templates/single-product/*'), GLOB_ONLYDIR);

    if(count($single_product_dir_list) > 0){
    foreach($single_product_dir_list as $dir_name){
      array_push($single_product_details, basename($dir_name));
    }
    }

    if(Session::has('appearance_active_tab_name')){
      $selected_tab = Session::get('appearance_active_tab_name');
      Session::forget('appearance_active_tab_name');
    }

    $templates_details['header_details']            =   $header_details;
    $templates_details['home_details']              =   $home_details;
    $templates_details['blog_details']              =   $blog_details;
    $templates_details['product_details']           =   $product_details;
    $templates_details['single_product_details']    =   $single_product_details;
    $templates_details['appearance_tab']            =   $unserialize_appearance_data;
    $templates_details['current_tab']               =   $selected_tab;
    $templates_details['parent_cat']                =   get_product_parent_categories();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['frontend_templates_details'] = $templates_details;   
    $data['appearance_settings'] = get_appearance_settings();

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

    
    return view('pages.admin.settings.appearance-content', $data);
  }
  
  /**
   * 
   * Settings email content
   *
   * @param null
   * @return response view
   */
  public function settingsEmailContent(){
    $data = array();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

    
    return view('pages.admin.settings.emails', $data);
  }
  
  /**
   * 
   * Settings custom currency list content
   *
   * @param null
   * @return response view
   */
  public function settingsCustomCurrencyListContent(){
    $data = array();
    $search_value = '';
    
    if(isset($_GET['term_currency_name']) && $_GET['term_currency_name'] != ''){
      $search_value = $_GET['term_currency_name'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['custom_currency_list'] = $this->getCustomCurrencyList( true, $search_value );
    $data['search_value'] = $search_value;

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

    
    return view('pages.admin.settings.custom-currency-list-content', $data);
  }
  
  /**
   * 
   * Settings custom currency add content
   *
   * @param null
   * @return response view
   */
  public function settingsCustomCurrencyAddContent(){
    $data = array();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

    
    return view('pages.admin.settings.add-custom-currency-content', $data);
  }
  
  /**
   * 
   * Settings custom currency update content
   *
   * @param null
   * @return response view
   */
  public function settingsCustomCurrencyUpdateContent( $id ){
    $data = array();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $get_currency = CustomCurrencyValue::where(['id' => $id])->first();
    
    if(!empty( $get_currency )){
      $data['currency_update_data'] = $get_currency;
      $is_vendor = is_vendor_login(); 
      $sidebar['is_vendor_login'] = $is_vendor;
      $data['sidebar_data'] = $sidebar;

      return view('pages.admin.settings.update-custom-currency-content', $data);
    }
    else{
      return view('errors.no_data');
    }
  }
  
  /**
   * 
   * Settings custom currency save content
   *
   * @param null
   * @return response view
   */
  public function settingsCustomCurrencySaveContent(){
    if( Request::isMethod('post') && Session::token() == Request::get('_token') ){
      $data = Request::all();
      
      $rules =  [
                  'currency_name'    => 'required',
                  'select_currency'  => 'required|unique:custom_currency_values,currency_code',
                  'currency_value'   => 'required'
                ];
      
      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        $custom_currency  =  new CustomCurrencyValue;
        
        $custom_currency->currency_name   =   Request::get('currency_name');
        $custom_currency->currency_code   =   Request::get('select_currency');
        $custom_currency->currency_value  =   Request::get('currency_value');
        

        if($custom_currency->save()){
          Session::flash('success-message', Lang::get('admin.successfully_saved_msg') );
          return redirect()->route('admin.custom_currency_settings_update_content', $custom_currency->id);
        }
      }
    }
  }
  
  /**
   * 
   * Settings custom currency update content data
   *
   * @param null
   * @return response view
   */
  
  public function settingsCustomCurrencyUpdateContentData( $id ){
    if( Request::isMethod('post') && Session::token() == Request::get('_token') ){
      $data = Request::all();
      
      $rules =  [
                  'currency_name'    => 'required',
                  'currency_value'   => 'required'
                ];
      
      $get_currency = CustomCurrencyValue::where(['currency_code' => Request::get('select_currency')])->first();
      
      if(!empty($get_currency) && $get_currency->id == $id){
        $rules['select_currency'] = 'required';
      }
      else{
        $rules['select_currency'] = 'required|unique:custom_currency_values,currency_code';
      }
      
      
      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        
        $data = array(
                    'currency_name'    =>  Request::get('currency_name'),
                    'currency_code'    =>  Request::get('select_currency'),
                    'currency_value'   =>  Request::get('currency_value'),
        );

        if(CustomCurrencyValue::where('id', $id)->update($data)){
          Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
          return redirect()->route('admin.custom_currency_settings_update_content', $id);
        }
      }
    }
  }

   /**
   * 
   * Settings email details content
   *
   * @param null
   * @return response view
   */
  public function settingsEmailDetailsContent(){
    $data = array();
    $email_temp = array();
    
    $temp_dir_list    =   \File::glob(base_path('resources/views/emails/order-confirmation/*'), GLOB_ONLYDIR);

    if(count($temp_dir_list) > 0){
      foreach($temp_dir_list as $dir_name){
        array_push($email_temp, basename($dir_name));
      }
    }
      
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['email_templates'] = $email_temp;
    $data['emails_notification_data'] = $this->option->getEmailsNotificationsData();

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

    
    return view('pages.admin.settings.email-content', $data);
  }
  
  
  /**
  * 
  * Update settings data
  *
  * @param null
  * @return void
  */
  public function updateSettingsData(){
    if( Request::isMethod('post') && Session::token() == Request::get('_token') ){
      $get_return_settings_data = array();
      
      if(Request::get('_settings_name') == 'general'){
        $is_reg_enable_at_frontend          = (Request::has('inputEnableDisableFrontendRegistration')) ? true : false;
        $is_guest_enable_at_frontend        = (Request::has('inputEnableGuestUser')) ? true : false;
        $is_login_enable_at_frontend        = (Request::has('inputEnableLoginUser')) ? true : false;
        $is_enable_recaptcha_admin_login    = (Request::has('inputEnableForAdmin')) ? true : false;
        $is_enable_recaptcha_user_login     = (Request::has('inputEnableForUser')) ? true : false;
        $is_enable_recaptcha_user_reg_login = (Request::has('inputEnableForUserReg')) ? true : false;
        
        $is_enable_recaptcha_vendor_login     = (Request::has('inputEnableForVendor')) ? true : false;
        $is_enable_recaptcha_vendor_reg_login = (Request::has('inputEnableForVendorReg')) ? true : false;
        
        $is_download_require_login_at_frontend     = (Request::has('inputLoginRestriction')) ? true : false;
        $is_access_downloadable_product_order_ty   = (Request::has('inputGrantAccessOrderDetails')) ? true : false;
        $is_access_downloadable_product_email      = (Request::has('inputGrantAccessEmail')) ? true : false;
        $is_enable_nexmo      = (Request::has('inputEnableNexmo')) ? true : false;
        
        
        $general_array = array(
            'general_options'     => array('site_title' => Request::get('inputSiteTitle'), 'email_address' => Request::get('inputEmailAddress'), 'site_logo' => Request::get('hf_site_picture'), 'allow_registration_for_frontend' => $is_reg_enable_at_frontend, 'default_role_slug_for_site' => Request::get('inputDefaultRoleForSite')),
            'taxes_options' => array('enable_status' => Request::get('inputTaxesOptions'), 'apply_tax_for' => Request::get('inputApplyTaxes'), 'tax_amount' => Request::get('inputTaxAmount')),
            'checkout_options'    => array('enable_guest_user' => $is_guest_enable_at_frontend, 'enable_login_user' => $is_login_enable_at_frontend),
            'downloadable_products_options'    => array('login_restriction' => $is_download_require_login_at_frontend, 'grant_access_from_thankyou_page' => $is_access_downloadable_product_order_ty, 'grant_access_from_email' => $is_access_downloadable_product_email), 
            'recaptcha_options'   => array('enable_recaptcha_for_admin_login' => $is_enable_recaptcha_admin_login, 'enable_recaptcha_for_user_login' => $is_enable_recaptcha_user_login, 'enable_recaptcha_for_user_registration' => $is_enable_recaptcha_user_reg_login, 'enable_recaptcha_for_vendor_login' => $is_enable_recaptcha_vendor_login, 'enable_recaptcha_for_vendor_registration' => $is_enable_recaptcha_vendor_reg_login, 'recaptcha_secret_key' => Request::get('inputRecaptchaSecretKey'), 'recaptcha_site_key' => Request::get('inputRecaptchaSiteKey')),
            'nexmo_config_option' => array('enable_nexmo_option' => $is_enable_nexmo, 'nexmo_key' => Request::get('inputNexmoKey'), 'nexmo_secret' => Request::get('inputNexmoSecret'), 'number_to' => Request::get('inputNexmoNumberTo'), 'number_from' => Request::get('inputNexmoNumberFrom'), 'message' => Request::get('inputNexmoSendingMessage')),
            'fixer_config_option' => array('fixer_api_access_key' => Request::get('inputCurrencyConverterAccessKey')),
            'currency_options' => array('currency_name' => Request::get('inputCurrency'), 'currency_position' => Request::get('inputCurrencyPosition'), 'thousand_separator' => Request::get('inputThousandSeparator'), 'decimal_separator' => Request::get('inputDecimalSeparator'), 'number_of_decimals' => Request::get('inputNumberofDecimals'), 'currency_conversion_method' => Request::get('CurrencyConversionMethod'), 'frontend_currency' => Request::get('selected_currency_for_frontend')),
            'date_format_options' => array('date_format' => Request::get('inputSelectFormat')),
            'default_frontend_currency' =>  Request::get('selected_currency_for_frontend')
        );
         
        $get_return_settings_data = $this->create_settings_array_data( Request::get('_settings_name'), $general_array );
      }
      
      $data = array(
                    'option_value'  =>  serialize($get_return_settings_data)
      );
      
      if( Option::where('option_name', '_settings_data')->update($data)){
        Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
        return redirect()->back();
      }
    }
    else {
      return redirect()-> back();
    }
  }
  
  /**
   * 
   * Settings data process
   *
   * @param array
   * @return array
   */
  public function create_settings_array_data($source, $array = array()){
    $return_settings_array  = array();
    $general_settings_array = array();
    
    $get_option = $this->option->getSettingsData();
    
    if($source == 'general'){
      $general_settings_array = $array;
    }
    else{
      $general_settings_array = $get_option['general_settings'];
    }
    
    $return_settings_array = array( 
        'general_settings'  => $general_settings_array
    );
    
    return $return_settings_array;
  }
  
  /**
   * 
   * Language zip file processing and settings for default and frontend
   *
   * @param null
   * @return void
   */
  public function manageLangFile($id = ''){
    if( Request::isMethod('post') && Session::token() == Request::get('_token') ){
      $input  = Request::all();
      $zip = new \ZipArchive;
      $destinationPath =  base_path('resources/lang/');
      $upload_folder   =  base_path('public/uploads/');
             
      if(isset($input['post_lang_file_upload']) || isset($input['post_lang_file_edit_option'])){
        $file   = Request::file('lang_file_upload');
        
        $rules = [
                    'inputLangName'    =>  'required',
                 ];

        $validator = Validator:: make($input, $rules);

        if($validator->fails()){
          return redirect()-> back()
          ->withInput()
          ->withErrors( $validator );
        }
        else{
          if(isset($input['post_lang_file_upload'])){
            if(isset($input['lang_file_upload']) && $file->getMimeType() == 'application/zip'){
              $fileName        =  $file->getClientOriginalName();
              $parseExtension  = explode('.', $fileName);


              if( !file_exists($destinationPath.$parseExtension[0])){
                $file->move($destinationPath, $fileName);

                if(file_exists($destinationPath.$fileName)){
                  if ($zip->open($destinationPath.$fileName) === TRUE) {
                    $zip->setPassword('shopist');
                    $zip->extractTo($destinationPath);
                    $zip->close();

                    if($parseExtension && count($parseExtension) > 0){
                      $folder_name = $parseExtension[0];
                    }
                    
                    if($folder_name){ 
                      if (file_exists($destinationPath.$folder_name.'/lang_sample_img.png')) {
                        if(rename($destinationPath.$folder_name.'/lang_sample_img.png', $upload_folder. $folder_name. "_lang_sample_img.png")){
                          $manageLanguage = new ManageLanguage();
																										
                          $manageLanguage->lang_name              =    $input['inputLangName'];
                          $manageLanguage->lang_code              =    $folder_name;
                          $manageLanguage->lang_sample_img        =    '/public/uploads/'.$folder_name. "_lang_sample_img.png";
                          $manageLanguage->status                 =    0;
                          $manageLanguage->default_lang           =    0;

                          if($manageLanguage->save()){
                            //unlink($destinationPath.$fileName);
                            Session::flash('success-message', Lang::get('admin.zip_file_success_msg'));
                            return redirect()-> back();
                          }
                        }
                      }
                      else {
                        
                        if(file_exists($destinationPath.$folder_name) && is_dir($destinationPath.$folder_name)){
                          $this->classGetFunction->removeDirectory($destinationPath.$folder_name);
                        }
                        
                        if(file_exists($destinationPath.$fileName)){
                          unlink($destinationPath.$fileName);
                        }
                        
                        Session::flash('error-message', Lang::get('admin.lang_sample_img_missing'));
                        return redirect()-> back();
                      }
                    }
                  } 
                  else{
                    Session::flash('error-message', Lang::get('admin.zip_file_validation_msg'));
                    return redirect()-> back();
                  }
                }
                else{
                  Session::flash('error-message', Lang::get('admin.lang_file_no_exists'));
                  return redirect()-> back();
                }
              }
              else{
                Session::flash('error-message', Lang::get('admin.file_exists'));
                return redirect()-> back();
              }
            }
            else{
              Session::flash('error-message', Lang::get('admin.zip_file_validation_msg'));
              return redirect()-> back();
            }
          }
          elseif(isset($input['post_lang_file_edit_option'])){
            $data = array();
            $data['lang_name'] = $input['inputLangName'];
            
            
            if(isset($input['lang_file_upload'])){
              if($file->getMimeType() == 'application/zip'){
                $fileName        =  $file->getClientOriginalName();
                $parseExtension  =  explode('.', $fileName);
                $get_lang_data   =  ManageLanguage::where(['id' => $id])->get()->toArray();
                $get_lang_by_id  =  array_shift($get_lang_data);
                
                if( file_exists($destinationPath.$parseExtension[0]) && $get_lang_by_id['lang_code'] == $parseExtension[0] || !file_exists($destinationPath.$parseExtension[0])){  
                  if(file_exists($destinationPath.$get_lang_by_id['lang_code'])){
                    if(is_dir($destinationPath.$get_lang_by_id['lang_code'])){
                      $this->classGetFunction->removeDirectory($destinationPath.$get_lang_by_id['lang_code']);
                    }
                  }
                  
                  if(file_exists($destinationPath.$get_lang_by_id['lang_code'].'.zip')){
                    unlink($destinationPath.$get_lang_by_id['lang_code'].'.zip');
                  }
                  
                  if(file_exists($upload_folder.$get_lang_by_id['lang_sample_img'])){
                    unlink($upload_folder.$get_lang_by_id['lang_sample_img']);
                  }
                  

                  $file->move($destinationPath, $fileName);
                  
                  if(file_exists($destinationPath.$fileName)){
                    if ($zip->open($destinationPath.$fileName) === TRUE){
                      $zip->extractTo($destinationPath);
                      $zip->close();
                      
                      if($parseExtension && count($parseExtension) > 0){
                        $folder_name = $parseExtension[0];
                      }
                      
                      if($folder_name){
                        if (file_exists($destinationPath.$folder_name.'/lang_sample_img.png')) {
                          if(rename($destinationPath.$folder_name.'/lang_sample_img.png', $upload_folder. $folder_name. "_lang_sample_img.png")){
                            $data['lang_code']        =  $folder_name;
                            $data['lang_sample_img']  =  $folder_name. "_lang_sample_img.png";
                          }
                        }
                        else {
                          if(file_exists($destinationPath.$folder_name) && is_dir($destinationPath.$folder_name)){
                            $this->classGetFunction->removeDirectory($destinationPath.$folder_name);
                          }
                          
                          if(file_exists($destinationPath.$fileName)){
                            unlink($destinationPath.$fileName);
                          }
                          
                          //unlink($destinationPath.$folder_name);
                          Session::flash('error-message', Lang::get('admin.lang_sample_img_missing'));
                          return redirect()-> back();
                        }
                      }
                    }
                    else{
                      Session::flash('error-message', Lang::get('admin.zip_file_validation_msg'));
                      return redirect()-> back();
                    }
                  }
                  else{
                    Session::flash('error-message', Lang::get('admin.lang_file_no_exists'));
                    return redirect()-> back();
                  }
                }
                else{
                  Session::flash('error-message', Lang::get('admin.file_exists'));
                  return redirect()-> back();
                }
              }
              else{
                Session::flash('error-message', Lang::get('admin.zip_file_validation_msg'));
                return redirect()-> back();
              }
            }
            
            
            if(count($data) > 0){
              if(ManageLanguage::where(['id' => $id])->update( $data )){
                Session::flash('success-message', Lang::get('admin.zip_file_success_updated_msg'));
                return redirect()-> route('admin.languages_settings_content');
              }
            }
          }
        }
      }
      elseif(isset($input['post_lang_settings'])){
        $get_lang_all = get_available_languages_data();
        $is_enabled   = false;
        $frontend_ary = array();
        $default_ary  = array();
        
        if(isset($input['switching_for_frontend']) && count($input['switching_for_frontend']) >0){
          $frontend_ary = $input['switching_for_frontend']; 
        }
        
        if(isset($input['switching_for_default']) && count($input['switching_for_default']) >0){
          $default_ary = $input['switching_for_default']; 
        }
        
        if(count($get_lang_all)>0){
          foreach($get_lang_all as $row){
            if( ( count($frontend_ary) > 0 && in_array($row['id'], $frontend_ary) ) && ( count($default_ary) > 0 && in_array($row['id'], $default_ary) ) ){
              $data = array(
                            'status'         =>    1,
                            'default_lang'   =>    1
              );

              if( ManageLanguage::where(['id' => $row['id']])->update( $data )){
                $is_enabled = true;
              }
            }
            elseif( ( count($default_ary) > 0 && in_array($row['id'], $default_ary) ) ){
              $data = array(
                            'status'         =>    0,
                            'default_lang'   =>    1
              );

              if( ManageLanguage::where(['id' => $row['id']])->update( $data )){
                $is_enabled = true;
              }
            }
            elseif( ( count($frontend_ary) > 0 && in_array($row['id'], $frontend_ary) ) ){
              $data = array(
                            'status'         =>    1,
                            'default_lang'   =>    0
              );

              if( ManageLanguage::where(['id' => $row['id']])->update( $data )){
                $is_enabled = true;
              }
            }
            else{
              $data = array(
                            'status'         =>    0,
                            'default_lang'   =>    0
              );

              if( ManageLanguage::where(['id' => $row['id']])->update( $data )){
                $is_enabled = true;
              }
            }
          }
        
          if($is_enabled){
            Session::flash('success-message', Lang::get('admin.update_language_settings_msg'));
            return redirect()-> back();
          }
        }
      }
    }
    else{
      return redirect()-> back();
    }
  }
  
  /**
   * 
   * Save appearance settings data 
   *
   * @param null
   * @return void
   */
  public function saveAppearanceSettingsData(){
    if( Request::isMethod('post') && Session::token() == Request::get('_token') ){
      $is_slider_enable             = false;
      $is_custom_css_enable         = false;
      $is_general_custom_css_enable = false;
      
      
      $unserialize_appearance_data  =   $this->option->getAppearanceData();

      if(Request::has('inputVisibilitySlider')){
        $is_slider_enable = true;
      }
      
      if(Request::has('inputHeaderCustomCSS')){
        $is_custom_css_enable = true;
      }
      
      if(Request::has('inputGeneralCustomCSS')){
        $is_general_custom_css_enable = true;
      }
       
      if(isset($unserialize_appearance_data['settings'])){
        $unserialize_appearance_data['settings'] = Request::get('_frontend_images_json');
      }
      
      if(isset($unserialize_appearance_data['settings_details']['header_details'])){
        if(isset($unserialize_appearance_data['settings_details']['header_details']['slider_visibility'])){
          $unserialize_appearance_data['settings_details']['header_details']['slider_visibility'] = $is_slider_enable;
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['custom_css'])){
          $unserialize_appearance_data['settings_details']['header_details']['custom_css'] = $is_custom_css_enable;
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_top_gradient_start_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_top_gradient_start_color'] = Request::get('header_top_bg_start_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_top_gradient_end_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_top_gradient_end_color'] = Request::get('header_top_bg_end_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_bottom_gradient_start_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_bottom_gradient_start_color'] = Request::get('header_bottom_bg_start_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_bottom_gradient_end_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_bottom_gradient_end_color'] = Request::get('header_bottom_bg_end_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_text_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_text_color'] = Request::get('header_text_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_text_hover_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_text_hover_color'] = Request::get('header_text_hover_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_text_size'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_text_size'] = Request::get('header_text_size');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_selected_menu_bg_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_selected_menu_bg_color'] = Request::get('header_selected_menu_bg_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_selected_menu_text_color'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_selected_menu_text_color'] = Request::get('header_selected_menu_text_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['header_details']['header_slogan'])){
          $unserialize_appearance_data['settings_details']['header_details']['header_slogan'] = Request::get('header_slogan');
        }
      }
      
      if(isset($unserialize_appearance_data['settings_details']['home_details'])){
        $unserialize_appearance_data['settings_details']['home_details']['cat_list_to_display'] = Request::get('inputSelectCatForHomePage');
      }
      
      if(isset($unserialize_appearance_data['settings_details']['home_details'])){
        $unserialize_appearance_data['settings_details']['home_details']['cat_collection_list_to_display'] = Request::get('inputSelectCatCollectionForHomePage');
      }
      
      if(isset($unserialize_appearance_data['settings_details']['footer_details'])){
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['footer_about_us_description'])){
          $unserialize_appearance_data['settings_details']['footer_details']['footer_about_us_description'] = Request::get('about_us_description_editor');
        }
        
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['fb'] = Request::get('fb_follow_us_url');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['twitter'] = Request::get('twitter_follow_us_url');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['linkedin'] = Request::get('linkedin_follow_us_url');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['dribbble'] = Request::get('dribbble_follow_us_url');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['google_plus'] = Request::get('google_plus_follow_us_url');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['instagram'] = Request::get('instagram_follow_us_url');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['footer_details']['follow_us_url'])){
          $unserialize_appearance_data['settings_details']['footer_details']['follow_us_url']['youtube'] = Request::get('youtube_follow_us_url');
        }
      }
      
      if(isset($unserialize_appearance_data['settings_details']['general'])){
        if(isset($unserialize_appearance_data['settings_details']['general']['custom_css'])){
          $unserialize_appearance_data['settings_details']['general']['custom_css'] = $is_general_custom_css_enable;
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['body_bg_color'])){
          $unserialize_appearance_data['settings_details']['general']['body_bg_color'] = Request::get('general_settings_body_bg');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['filter_price_min'])){
          $unserialize_appearance_data['settings_details']['general']['filter_price_min'] = Request::get('min_filter_price');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['filter_price_max'])){
          $unserialize_appearance_data['settings_details']['general']['filter_price_max'] = Request::get('max_filter_price');
        }
       
        if(isset($unserialize_appearance_data['settings_details']['general']['sidebar_panel_bg_color'])){
          $unserialize_appearance_data['settings_details']['general']['sidebar_panel_bg_color'] = Request::get('sidebar_panel_bg_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['sidebar_panel_title_text_color'])){
          $unserialize_appearance_data['settings_details']['general']['sidebar_panel_title_text_color'] = Request::get('sidebar_panel_title_text_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['sidebar_panel_title_text_bottom_border_color'])){
          $unserialize_appearance_data['settings_details']['general']['sidebar_panel_title_text_bottom_border_color'] = Request::get('sidebar_panel_title_text_bottom_border');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['sidebar_panel_content_text_color'])){
          $unserialize_appearance_data['settings_details']['general']['sidebar_panel_content_text_color'] = Request::get('sidebar_panel_content_text_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['product_box_bg_color'])){
          $unserialize_appearance_data['settings_details']['general']['product_box_bg_color'] = Request::get('product_box_bg_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['product_box_border_color'])){
          $unserialize_appearance_data['settings_details']['general']['product_box_border_color'] = Request::get('product_box_border_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['product_box_text_color'])){
          $unserialize_appearance_data['settings_details']['general']['product_box_text_color'] = Request::get('product_box_content_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['product_box_btn_bg_color'])){
          $unserialize_appearance_data['settings_details']['general']['product_box_btn_bg_color'] = Request::get('product_box_btn_bg_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['btn_text_color'])){
          $unserialize_appearance_data['settings_details']['general']['btn_text_color'] = Request::get('btn_text_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['product_box_btn_hover_color'])){
          $unserialize_appearance_data['settings_details']['general']['product_box_btn_hover_color'] = Request::get('product_box_btn_hover_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['btn_hover_text_color'])){
          $unserialize_appearance_data['settings_details']['general']['btn_hover_text_color'] = Request::get('btn_hover_text_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['sidebar_panel_title_text_font_size'])){
          $unserialize_appearance_data['settings_details']['general']['sidebar_panel_title_text_font_size'] = Request::get('sidebar_panel_title_text_size');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['sidebar_panel_content_text_font_size'])){
          $unserialize_appearance_data['settings_details']['general']['sidebar_panel_content_text_font_size'] = Request::get('sidebar_panel_content_text_size');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['product_box_text_font_size'])){
          $unserialize_appearance_data['settings_details']['general']['product_box_text_font_size'] = Request::get('product_box_content_text_size');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['selected_menu_border_color'])){
          $unserialize_appearance_data['settings_details']['general']['selected_menu_border_color'] = Request::get('selected_menu_border_color');
        }
        
        if(isset($unserialize_appearance_data['settings_details']['general']['pages_content_title_border_color'])){
          $unserialize_appearance_data['settings_details']['general']['pages_content_title_border_color'] = Request::get('pages_content_title_border_color');
        }
      }
      
      $data = array(
                    'option_value'  => serialize($unserialize_appearance_data)
      );
      
      if( Option::where('option_name', '_appearance_tab_data')->update($data)){
        return redirect()-> back();
      }
    }
  }
		
		/**
   * 
   * Save email notifications content data
   *
   * @param null
   * @return void
   */
		public function saveEmailsContentData(){
      if( Request::isMethod('post') && Session::token() == Request::get('_token') ){
        $emails_data  =  $this->option->getEmailsNotificationsData();

        if(Request::has('email_type') && Request::get('email_type') == 'new_order'){
          $is_new_order_enable = false;

          if(Request::has('new_order_enable_disable')){
            $is_new_order_enable = true;
          }

          $emails_data['new_order']['enable_disable']			 =		$is_new_order_enable;
          $emails_data['new_order']['subject']				     =		Request::get('new_order_subject');
          $emails_data['new_order']['email_heading']			 =    Request::get('new_order_email_heading');
          $emails_data['new_order']['body_bg_color']			 =		Request::get('new_order_body_bg_color');
          $emails_data['new_order']['selected_template']   =    Request::get('templates_name');
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'cancelled_order'){
          $is_cancelled_order_enable = false;

          if(Request::has('cancelled_order_enable_disable')){
            $is_cancelled_order_enable = true;
          }

          $emails_data['cancelled_order']['enable_disable']			 =		$is_cancelled_order_enable;
          $emails_data['cancelled_order']['subject']				     =		Request::get('cancelled_order_subject');
          $emails_data['cancelled_order']['email_heading']			 =    Request::get('cancelled_order_email_heading');
          $emails_data['cancelled_order']['body_bg_color']			 =		Request::get('cancelled_order_body_bg_color');
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'processed_order'){
          $is_processed_order_enable = false;

          if(Request::has('processed_order_enable_disable')){
            $is_processed_order_enable = true;
          }

          $emails_data['processed_order']['enable_disable']			 =		$is_processed_order_enable;
          $emails_data['processed_order']['subject']				     =		Request::get('processed_order_subject');
          $emails_data['processed_order']['email_heading']			 =    Request::get('processed_order_email_heading');
          $emails_data['processed_order']['body_bg_color']			 =		Request::get('processed_order_body_bg_color');
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'completed_order'){
          $is_completed_order_enable = false;

          if(Request::has('completed_order_enable_disable')){
            $is_completed_order_enable = true;
          }

          $emails_data['completed_order']['enable_disable']			 =		$is_completed_order_enable;
          $emails_data['completed_order']['subject']				     =		Request::get('completed_order_subject');
          $emails_data['completed_order']['email_heading']			 =    Request::get('completed_order_email_heading');
          $emails_data['completed_order']['body_bg_color']			 =		Request::get('completed_order_body_bg_color');
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'new_customer_account'){
          $is_new_customer_account_enable = false;

          if(Request::has('new_customer_account_enable_disable')){
            $is_new_customer_account_enable = true;
          }

          $emails_data['new_customer_account']['enable_disable']		 =		$is_new_customer_account_enable;
          $emails_data['new_customer_account']['subject']				     =		Request::get('new_customer_account_subject');
          $emails_data['new_customer_account']['email_heading']			 =    '';
          $emails_data['new_customer_account']['body_bg_color']			 =		'';
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'vendor_new_customer_account'){
          $is_vendor_new_account_enable = false;

          if(Request::has('vendor_new_account_enable_disable')){
            $is_vendor_new_account_enable = true;
          }

          $emails_data['vendor_new_account']['enable_disable']		 =		$is_vendor_new_account_enable;
          $emails_data['vendor_new_account']['subject']				     =		Request::get('vendor_new_account_subject');
          $emails_data['vendor_new_account']['email_heading']			 =    '';
          $emails_data['vendor_new_account']['body_bg_color']			 =		'';
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'vendor_account_activation'){
          $is_vendor_account_activation_enable = false;

          if(Request::has('vendor_account_activation_enable_disable')){
            $is_vendor_account_activation_enable = true;
          }

          $emails_data['vendor_account_activation']['enable_disable']		 =		$is_vendor_account_activation_enable;
          $emails_data['vendor_account_activation']['subject']				   =		Request::get('vendor_account_activation_subject');
          $emails_data['vendor_account_activation']['email_heading']		 =    '';
          $emails_data['vendor_account_activation']['body_bg_color']		=		  '';
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'vendor_withdraw_request'){
          $is_vendor_withdraw_request_enable = false;

          if(Request::has('vendor_withdraw_request_enable_disable')){
            $is_vendor_withdraw_request_enable = true;
          }

          $emails_data['vendor_withdraw_request']['enable_disable']		 =		$is_vendor_withdraw_request_enable;
          $emails_data['vendor_withdraw_request']['subject']				   =		Request::get('vendor_withdraw_request_subject');
          $emails_data['vendor_withdraw_request']['email_heading']		 =    '';
          $emails_data['vendor_withdraw_request']['body_bg_color']		 =		'';
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'vendor_withdraw_request_cancelled'){
          $is_vendor_withdraw_request_cancelled_enable = false;

          if(Request::has('vendor_withdraw_request_cancelled_enable_disable')){
            $is_vendor_withdraw_request_cancelled_enable = true;
          }

          $emails_data['vendor_withdraw_request_cancelled']['enable_disable']		 =		$is_vendor_withdraw_request_cancelled_enable;
          $emails_data['vendor_withdraw_request_cancelled']['subject']				   =		Request::get('vendor_withdraw_request_cancelled_subject');
          $emails_data['vendor_withdraw_request_cancelled']['email_heading']		 =    '';
          $emails_data['vendor_withdraw_request_cancelled']['body_bg_color']		 =		'';
        }
        elseif(Request::has('email_type') && Request::get('email_type') == 'vendor_withdraw_request_completed'){
          $is_vendor_withdraw_request_completed_enable = false;

          if(Request::has('vendor_withdraw_request_completed_enable_disable')){
            $is_vendor_withdraw_request_completed_enable = true;
          }

          $emails_data['vendor_withdraw_request_completed']['enable_disable']		 =		$is_vendor_withdraw_request_completed_enable;
          $emails_data['vendor_withdraw_request_completed']['subject']				      =		Request::get('vendor_withdraw_request_completed_subject');
          $emails_data['vendor_withdraw_request_completed']['email_heading']		  =    '';
          $emails_data['vendor_withdraw_request_completed']['body_bg_color']		  =		'';
        }

        $data = array(
                'option_value'  => serialize($emails_data)
        );

        if( Option::where('option_name', '_emails_notification_data')->update($data)){
          Session::flash('success-message', Lang::get('admin.successfully_updated_msg'));
          return redirect()-> back();
        }
      }
		}	
  
  /**
   * 
   * Dynamic menu view  
   *
   * @param null
   * @return void
   */
		public function settingsMenuContent(){
    $data = array();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['menu_html'] = $this->menuHTML();
    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

     
    return view('pages.admin.settings.menu-content', $data);
  }
  
  /**
   * 
   * menu html
   *
   * @param null
   * @return void
   */
  public function menuHTML(){
    $parent_categories = $this->product->get_parent_categories(0, 'product_cat');
    $menu_data = $this->option->getMenuData();
    
    $html = '';
    $cat_id = array();
    
    if(is_array($menu_data) && count($menu_data) > 0){
      foreach($menu_data as $menu){
        $status = '';
        $name = '';
        if($menu->status == 'enable'){
          $status = 'checked="checked"';
        }
        
        $label1 = explode('|', $menu->label);
        $label2 = explode('##', $label1[1]);
        
        if($label2[0] == 'simple'){
          if($label1[0] == 'home'){
            $name = Lang::get('frontend.home');
          }
          elseif ($label1[0] == 'collection') {
            $name = Lang::get('frontend.shop_by_cat_label');
          }
          elseif ($label1[0] == 'products') {
            $name = Lang::get('frontend.all_products_label');
          }
          elseif ($label1[0] == 'checkout') {
            $name = Lang::get('frontend.checkout');
          }
          elseif ($label1[0] == 'cart') {
            $name = Lang::get('frontend.cart');
          }
          elseif ($label1[0] == 'blog') {
            $name = Lang::get('frontend.blog');
          }
          elseif ($label1[0] == 'store_list') {
            $name = Lang::get('frontend.vendor_account_store_list_title_label');
          }
          elseif ($label1[0] == 'pages') {
            $name = Lang::get('frontend.pages_label');
          }
        }
        elseif (isset($label2[0]) && $label2[0] == 'cat' && isset($label2[1])) {
          $get_term = $this->product->getTermDataById( $label2[1] );
          
          if(count($get_term) > 0){
            $name = $get_term[0]['name']. ' &nbsp;&nbsp; ('. Lang::get('admin.parent_cat_trace_label'). ')';
            array_push($cat_id, $label2[1]);
          }
        }
        
        if(!empty($name)){
          $html .= '<li class="ui-state-default">';
          $html .= '<div class="input-group mb-3">';
          $html .= '<div class="input-group-prepend">';
          $html .= '<span class="input-group-text" id="basic-addon1"><input type="checkbox" '. $status .' name="inline_menu" class="menu-checkbox" value="'. $menu->label .'"></span>';
          $html .= '</div>';
          $html .= '<label class="form-control">'. $name .'</label>';
          $html .= '</div>';
          $html .= '</li>';
        }
      }
      
      if(count($parent_categories) > 0){
        foreach($parent_categories as $cat){
          if(!in_array($cat['term_id'], $cat_id)){
            $html .=  '<li class="ui-state-default">';
            $html .=  '<div class="input-group mb-3">';
            $html .=  '<div class="input-group-prepend">';  

            $term1 = $cat['slug']; $term2 = $cat['term_id']; $term3 = $term1 . '|cat##' . $term2;

            $html .=  '<span class="input-group-text" id="basic-addon1"><input type="checkbox" name="inline_menu" class="menu-checkbox" value="'. $term3 .'"></span>';
            $html .=  '</div>';
            $html .=  '<label class="form-control">'. $cat['name'] .' &nbsp;&nbsp; ('. Lang::get('admin.parent_cat_trace_label') .')</label>';
            $html .=  '</div></li>';
          }
        }
      }
    }
    
    return $html;
  }
  
  /**
   * 
   * Get all custom currency list
   *
   * @param null
   * @return void
   */
  
  public function getCustomCurrencyList( $pagination = false, $search_val = null ){
    $currency_list = array();
    
    if(!empty($search_val) && $search_val != ''){
      $currency_list = CustomCurrencyValue::where('currency_name', 'LIKE', '%'. $search_val.'%')
                       ->orderBy('id', 'desc')
                       ->get()
                       ->toArray();
    }
    else{
      $currency_list = CustomCurrencyValue::orderBy('id', 'desc')
                       ->get()
                       ->toArray();
    }
    
    if($pagination){
      $currentPage = LengthAwarePaginator::resolveCurrentPage();
      $col = new Collection( $currency_list );
      $perPage = 10;
      $currentPageSearchResults = $col->slice(($currentPage - 1) * $perPage, $perPage)->all();
      $currency_object = new LengthAwarePaginator($currentPageSearchResults, count($col), $perPage);
      
      $currency_object->setPath( route('admin.custom_currency_settings_list_content') );
    }
    
    if($pagination){
      return $currency_object;
    }
    else{
      return $currency_list;
    }
  }
}