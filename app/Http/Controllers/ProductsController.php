<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Request;
use Session;
use Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Term;
use App\Models\TermExtra;
use Illuminate\Support\Facades\Lang;
use App\Models\Post;
use App\Models\PostExtra;
use App\Models\Product;
use App\Models\ProductExtra;
use App\Models\ObjectRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\VendorsController;
use App\Http\Controllers\OptionController;
use App\Library\CommonFunction;
use Carbon\Carbon;
use App\Models\SaveCustomDesign;


class ProductsController extends Controller
{
	public $option;
  public $carbonObject;
  public $classCommonFunction;
  public $cat_list_arr = array();
  public $parent_id = 0;
  public $vendors;


  public function __construct(){
		$this->option               =   new OptionController();
    $this->carbonObject         =   new Carbon();
    $this->classCommonFunction  =   new CommonFunction();
    $this->vendors              =   new VendorsController();
	}	
  
  /**
   * 
   * Product add content
   *
   * @param null
   * @return response view
   */
  public function productAddContent(){
    $data = array();
    $data = $this->classCommonFunction->commonDataForAllPages();
    $get_data = $this->createProductContentData( $data );
 
    return view('pages.admin.product.add-product-content', $get_data);
  }
  
  /**
   * 
   * Product update content
   *
   * @param null
   * @return response view
   */
  public function productUpdateContent( $params ){
    $get_post  =  $this->getProductDataByHandle( $params );
    
    if(is_array($get_post)  && count($get_post) > 0){
      $data = array();
      $get_available_attribute =  array();
      $product_id = $get_post['id'];
      
      $data = $this->classCommonFunction->commonDataForAllPages();
      $get_available_attribute	=  $this->getAllAttributes( $product_id );

      $data['attrs_list_data_by_product']     =   $get_available_attribute;
      $data['product_post_data']              =   $get_post;

     
      $data['role_based_pricing_data']        =   get_role_based_pricing_by_product_id($product_id);
      $data['variation_data']                 =   $this->classCommonFunction->get_variation_and_data_by_product_id( $product_id );
      $data['custom_designer_settings_data']  =   $this->option->getCustomDesignerSettingsData();

    
      $get_variation_data = $this->classCommonFunction->get_variation_by_product_id( $product_id );


      if(!empty($get_variation_data)){
        $data['variation_json']  =  json_encode($get_variation_data);
      }
      else{
        $data['variation_json'] = null;
      }

      $get_post_attr  =  PostExtra::where(['post_id' => $product_id, 'key_name' => '_attribute_post_data'])->first();

      if(!empty($get_post_attr)){
        $data['attribute_post_meta_by_id'] = json_decode($get_post_attr->key_value);
      }
      else{
        $data['attribute_post_meta_by_id'] = null;
      }
      
      if($data['product_post_data']['_product_custom_designer_settings']['enable_global_settings'] == 'yes'){
        if(isset($data['custom_designer_settings_data']) && count($data['custom_designer_settings_data'])>0){
          $data['designer_hf_data']   =  $data['custom_designer_settings_data']['general_settings'];
        }
        else{
          $data['designer_hf_data'] = array();
        }
      }
      elseif($data['product_post_data']['_product_custom_designer_settings']['enable_global_settings'] == 'no'){
        if(isset($data['product_post_data']['_product_custom_designer_settings']) && count($data['product_post_data']['_product_custom_designer_settings']) >0){
          $data['designer_hf_data']   =   $data['product_post_data']['_product_custom_designer_settings'];
        }
        else{
          $data['designer_hf_data'] = array();
        }
      }
      
      $get_data = SaveCustomDesign ::where('product_id', $product_id)->first();

      if(!empty($get_data) && $get_data->count() > 0){
        $data['design_json_data']  =  $get_data['design_data'];
      }
      else{
        $data['design_json_data']  =  '';
      }

      $data['art_cat_lists_data']  =   $this->getTermData( 'designer_cat', false, null, 1 );
      $data['fonts_list']  =   $this->getFontsList(false, null, 1);
      $data['shape_list']  =   $this->getShapeList(false, null, 1);
      
      $generalTabActiveClass = '';
      $featureTabActiveClass = '';
      $customizeBtn          = 'style=display:none;';
      $tabSettings           = array();

      if($data['product_post_data']['post_type'] == 'simple_product' || $data['product_post_data']['post_type'] == 'downloadable_product'){
        $generalTabActiveClass = 'show active';
      }
      elseif($data['product_post_data']['post_type'] == 'customizable_product'){
        $generalTabActiveClass = 'show active';
        $customizeBtn          = 'style=display:none;';
      }
      elseif($data['product_post_data']['post_type'] == 'configurable_product'){
        $featureTabActiveClass = 'show active';
      }

      $tabSettings['generalTab']   = $generalTabActiveClass;
      $tabSettings['featureTab']   = $featureTabActiveClass;
      $tabSettings['btnCustomize'] = $customizeBtn;

      $data['tabSettings'] = $tabSettings;


      $data['selected_cat']       =   $this->getCatByObjectId( $product_id );
      $data['selected_tag']       =   $this->getTagsByObjectId( $product_id );
      $data['selected_colors']    =   $this->getColorsByObjectId( $product_id );
      $data['selected_sizes']     =   $this->getSizesByObjectId( $product_id );
      $data['selected_brands']    =   $this->getManufacturerByObjectId( $product_id );

      $upsell_products = array();
      if(count(get_upsell_products($product_id)) > 0){
        foreach(get_upsell_products($product_id) as $upsell_ids){
          array_push($upsell_products, get_product_title($upsell_ids). ' #'.$upsell_ids);
        }
      }

      $data['upsell_products'] = json_encode( $upsell_products );

      $crosssell_products = array();
      if(count(get_crosssell_products($product_id)) > 0){
        foreach(get_crosssell_products($product_id) as $crosssell_ids){
          array_push($crosssell_products, get_product_title($crosssell_ids). ' #'.$crosssell_ids);
        }
      }

      $data['crosssell_products'] = json_encode( $crosssell_products );

      $is_vendor = is_vendor_login(); 
      $data['is_vendor_login'] = $is_vendor;
      $get_data = $this->createProductContentData( $data );
      
      return view('pages.admin.product.update-product-content', $get_data);
    }
    else{
      return view('errors.no_data');
    }
  }
  
  /**
   * 
   * Product list content
   *
   * @param null
   * @return response view
   */
  public function productListContent( $params ){
    $data = array();
    $search_value = '';
      
    if(isset($_GET['term_product']) && $_GET['term_product'] != ''){
      $search_value = $_GET['term_product'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;

    $data['product_all_data']  =  $this->getProducts(true, $search_value, -1, $params, $is_vendor);
    $data['search_value']      =  $search_value;
    $data['settings'] = $this->option->getSettingsData();
    
    return view('pages.admin.product.product-list', $data);
  }

  /**
   * 
   * Product categories list content
   *
   * @param null
   * @return response view
   */
  public function productCategoriesListContent(){
    $data = array();
    $search_value = '';
        
    if(isset($_GET['term_cat']) && $_GET['term_cat'] != ''){
      $search_value = $_GET['term_cat'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['cat_list_data']           =  $this->getTermData( 'product_cat', true, $search_value, -1 );
    $data['only_cat_name']           =  $this->get_categories_name_for_list('product_cat');
    $data['search_value']            =  $search_value;
    $data['action']                  =  route('admin.product_categories_list');

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.categories-list', $data);
  }
  
  /**
   * 
   * Product tags list content
   *
   * @param null
   * @return response view
   */
  public function productTagsListContent(){
    $data = array();
    $search_value = '';
      
    if(isset($_GET['term_tag']) && $_GET['term_tag'] != ''){
      $search_value = $_GET['term_tag'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['tag_list_data']   =  $this->getTermData( 'product_tag', true, $search_value, -1 );
    $data['search_value']    =  $search_value;

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.product.product-tags-list', $data);
  }
  
  /**
   * 
   * Product attributes list content
   *
   * @param null
   * @return response view
   */
  public function productAttributesListContent(){
    $data = array();
    $search_value = '';
      
    if(isset($_GET['term_attrs']) && $_GET['term_attrs'] != ''){
      $search_value = $_GET['term_attrs'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['attribute_list_data']   =  $this->getTermData( 'product_attr', true, $search_value, -1 );
    $data['search_value']          =  $search_value;

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.product.product-attribute-list', $data);
  }
  
  /**
   * 
   * Product colors list content
   *
   * @param null
   * @return response view
   */
  public function productColorsListContent(){
    $data = array();
    $search_value = '';
      
    if(isset($_GET['term_colors']) && $_GET['term_colors'] != ''){
      $search_value = $_GET['term_colors'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['colors_list_data']   =   $this->getTermData( 'product_colors', true, $search_value, -1 );
    $data['search_value']       =   $search_value;

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.product.product-colors-list', $data);
  }
  
  /**
   * 
   * Product sizes list content
   *
   * @param null
   * @return response view
   */
  public function productSizesListContent(){
    $data = array();
    $search_value = '';
      
    if(isset($_GET['term_sizes']) && $_GET['term_sizes'] != ''){
      $search_value = $_GET['term_sizes'];
    }
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['sizes_list_data']   =   $this->getTermData( 'product_sizes', true, $search_value, -1 );
    $data['search_value']      =   $search_value;

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.product.product-sizes-list', $data);
  }

  /**
   * 
   * Product comments list content
   *
   * @param null
   * @return response view
   */
  public function productCommentsListContent(){
    $data = array();
    
    $data = $this->classCommonFunction->commonDataForAllPages();
    $data['product_comments'] = $this->getProductCommentsList();

    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;
    
    return view('pages.admin.product.comments-list', $data);
  }

  
  /**
   * 
   * Get term data
   *
   * @param term type, pagination, search value, status flag
   * @param term type required
   * @param pagination required. Boolean type TRUE or FALSE, by default false
   * @param search value optional
	 * @param status flag by default -1. -1 for all data, 1 for status enable and 0 for disable status
   * @return array
   */
	public function getTermData( $term_type, $pagination = false, $search_val = null, $status_flag = -1){
    
    if($status_flag == -1){
        $where = ['terms.type' => $term_type];
    }
    else{
        $where = ['terms.type' => $term_type, 'terms.status' => $status_flag];
    }
				
    if($search_val && $search_val != ''){
      $get_term_data = DB::table('terms') 
                           ->where($where)
                           ->where('terms.name', 'LIKE', $search_val.'%')
                           ->orderBy('terms.term_id', 'desc');
                       
    }
    else{
      $get_term_data = DB::table('terms')
                           ->where($where)
                           ->orderBy('terms.term_id', 'desc');
                       
    }

    if($term_type == 'blog_cat' || $term_type == 'product_cat'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                         ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_category_description' THEN term_extras.key_value END) as category_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_category_img_url' THEN term_extras.key_value END) as category_img_url"), 'terms.created_at', 'terms.updated_at');          
    }
    else if($term_type == 'product_brands'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                         ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_brand_country_name' THEN term_extras.key_value END) as brand_country_name"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_short_description' THEN term_extras.key_value END) as brand_short_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_logo_img_url' THEN term_extras.key_value END) as brand_logo_img_url"), 'terms.created_at', 'terms.updated_at');
    }
    else if($term_type == 'product_tag'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                         ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_tag_description' THEN term_extras.key_value END) as tag_description"), 'terms.created_at', 'terms.updated_at');
    }
    else if($term_type == 'product_attr'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                         ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_product_attr_values' THEN term_extras.key_value END) as product_attr_values"), 'terms.created_at', 'terms.updated_at');
    }
    else if($term_type == 'product_colors'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                          ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_product_color_code' THEN term_extras.key_value END) as color_code"), 'terms.created_at', 'terms.updated_at');
    }
    else{
      $get_term_data = $get_term_data->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', 'terms.created_at', 'terms.updated_at');
    }

    if($pagination){
      $term_data = $get_term_data->groupBy('terms.term_id')
                                 ->paginate(10);
    }
    else{
      $term_data = $get_term_data->groupBy('terms.term_id')
                                 ->get()
                                 ->toArray();
    }

    return $term_data;
  }
  
  /**
   * 
   * Get term data by term id
   *
   * @param term id
   * @param term id required
   * @return array
   */
	public function getTermDataById( $term_id ){
    $term_data = array();

    $get_term = Term::where('term_id', $term_id)
                      ->pluck('type')
                      ->first();

    $get_term_data = DB::table('terms')
                         ->where(['terms.term_id' => $term_id])
                         ->orderBy('terms.term_id', 'desc');


    if(!empty($get_term) && (($get_term == 'blog_cat') || ($get_term == 'product_cat'))){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                                     ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_category_description' THEN term_extras.key_value END) as category_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_category_img_url' THEN term_extras.key_value END) as category_img_url"), 'terms.created_at', 'terms.updated_at'); 
      $data = $get_term_data->groupBy('term_extras.term_id')->get();                               
    }
    else if($get_term == 'product_brands'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                                     ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_brand_country_name' THEN term_extras.key_value END) as brand_country_name"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_short_description' THEN term_extras.key_value END) as brand_short_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_logo_img_url' THEN term_extras.key_value END) as brand_logo_img_url"), 'terms.created_at', 'terms.updated_at');
      $data = $get_term_data->groupBy('term_extras.term_id')->get();                               
    }
    else if($get_term == 'product_tag'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                                     ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_tag_description' THEN term_extras.key_value END) as tag_description"), 'terms.created_at', 'terms.updated_at');
      $data = $get_term_data->groupBy('term_extras.term_id')->get();
    }
    else if($get_term == 'product_attr'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                                     ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_product_attr_values' THEN term_extras.key_value END) as product_attr_values"), 'terms.created_at', 'terms.updated_at');
      $data = $get_term_data->groupBy('term_extras.term_id')->get();
    }
    else if($get_term == 'product_colors'){
      $get_term_data = $get_term_data->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                                     ->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_product_color_code' THEN term_extras.key_value END) as color_code"), 'terms.created_at', 'terms.updated_at');
      $data = $get_term_data->groupBy('term_extras.term_id')->get();
    }
    else{
      $get_term_data = $get_term_data->select('terms.term_id', 'terms.name', 'terms.slug', 'terms.type', 'terms.parent', 'terms.status', 'terms.created_at', 'terms.updated_at');
      $data = $get_term_data->get();
    }
    
    $term_data = collect($data)->map(function($x){ return (array) $x; })->toArray(); 

    return $term_data;
  }
  
  /**
   * 
   * Get function for categories name
   *
   * @param cat type
   * @return array
   */
  public function get_categories_name($cat_type){
    $cat_list_array   =   array();
    $get_cats         =   Term:: where(['type' => $cat_type, 'status' => 1])
                          ->get()
                          ->toArray();
    
    if(count($get_cats) > 0){
      foreach($get_cats as $row){     
        $cat_list['id']   = $row['term_id'];
        $cat_list['name'] = $row['name'];
        $cat_list['slug'] = $row['slug'];
        $cat_list_array[] = $cat_list;
      }
    }
    
    return $cat_list_array;
  }
  
  /**
   * 
   * Get function for categories name for list
   *
   * @param cat type
   * @return array
   */
  public function get_categories_name_for_list($cat_type){
    $cat_list_array   =   array();
    $get_cats         =   Term:: where(['type' => $cat_type])
                          ->get()
                          ->toArray();
    
    if(count($get_cats) > 0){
      foreach($get_cats as $row){     
        $cat_list['id']   = $row['term_id'];
        $cat_list['name'] = $row['name'];
        $cat_list['slug'] = $row['slug'];
        $cat_list_array[] = $cat_list;
      }
    }
    
    return $cat_list_array;
  }
		
	/**
   * Get function for parent and child categories
   *
   * @param cat id, cat type
   * @return array
   */
  public function get_categories($cat_id = 0, $cat_type){
    $categories = array();

    $get_categories = DB::table('terms')
                      ->where('terms.type', '=', $cat_type)
                      ->where('terms.status', '=', 1)
                      ->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                      ->select(DB::raw('terms.term_id as id'), 'terms.name', DB::raw('terms.slug as slug'), 'terms.type', 'terms.parent', 'terms.status', DB::raw("max(CASE WHEN term_extras.key_name = '_category_description' THEN term_extras.key_value END) as description"), DB::raw("max(CASE WHEN term_extras.key_name = '_category_img_url' THEN term_extras.key_value END) as img_url"), 'terms.created_at', 'terms.updated_at')
                      ->orderBy('terms.term_id', 'desc')
                      ->groupBy('terms.term_id')
                      ->get()
                      ->toArray(); 

    if(is_array($get_categories) && count($get_categories) > 0){
      $categories = $this->buildTree(json_decode(json_encode($get_categories), true), 0); 
    }
          
    return $categories; 
  }

  /**
   * Get function for create category tree
   *
   * @param array, parent_id
   * @return array
   */ 
  public function buildTree(array $elements, $parentId = 0) {
    $branch = array();

    foreach ($elements as $element) {
      if ($element['parent'] == $parentId) {
          $children = $this->buildTree($elements, $element['id']);

          if ($children) {
            $element['children'] = $children;
          }
          else{
            $element['children'] = array();
          }

          $branch[] = $element;
      }
    }

    return $branch;
  }
		
	/**
   * Get function for parent categories
   *
   * @param cat id, cat type
   * @return array
   */
  public function get_parent_categories($cat_id = 0, $cat_type){
		$parent_categories = array();
    $get_categories_data  =  Term::where(['type' => $cat_type, 'parent' => $cat_id, 'status' => 1])->get()->toArray();
				
    if(count($get_categories_data) > 0){
        $parent_categories = $get_categories_data;
    }

    return $parent_categories;
	}
  
  /**
   * Get function for all categories array list
   *
   * @param cat array
   * @return array
   */
  public function createCategoriesSimpleList($cat_arr = array()){
    if(count($cat_arr) > 0){
      foreach($cat_arr as $cat){
        $cat_data['id'] = $cat['id'];
        $cat_data['name'] = $cat['name'];
        $cat_data['slug'] = $cat['slug'];
        $cat_data['parent'] = $cat['parent'];
        $cat_data['description'] = $cat['description'];
        $cat_data['img_url'] = $cat['img_url'];
        
        array_push($this->cat_list_arr, $cat_data);
        
        if(count($cat['children']) >0){
          $this->categoriesSimpleListExtra($cat['children']);
        }
      }
    }
  
    return $this->cat_list_arr;
	}
  
  /**
   * Get function for all categories list extra
   *
   * @param cat array
   * @return array
   */
  public function categoriesSimpleListExtra($cat_arr = array()){
    if(count($cat_arr) > 0){
      foreach($cat_arr as $cat){
        $cat_data['id'] = $cat['id'];
        $cat_data['name'] = $cat['name'];
        $cat_data['slug'] = $cat['slug'];
        $cat_data['parent'] = $cat['parent'];
        $cat_data['description'] = $cat['description'];
        $cat_data['img_url'] = $cat['img_url'];
        
        array_push($this->cat_list_arr, $cat_data);
        
        if(count($cat['children']) >0){
          $this->categoriesSimpleListExtra($cat['children']);
        }
      }
    }
	}
  
  /**
   * 
   * Save products
   *
   * @param product slug
	 * @param product slug by default null
   * @return void
   */
  public function saveProduct($params = null){
    if( Request::isMethod('post') && Session::token() == Request::Input('_token')){
      
      //vendor product add restricted
      if(is_vendor_login() && Session::has('shopist_admin_user_id') &&  Request::Input('hf_post_type') == 'add_post'){
        $selected_package_details = get_package_details_by_vendor_id( Session::get('shopist_admin_user_id') );
        $get_total_product = Product::where(['author_id' => Session::get('shopist_admin_user_id')])->get()->count();
        
        if(!empty($get_total_product) && $get_total_product > 0){
          $count = $get_total_product + 1;
          
          if($count > $selected_package_details->max_number_product){
            Session::flash('error-message', Lang::get('admin.vendor_product_exceed_msg', ['number' => $selected_package_details->max_number_product]) );
            return redirect()-> back();
          }
        }
      }
      
      $data = Request::Input();

      $rules =  ['product_name'  => 'required'];

      $validator = Validator:: make($data, $rules);
      
      if($validator->fails()){
        return redirect()-> back()
        ->withInput()
        ->withErrors( $validator );
      }
      else{
        $product_id = 0;

        //downloadable product file manage
        $downloadable_product_data = array();
        $file_name = Request::Input('downloadable_file_name');
        $uploaded_file_url = Request::Input('downloadable_uploaded_file_url');
        $online_file_url = Request::Input('downloadable_online_file_url');

        if(Request::has('downloadable_file_name') && Request::has('downloadable_uploaded_file_url') && Request::has('downloadable_online_file_url') && count($file_name) > 0 && count($uploaded_file_url) > 0 && count($online_file_url)){
          foreach($file_name as $key => $name){
            $url = str_replace(url('/'), '', $uploaded_file_url[$key]);
            $downloadable_product_data[$key] = array('file_name' => $name, 'uploaded_file_url' => $url, 'online_file_url' => $online_file_url[$key]);
          }
        }

        $download_limit = '';
        $download_expiry = '';

        if(Request::Input('download_limit')){
          $download_limit = Request::Input('download_limit');
        }


        //role based pricing
        $role_price = array();
        $available_user_role = get_available_user_roles();

        if(count($available_user_role) > 0 && Request::has('RoleRegularPricing') && Request::has('RoleSalePricing')){
          $role_based_regular_pricing = Request::Input('RoleRegularPricing');
          $role_based_sale_pricing =  Request::Input('RoleSalePricing');


          foreach($role_based_regular_pricing as $key=> $role){
            $role_sale_price = '';
            $role_regular_price = $role;

            if($role_regular_price){
              $role_sale_price = $role_based_sale_pricing[$key];
            }

            $role_price[$key] = array('regular_price' => $role_regular_price, 'sale_price' => $role_sale_price);
          }
        }
        
        //manage cross sell and upsell products
        $selected_upsell_products = array();
        $selected_crosssell_products = array();
        
        $get_selected_upsell_product = json_decode(Request::Input('selected_upsell_products'));
        $get_selected_crosssell_product = json_decode(Request::Input('selected_crosssell_products'));
        
        if(!empty($get_selected_upsell_product) && count($get_selected_upsell_product) > 0){
          foreach($get_selected_upsell_product as $upsell_products){
            $explod_val = explode('#', $upsell_products);
            $get_id = end($explod_val);
            array_push($selected_upsell_products, $get_id);
          }
        }
        
        if(!empty($get_selected_crosssell_product) && count($get_selected_crosssell_product) > 0){
          foreach($get_selected_crosssell_product as $crosssell_products){
            $explod_val = explode('#', $crosssell_products);
            $get_id = end($explod_val);
            array_push($selected_crosssell_products, $get_id);
          }
        }
        
        
        if(!empty($params)){
          $get_post =  Product :: where('slug', $params)->get()->toArray();

          if(count($get_post) > 0){
            $product_id  =  $get_post[0]['id'];
          }
        }
        
        $price          = '';
        $regular_price  = '';
        $sale_price     = '';
        $stock_qty      = 0;
        $sale_price_start_date = '';
        $sale_price_end_date   = '';
        $stock_availability    = ''; 

        if(is_numeric(Request::Input('inputRegularPrice')) && Request::has('inputRegularPrice')){
          $regular_price = Request::Input('inputRegularPrice');
        }

        if(is_numeric(Request::Input('inputSalePrice')) && Request::has('inputRegularPrice')){
          $sale_price = Request::Input('inputSalePrice');
        }
 
        
        if(($regular_price && $sale_price) && (abs($sale_price) < abs($regular_price)) && $sale_price > 0){
          $price = Request::Input('inputSalePrice');
        }
        else{
          $price = Request::Input('inputRegularPrice');
        }

        $today = date("Y-m-d");

        if(Request::Input('inputSalePriceStartDate') >= $today){
          $sale_price_start_date = Request::Input('inputSalePriceStartDate');
        }

        if(Request::Input('inputSalePriceEndDate') >= $today){
          $sale_price_end_date = Request::Input('inputSalePriceEndDate');
        }

        if(Request::Input('download_expiry') >= $today){
          $download_expiry = Request::Input('download_expiry');
        }

        if(is_numeric(Request::Input('inputStockQty'))){
          $stock_qty = Request::Input('inputStockQty');
        }


        $manage_stock         = 'yes';
        $enable_recommended   = 'yes';
        $enable_features      = 'yes';
        $enable_latest        = 'yes';
        $enable_related       = 'yes';
        $enable_custom_design = 'yes';
        $enable_taxes         = 'yes';
        $enable_review        = 'yes';
        $enable_p_page        = 'yes';
        $enable_d_page        = 'yes';
        $enable_review_totals = 'yes';
        $enable_product_video = 'yes';
        $enable_manufacturer  = 'yes';
        $visibilityschedule   = 'yes';
        $home_page_product    = 'yes';
        $is_pricing_enable    = 'no';

        $_stock           = (Request::has('manage_stock')) ? true : false;
        $_recommended     = (Request::has('enable_recommended_product')) ? true : false;
        $_features        = (Request::has('enable_features_product')) ? true : false;
        $_latest          = (Request::has('enable_latest_product')) ? true : false;
        $_related         = (Request::has('inputEnableForRelatedProduct')) ? true : false;
        $_custom_design   = (Request::has('inputEnableForCustomDesignProduct')) ? true : false;
        $_taxes           = (Request::has('inputEnableTaxesForProduct')) ? true : false;
        $_review          = (Request::has('inputEnableReviews')) ? true : false;
        $_review_p_page   = (Request::has('inputEnableAddReviewLinkToProductPage')) ? true : false;
        $_review_d_page   = (Request::has('inputEnableAddReviewLinkToDetailsPage')) ? true : false;
        $_product_video   = (Request::has('inputEnableProductVideo')) ? true : false;
        $_manufacturer    = (Request::has('inputEnableProductManufacturer')) ? true : false;
        $_visibility      = (Request::has('inputVisibilitySchedule')) ? true : false;
        $_home_product    = (Request::has('inputEnableForHomePage')) ? true : false;
        $_is_role_based_pricing_enable    = (Request::has('inputEnableDisableRoleBasedPricing')) ? true : false;


        if($_stock){
          $manage_stock = 'yes';
        }
        else{
          $manage_stock = 'no';
        }

        if($_recommended){
          $enable_recommended = 'yes';
        }
        else{
          $enable_recommended = 'no';
        }

        if($_features){
          $enable_features = 'yes';
        }
        else{
          $enable_features = 'no';
        }

        if($_latest){
          $enable_latest = 'yes';
        }
        else{
          $enable_latest = 'no';
        }

        if($_related){
          $enable_related = 'yes';
        }
        else{
          $enable_related = 'no';
        }

        if($_custom_design && Request::Input('change_product_type') == 'customizable_product'){
          $enable_custom_design = 'yes';
        }
        else{
          $enable_custom_design = 'yes';
        }

        if($_taxes){
          $enable_taxes = 'yes';
        }
        else{
          $enable_taxes = 'no';
        }

        if($_review){
          $enable_review = 'yes';
        }
        else{
          $enable_review = 'no';
        }

        if($_review_p_page){
          $enable_p_page = 'yes';
        }
        else{
          $enable_p_page = 'no';
        }

        if($_review_d_page){
          $enable_d_page = 'yes';
        }
        else{
          $enable_d_page = 'no';
        }

        if($_product_video){
          $enable_product_video = 'yes';
        }
        else{
          $enable_product_video = 'no';
        }

        if($_manufacturer){
          $enable_manufacturer = 'yes';
        }
        else{
          $enable_manufacturer = 'no';
        }

        if($_visibility){
          $visibilityschedule = 'yes';
        }
        else{
          $visibilityschedule = 'no';
        } 

        if($_home_product){
          $home_page_product = 'yes';
        }
        else{
          $home_page_product = 'no';
        }

        if($_is_role_based_pricing_enable){
          $is_pricing_enable = 'yes';
        }
        else{
          $is_pricing_enable = 'no';
        }

        if($manage_stock == 'yes'){
          if ($manage_stock == 'yes' && $stock_qty == 0 && Request::Input('back_to_order_status') == 'not_allow') {
            $stock_availability = 'out_of_stock';
          }
          else{
            $stock_availability = 'in_stock';
          }
        }  
        else{
          $stock_availability = Request::Input('stock_availability_status');
        }

        //designer settings 
        $cavas_small_width = 0;
        $cavas_small_height = 0;
        $cavas_medium_width = 0;
        $cavas_medium_height = 0;
        $cavas_large_width = 0;
        $cavas_large_height = 0;

        $enable_design_layout = 'no';
        $enable_global_settings = 'no';

        $get_designer_settings = $this->option->getCustomDesignerSettingsData();

        $_is_enable_design_layout     = (Request::has('inputEnableDesignLayout')) ? true : false;
        $_is_enable_global_settings   = (Request::has('inputEnableGlobalSettings')) ? true : false;


        if($_is_enable_global_settings){
          $enable_global_settings = 'yes';
        }
        else{
          $enable_global_settings = 'no';
        }

        if($_is_enable_design_layout){
          $enable_design_layout = 'yes';
        }
        else{
          $enable_design_layout = 'no';
        }


        // canvas size
        if(Request::has('specific_canvas_small_devices_width') && $enable_global_settings == 'no'){
          $cavas_small_width = Request::Input('specific_canvas_small_devices_width');
        }
        elseif ($enable_global_settings == 'yes') {
          $cavas_small_width = '';
        }
        else{
          $cavas_small_width = $get_designer_settings['general_settings']['canvas_dimension']['small_devices']['width'];
        }

        if(Request::has('specific_canvas_small_devices_height') && $enable_global_settings == 'no'){
          $cavas_small_height = Request::Input('specific_canvas_small_devices_height');
        }
        elseif ($enable_global_settings == 'yes') {
          $cavas_small_height = '';
        }
        else{
          $cavas_small_height = $get_designer_settings['general_settings']['canvas_dimension']['small_devices']['height'];
        }


        if(Request::has('specific_canvas_medium_devices_width') && $enable_global_settings == 'no'){
          $cavas_medium_width = Request::Input('specific_canvas_medium_devices_width');
        }
        elseif ($enable_global_settings == 'yes'){
          $cavas_medium_width = '';
        }
        else{
          $cavas_medium_width = $get_designer_settings['general_settings']['canvas_dimension']['medium_devices']['width'];
        }

        if(Request::has('specific_canvas_medium_devices_height') && $enable_global_settings == 'no'){
          $cavas_medium_height = Request::Input('specific_canvas_medium_devices_height');
        }
        elseif ($enable_global_settings == 'yes'){
          $cavas_medium_height = '';
        }
        else{
          $cavas_medium_height = $get_designer_settings['general_settings']['canvas_dimension']['medium_devices']['height'];
        }


        if(Request::has('specific_canvas_large_devices_width') && $enable_global_settings == 'no'){
          $cavas_large_width = Request::Input('specific_canvas_large_devices_width');
        }
        elseif ($enable_global_settings == 'yes'){
          $cavas_large_width = '';
        }
        else{
          $cavas_large_width = $get_designer_settings['general_settings']['canvas_dimension']['large_devices']['width'];
        }

        if(Request::has('specific_canvas_large_devices_height') && $enable_global_settings == 'no'){
          $cavas_large_height = Request::Input('specific_canvas_large_devices_height');
        }
        elseif ($enable_global_settings == 'yes') {
          $cavas_large_height = '';
        }
        else{
          $cavas_large_height = $get_designer_settings['general_settings']['canvas_dimension']['large_devices']['height'];
        }


        $designer_settings = array(
          'canvas_dimension' => array('small_devices' => array('width' => $cavas_small_width, 'height' => $cavas_small_height), 'medium_devices' => array('width'=> $cavas_medium_width, 'height' => $cavas_medium_height), 'large_devices' => array('width' => $cavas_large_width, 'height' => $cavas_large_height)),
          'enable_layout_at_frontend' => $enable_design_layout,
          'enable_global_settings' => $enable_global_settings
        );


        $videoSourceName = '';

        if(Request::Input('inputVideoSourceName')){
          $videoSourceName = Request::Input('inputVideoSourceName');
        }

        $page_title = '';
        $url_slug   = '';

        if(Request::has('seo_title') && !empty(Request::Input('seo_title'))){
          $page_title = Request::Input('seo_title');
        }
        else{
          $page_title = Request::Input('product_name');
        }

        if(Request::has('seo_url_format') && !empty(Request::Input('seo_url_format'))){
          $url_slug = string_slug_format(Request::Input('seo_url_format'));
        }
        else{
          $url_slug = string_slug_format(Request::Input('product_name'));
        }

        $post        =  new Product;
        $post_slug   =  '';

        $post_slug = create_unique_slug('product', $url_slug);

        $author_id = 0;
        if(Request::has('vendor_list') && !empty(Request::Input('vendor_list'))){
          $author_id = Request::Input('vendor_list');
        }
        else{
          $author_id = Session::get('shopist_admin_user_id');
        }

        $get_images = json_decode(Request::Input('hf_uploaded_all_images'));
        $product_image = $get_images->product_image;

        if(Request::Input('hf_post_type') == 'add_post'){
          $post->author_id          =   $author_id;
          $post->content            =   string_encode(Request::Input('eb_description_editor'));
          $post->title              =   strip_tags(Request::Input('product_name'));
          $post->slug               =   $post_slug;
          $post->status             =   Request::Input('product_visibility');
          $post->sku                =   strip_tags(Request::Input('ProductSKU'));
          $post->regular_price      =   $regular_price;
          $post->sale_price         =   $sale_price;
          $post->price              =   $price;
          $post->stock_qty          =   $stock_qty;
          $post->stock_availability =   $stock_availability;
          $post->type               =   Request::Input('change_product_type');
          $post->image_url          =   $product_image;

          if($post->save()){  
            if(ProductExtra::insert(array(
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_related_images_url',
                                          'key_value'     =>  Request::Input('hf_uploaded_all_images'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_sale_price_start_date',
                                          'key_value'     =>  $sale_price_start_date,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_sale_price_end_date',
                                          'key_value'     =>  $sale_price_end_date,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_manage_stock',
                                          'key_value'     =>  $manage_stock,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_manage_stock_back_to_order',
                                          'key_value'     =>  Request::Input('back_to_order_status'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_extra_features',
                                          'key_value'     =>  string_encode(Request::Input('eb_features_editor')),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_as_recommended',
                                          'key_value'     =>  $enable_recommended,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_as_features',
                                          'key_value'     =>  $enable_features,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_as_latest',
                                          'key_value'     =>  $enable_latest,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_as_related',
                                          'key_value'     =>  $enable_related,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_as_custom_design',
                                          'key_value'     =>  $enable_custom_design,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_as_selected_cat',
                                          'key_value'     =>  $home_page_product,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_taxes',
                                          'key_value'     =>  $enable_taxes,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_custom_designer_settings',
                                          'key_value'     =>  serialize($designer_settings),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_custom_designer_data',
                                          'key_value'     =>  Request::Input('hf_custom_designer_data'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_reviews',
                                          'key_value'     =>  $enable_review,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_reviews_add_link_to_product_page',
                                          'key_value'     =>  $enable_p_page,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_reviews_add_link_to_details_page',
                                          'key_value'     =>  $enable_d_page,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_video_feature',
                                          'key_value'     =>  $enable_product_video,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_video_feature_display_mode',
                                          'key_value'     =>  Request::Input('inputVideoDisplayMode'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_video_feature_title',
                                          'key_value'     =>  Request::Input('inputTitleForVideo'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_video_feature_panel_size',
                                          'key_value'     =>  serialize(array('width' => Request::Input('inputVideoPanelWidth'), 'height' => Request::Input('inputVideoPanelHeight'))),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_video_feature_source',
                                          'key_value'     =>  $videoSourceName,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_video_feature_source_embedded_code',
                                          'key_value'     =>  Request::Input('inputEmbedCode'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_video_feature_source_online_url',
                                          'key_value'     =>  Request::Input('inputAddOnlineVideoUrl'),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'meta_key'      =>  '_product_enable_manufacturer',
                                          'meta_value'    =>  $enable_manufacturer,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_enable_visibility_schedule',
                                          'key_value'     =>  $visibilityschedule,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_seo_title',
                                          'key_value'     =>  strip_tags($page_title),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_seo_description',
                                          'key_value'     =>  strip_tags(Request::Input('seo_description')),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_seo_keywords',
                                          'key_value'     =>  strip_tags(Request::Input('seo_keywords')),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_product_compare_data',
                                          'key_value'     =>  serialize(Request::Input('inputCompareData')),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_is_role_based_pricing_enable',
                                          'key_value'     =>  $is_pricing_enable,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_role_based_pricing',
                                          'key_value'     =>  serialize($role_price),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_downloadable_product_files',
                                          'key_value'     =>  serialize($downloadable_product_data),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_downloadable_product_download_limit',
                                          'key_value'     =>  $download_limit,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_downloadable_product_download_expiry',
                                          'key_value'     =>  $download_expiry,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_upsell_products',
                                          'key_value'     =>  serialize($selected_upsell_products),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_crosssell_products',
                                          'key_value'     =>  serialize($selected_crosssell_products),
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      ),
                                      array(
                                          'product_id'    =>  $post->id,
                                          'key_name'      =>  '_selected_vendor',
                                          'key_value'     =>  $author_id,
                                          'created_at'    =>  date("y-m-d H:i:s", strtotime('now')),
                                          'updated_at'    =>  date("y-m-d H:i:s", strtotime('now'))
                                      )



            ))){

            //save categories
            if(Request::has('inputCategoriesName') && count(Request::Input('inputCategoriesName'))>0){
              $cat_array = array();

              foreach(Request::Input('inputCategoriesName') as $cat_id){
                $cat_data = array('term_id'  =>  $cat_id, 'object_id'  =>  $post->id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($cat_array, $cat_data);
              }

              if(count($cat_array) > 0){
                ObjectRelationship::insert( $cat_array );    
              }
            }

            //save manufacturer
            if(Request::has('inputManufacturerName') && count(Request::Input('inputManufacturerName'))>0){
              $manufacturer_array = array();

              foreach(Request::Input('inputManufacturerName') as $brands_id){
                $manufacturer_data = array('term_id'  =>  $brands_id, 'object_id'  =>  $post->id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($manufacturer_array, $manufacturer_data);   
              }

              if(count($manufacturer_array) > 0){
                ObjectRelationship::insert( $manufacturer_array );    
              }
            }

            //save tags
            if(Request::has('inputTagsName') && count(Request::Input('inputTagsName'))>0){
              $tags_array = array();

              foreach(Request::Input('inputTagsName') as $tags_id){
                $tags_data = array('term_id'  =>  $tags_id, 'object_id'  =>  $post->id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($tags_array, $tags_data);   
              }

              if(count($tags_array) > 0){
                ObjectRelationship::insert( $tags_array );    
              }
            }

            //save colors
            if(Request::has('inputColorsName') && count(Request::Input('inputColorsName'))>0){
              $colors_array = array();

              foreach(Request::Input('inputColorsName') as $colors_id){
                $colors_data = array('term_id'  =>  $colors_id, 'object_id'  =>  $post->id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($colors_array, $colors_data);   
              }

              if(count($colors_array) > 0){
                ObjectRelationship::insert( $colors_array );    
              }
            }

            //save sizes
            if(Request::has('inputSizesName') && count(Request::Input('inputSizesName'))>0){
              $sizes_array = array();

              foreach(Request::Input('inputSizesName') as $sizes_id){
                $sizes_data = array('term_id'  =>  $sizes_id, 'object_id'  =>  $post->id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($sizes_array, $sizes_data);   
              }

              if(count($sizes_array) > 0){
                ObjectRelationship::insert( $sizes_array );    
              }
            }

            Session::flash('success-message', Lang::get('admin.successfully_saved_msg') );
            return redirect()->route('admin.update_product', $post->slug);
            }
          }
        }
        elseif (Request::Input('hf_post_type') == 'update_post'){
          $data = array(
                        'content'	           =>  string_encode(Request::Input('eb_description_editor')),
                        'title'              =>  strip_tags(Request::Input('product_name')),
                        'status'             =>  Request::Input('product_visibility'),
                        'sku'                =>  strip_tags(Request::Input('ProductSKU')),
                        'regular_price'      =>  $regular_price,
                        'sale_price'         =>  $sale_price,
                        'price'              =>  $price,
                        'stock_qty'          =>  $stock_qty,
                        'stock_availability' =>  $stock_availability,
                        'type'               =>  Request::Input('change_product_type'),
                        'image_url'          =>  $product_image
          );
          if( Product::where('id', $product_id)->update($data)){
            $data_related_url = array(
                              'key_value'    =>  Request::Input('hf_uploaded_all_images')
            );

            $data_sale_price_start_date = array(
                              'key_value'    =>  $sale_price_start_date,
            );

            $data_sale_price_end_date = array(
                              'key_value'    =>  $sale_price_end_date,
            );

            $data_manage_stock = array(
                              'key_value'    =>  $manage_stock
            );

            $data_manage_stock_back_to_order = array(
                              'key_value'    =>  Request::Input('back_to_order_status')
            );

            $data_extra_features = array(
                              'key_value'    =>  string_encode(Request::Input('eb_features_editor'))
            );

            $data_enable_recommended = array(
                              'key_value'    =>  $enable_recommended
            );

            $data_enable_features = array(
                              'key_value'    =>  $enable_features
            );

            $data_enable_latest = array(
                              'key_value'    =>  $enable_latest
            );

            $data_enable_related = array(
                              'key_value'    =>  $enable_related
            );

            $data_enable_custom_design = array(
                              'key_value'    =>  $enable_custom_design
            );

            $data_enable_home_product = array(
                              'key_value'    =>  $home_page_product
            );

            $data_enable_taxes = array(
                              'key_value'    =>  $enable_taxes
            );

            $data_custom_designer_settings = array(
                              'key_value'    =>  serialize($designer_settings)
            );

            $data_custom_designer_data = array(
                              'key_value'    =>  Request::Input('hf_custom_designer_data')
            );

            $data_enable_review = array(
                              'key_value'    =>  $enable_review
            );

            $data_p_page = array(
                              'key_value'    =>  $enable_p_page
            );

            $data_d_page = array(
                              'key_value'    =>  $enable_d_page
            );

            $data_enable_product_video = array(
                              'key_value'    =>  $enable_product_video
            );

            $data_display_mode = array(
                              'key_value'    =>  Request::Input('inputVideoDisplayMode')
            );

            $data_title_for_video = array(
                              'key_value'    =>  Request::Input('inputTitleForVideo')
            );

            $data_video_feature_panel_size = array(
                              'key_value'    =>  serialize(array('width' => Request::Input('inputVideoPanelWidth'), 'height' => Request::Input('inputVideoPanelHeight')))
            );

            $data_video_source_name = array(
                              'key_value'    =>  $videoSourceName
            );

            $data_video_embed_code = array(
                              'key_value'    =>  Request::Input('inputEmbedCode')
            );

            $data_video_online_url = array(
                              'key_value'    =>  Request::Input('inputAddOnlineVideoUrl')
            );

            $data_enable_manufacturer = array(
                              'key_value'    =>  $enable_manufacturer
            );

            $data_visibilityschedule = array(
                              'key_value'    =>  $visibilityschedule
            );

            $data_seo_title = array(
                              'key_value'    =>  strip_tags($page_title)
            );

            $data_seo_description = array(
                              'key_value'    =>  strip_tags(Request::Input('seo_description'))
            );

            $data_seo_keywords = array(
                              'key_value'    =>  strip_tags(Request::Input('seo_keywords'))
            );

            $data_compare_product = array(
                              'key_value'    => serialize(Request::Input('inputCompareData'))
            );

            $data_is_role_based_enable = array(
                              'key_value'    => $is_pricing_enable
            );

            $data_role_based_pricing = array(
                              'key_value'    => serialize($role_price)
            );

            $data_downloadable_product_files = array(
                              'key_value'    => serialize($downloadable_product_data)
            );

            $data_downloadable_product_download_limit = array(
                              'key_value'    => $download_limit
            );

            $data_downloadable_product_download_expiry = array(
                              'key_value'    => $download_expiry
            );

            $upsell_selected_product = array(
                              'key_value'    => serialize($selected_upsell_products)
            );

            $crosssell_selected_product = array(
                              'key_value'    => serialize($selected_crosssell_products)
            );

            $selected_vendor = array(
                              'key_value'    => $author_id
            );


            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_related_images_url'])->update($data_related_url);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_sale_price_start_date'])->update($data_sale_price_start_date);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_sale_price_end_date'])->update($data_sale_price_end_date);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_manage_stock'])->update($data_manage_stock);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_manage_stock_back_to_order'])->update($data_manage_stock_back_to_order);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_extra_features'])->update($data_extra_features);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_as_recommended'])->update($data_enable_recommended);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_as_features'])->update($data_enable_features);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_as_latest'])->update($data_enable_latest);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_as_related'])->update($data_enable_related);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_as_custom_design'])->update($data_enable_custom_design);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_as_selected_cat'])->update($data_enable_home_product);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_taxes'])->update($data_enable_taxes);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_custom_designer_settings'])->update($data_custom_designer_settings);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_custom_designer_data'])->update($data_custom_designer_data);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_reviews'])->update($data_enable_review);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_reviews_add_link_to_product_page'])->update($data_p_page);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_reviews_add_link_to_details_page'])->update($data_d_page);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_video_feature'])->update($data_enable_product_video);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_video_feature_display_mode'])->update($data_display_mode);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_video_feature_title'])->update($data_title_for_video);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_video_feature_panel_size'])->update($data_video_feature_panel_size);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_video_feature_source'])->update($data_video_source_name);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_video_feature_source_embedded_code'])->update($data_video_embed_code);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_video_feature_source_online_url'])->update($data_video_online_url);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_manufacturer'])->update($data_enable_manufacturer);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_enable_visibility_schedule'])->update($data_visibilityschedule);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_seo_title'])->update($data_seo_title);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_seo_description'])->update($data_seo_description);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_seo_keywords'])->update($data_seo_keywords);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_product_compare_data'])->update($data_compare_product);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_is_role_based_pricing_enable'])->update($data_is_role_based_enable);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_role_based_pricing'])->update($data_role_based_pricing);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_downloadable_product_files'])->update($data_downloadable_product_files);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_downloadable_product_download_limit'])->update($data_downloadable_product_download_limit);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_downloadable_product_download_expiry'])->update($data_downloadable_product_download_expiry);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_selected_vendor'])->update($selected_vendor);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_upsell_products'])->update($upsell_selected_product);
            ProductExtra::where(['product_id' => $product_id, 'key_name' => '_crosssell_products'])->update($crosssell_selected_product);

            $is_object_exist = ObjectRelationship::where('object_id', $product_id)->get();

            if(count($is_object_exist)>0){
              ObjectRelationship::where('object_id', $product_id)->delete();
            }

            //save categories
            if(Request::has('inputCategoriesName') && count(Request::Input('inputCategoriesName'))>0){
              $cat_array = array();

              foreach(Request::Input('inputCategoriesName') as $cat_id){
                $cat_data = array('term_id'  =>  $cat_id, 'object_id'  => $product_id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($cat_array, $cat_data);
              }

              if(count($cat_array) > 0){
                ObjectRelationship::insert( $cat_array );    
              }
            }

            //save manufacturer
            if(Request::has('inputManufacturerName') && count(Request::Input('inputManufacturerName'))>0){
              $manufacturer_array = array();

              foreach(Request::Input('inputManufacturerName') as $brands_id){
                $manufacturer_data = array('term_id'  => $brands_id, 'object_id'  => $product_id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($manufacturer_array, $manufacturer_data);   
              }

              if(count($manufacturer_array) > 0){
                ObjectRelationship::insert( $manufacturer_array );    
              }
            }

            //save tags
            if(Request::has('inputTagsName') && count(Request::Input('inputTagsName'))>0){
              $tags_array = array();

              foreach(Request::Input('inputTagsName') as $tags_id){
                $tags_data = array('term_id'  => $tags_id, 'object_id'  => $product_id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($tags_array, $tags_data);   
              }

              if(count($tags_array) > 0){
                ObjectRelationship::insert( $tags_array );    
              }
            }

            //save colors
            if(Request::has('inputColorsName') && count(Request::Input('inputColorsName'))>0){
              $colors_array = array();

              foreach(Request::Input('inputColorsName') as $colors_id){
                $colors_data = array('term_id'  => $colors_id, 'object_id'  => $product_id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($colors_array, $colors_data);   
              }

              if(count($colors_array) > 0){
                ObjectRelationship::insert( $colors_array );    
              }
            }

            //save sizes
            if(Request::has('inputSizesName') && count(Request::Input('inputSizesName'))>0){
              $sizes_array = array();

              foreach(Request::Input('inputSizesName') as $sizes_id){
                $sizes_data = array('term_id'  => $sizes_id, 'object_id'  => $product_id, 'created_at'  =>  date("y-m-d H:i:s", strtotime('now')), 'updated_at'  =>  date("y-m-d H:i:s", strtotime('now')));

                array_push($sizes_array, $sizes_data);   
              }

              if(count($sizes_array) > 0){
                ObjectRelationship::insert( $sizes_array );    
              }
            }

            Session::flash('success-message', Lang::get('admin.successfully_updated_msg' ));
            return redirect()->route('admin.update_product', $params);
          }
        }
      }  
    }
    else{
      return redirect()-> back();
    }
  }
  
  /**
   * Get function for products data
   *
   * @param products id
   * @param products id required
   * @return array
   */
  public function getProductDataById( $product_id ){
    $get_product = array();

    $get_product = DB::table('products')
                       ->where(['products.id' => $product_id]) 
                       ->join('product_extras', 'products.id', '=', 'product_extras.product_id')
                       ->select('products.id', 'products.author_id', DB::raw('products.content as post_content'), DB::raw('products.title as post_title'), DB::raw('products.slug as post_slug'), DB::raw('products.status as post_status'), DB::raw('products.sku as post_sku'),  DB::raw('products.regular_price as post_regular_price'), DB::raw('products.sale_price as post_sale_price'), DB::raw('products.price as post_price'), DB::raw('products.stock_qty as post_stock_qty'), DB::raw('products.stock_availability as post_stock_availability'),  DB::raw('products.type as post_type'), DB::raw('products.image_url as post_image_url'), DB::raw("max(CASE WHEN product_extras.key_name = '_product_related_images_url' THEN product_extras.key_value END) as _product_related_images_url"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_related_images_url' THEN product_extras.key_value END) as product_related_img_json"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_sale_price_start_date' THEN product_extras.key_value END) as _product_sale_price_start_date"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_sale_price_end_date' THEN product_extras.key_value END) as _product_sale_price_end_date"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_manage_stock' THEN product_extras.key_value END) as _product_manage_stock"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_manage_stock_back_to_order' THEN product_extras.key_value END) as _product_manage_stock_back_to_order"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_extra_features' THEN product_extras.key_value END) as _product_extra_features"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_recommended' THEN product_extras.key_value END) as _product_enable_as_recommended"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_latest' THEN product_extras.key_value END) as _product_enable_as_latest"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_related' THEN product_extras.key_value END) as _product_enable_as_related"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_custom_design' THEN product_extras.key_value END) as _product_enable_as_custom_design"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_selected_cat' THEN product_extras.key_value END) as _product_enable_as_selected_cat"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_taxes' THEN product_extras.key_value END) as _product_enable_taxes"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_custom_designer_settings' THEN product_extras.key_value END) as _product_custom_designer_settings"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_custom_designer_data' THEN product_extras.key_value END) as _product_custom_designer_data"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_custom_designer_data' THEN product_extras.key_value END) as product_custom_designer_json"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_reviews' THEN product_extras.key_value END) as _product_enable_reviews"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_reviews_add_link_to_product_page' THEN product_extras.key_value END) as _product_enable_reviews_add_link_to_product_page"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_reviews_add_link_to_details_page' THEN product_extras.key_value END) as _product_enable_reviews_add_link_to_details_page"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_video_feature' THEN product_extras.key_value END) as _product_enable_video_feature"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_display_mode' THEN product_extras.key_value END) as _product_video_feature_display_mode"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_title' THEN product_extras.key_value END) as _product_video_feature_title"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_panel_size' THEN product_extras.key_value END) as _product_video_feature_panel_size"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_source' THEN product_extras.key_value END) as _product_video_feature_source"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_source_embedded_code' THEN product_extras.key_value END) as _product_video_feature_source_embedded_code"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_source_online_url' THEN product_extras.key_value END) as _product_video_feature_source_online_url"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_manufacturer' THEN product_extras.key_value END) as _product_enable_manufacturer"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_visibility_schedule' THEN product_extras.key_value END) as _product_enable_visibility_schedule"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_seo_title' THEN product_extras.key_value END) as _product_seo_title"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_seo_description' THEN product_extras.key_value END) as _product_seo_description"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_seo_keywords' THEN product_extras.key_value END) as _product_seo_keywords"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_compare_data' THEN product_extras.key_value END) as _product_compare_data"), DB::raw("max(CASE WHEN product_extras.key_name = '_is_role_based_pricing_enable' THEN product_extras.key_value END) as _is_role_based_pricing_enable"), DB::raw("max(CASE WHEN product_extras.key_name = '_role_based_pricing' THEN product_extras.key_value END) as _role_based_pricing"), DB::raw("max(CASE WHEN product_extras.key_name = '_downloadable_product_files' THEN product_extras.key_value END) as _downloadable_product_files"), DB::raw("max(CASE WHEN product_extras.key_name = '_downloadable_product_download_limit' THEN product_extras.key_value END) as _downloadable_product_download_limit"), DB::raw("max(CASE WHEN product_extras.key_name = '_downloadable_product_download_expiry' THEN product_extras.key_value END) as _downloadable_product_download_expiry"), DB::raw("max(CASE WHEN product_extras.key_name = '_upsell_products' THEN product_extras.key_value END) as _upsell_products"), DB::raw("max(CASE WHEN product_extras.key_name = '_crosssell_products' THEN product_extras.key_value END) as _crosssell_products"), DB::raw("max(CASE WHEN product_extras.key_name = '_selected_vendor' THEN product_extras.key_value END) as _selected_vendor"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_features' THEN product_extras.key_value END) as _product_enable_as_features"))
                       ->get()
                       ->toArray();

    if(is_array($get_product) && count($get_product) > 0){
      if(!empty($get_product[0]->_product_related_images_url)){
        $get_product[0]->_product_related_images_url = json_decode($get_product[0]->_product_related_images_url); 
      }

      if(!empty($get_product[0]->_product_custom_designer_panel_size)){
        $get_product[0]->_product_custom_designer_panel_size = unserialize($get_product[0]->_product_custom_designer_panel_size); 
      }

      if(!empty($get_product[0]->_product_video_feature_panel_size)){
        $get_product[0]->_product_video_feature_panel_size = unserialize($get_product[0]->_product_video_feature_panel_size); 
      }

      if(!empty($get_product[0]->_product_selected_categories)){
        $get_product[0]->_product_selected_categories = unserialize($get_product[0]->_product_selected_categories); 
      }

      if(!empty($get_product[0]->_product_selected_tags)){
        $get_product[0]->_product_selected_tags = unserialize($get_product[0]->_product_selected_tags); 
      }

      if(!empty($get_product[0]->_product_custom_designer_data)){
        $get_product[0]->_product_custom_designer_data = json_decode($get_product[0]->_product_custom_designer_data); 
      }

      if(!empty($get_product[0]->_product_custom_designer_settings)){
        $get_product[0]->_product_custom_designer_settings = unserialize($get_product[0]->_product_custom_designer_settings); 
      }

      if(!empty($get_product[0]->_product_compare_data)){
        $get_product[0]->_product_compare_data = unserialize($get_product[0]->_product_compare_data); 
      }

      if(!empty($get_product[0]->_product_color_filter_data)){
        $get_product[0]->_product_color_filter_data = unserialize($get_product[0]->_product_color_filter_data); 
      }

      if(!empty($get_product[0]->_role_based_pricing)){
        $get_product[0]->_role_based_pricing = unserialize($get_product[0]->_role_based_pricing); 
      }

      if(!empty($get_product[0]->_downloadable_product_files)){
        $get_product[0]->_downloadable_product_files = unserialize($get_product[0]->_downloadable_product_files); 
      }

      $get_product = (array) array_shift($get_product);
    }                   

    return $get_product;                 
  }

  /**
   * Get function for products data
   *
   * @param products handle
   * @param products handle required
   * @return array
   */
  public function getProductDataByHandle( $handle ){
    $get_product = array();

    $get_product = DB::table('products')
                       ->where(['products.slug' => $handle]) 
                       ->join('product_extras', 'products.id', '=', 'product_extras.product_id')
                       ->select('products.id', 'products.author_id', DB::raw('products.content as post_content'), DB::raw('products.title as post_title'), DB::raw('products.slug as post_slug'), DB::raw('products.status as post_status'), DB::raw('products.sku as post_sku'),  DB::raw('products.regular_price as post_regular_price'), DB::raw('products.sale_price as post_sale_price'), DB::raw('products.price as post_price'), DB::raw('products.stock_qty as post_stock_qty'), DB::raw('products.stock_availability as post_stock_availability'),  DB::raw('products.type as post_type'), DB::raw('products.image_url as post_image_url'), DB::raw("max(CASE WHEN product_extras.key_name = '_product_related_images_url' THEN product_extras.key_value END) as _product_related_images_url"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_related_images_url' THEN product_extras.key_value END) as product_related_img_json"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_sale_price_start_date' THEN product_extras.key_value END) as _product_sale_price_start_date"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_sale_price_end_date' THEN product_extras.key_value END) as _product_sale_price_end_date"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_manage_stock' THEN product_extras.key_value END) as _product_manage_stock"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_manage_stock_back_to_order' THEN product_extras.key_value END) as _product_manage_stock_back_to_order"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_extra_features' THEN product_extras.key_value END) as _product_extra_features"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_recommended' THEN product_extras.key_value END) as _product_enable_as_recommended"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_latest' THEN product_extras.key_value END) as _product_enable_as_latest"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_related' THEN product_extras.key_value END) as _product_enable_as_related"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_custom_design' THEN product_extras.key_value END) as _product_enable_as_custom_design"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_selected_cat' THEN product_extras.key_value END) as _product_enable_as_selected_cat"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_taxes' THEN product_extras.key_value END) as _product_enable_taxes"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_custom_designer_settings' THEN product_extras.key_value END) as _product_custom_designer_settings"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_custom_designer_data' THEN product_extras.key_value END) as _product_custom_designer_data"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_custom_designer_data' THEN product_extras.key_value END) as product_custom_designer_json"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_reviews' THEN product_extras.key_value END) as _product_enable_reviews"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_reviews_add_link_to_product_page' THEN product_extras.key_value END) as _product_enable_reviews_add_link_to_product_page"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_reviews_add_link_to_details_page' THEN product_extras.key_value END) as _product_enable_reviews_add_link_to_details_page"),  DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_video_feature' THEN product_extras.key_value END) as _product_enable_video_feature"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_display_mode' THEN product_extras.key_value END) as _product_video_feature_display_mode"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_title' THEN product_extras.key_value END) as _product_video_feature_title"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_panel_size' THEN product_extras.key_value END) as _product_video_feature_panel_size"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_source' THEN product_extras.key_value END) as _product_video_feature_source"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_source_embedded_code' THEN product_extras.key_value END) as _product_video_feature_source_embedded_code"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_video_feature_source_online_url' THEN product_extras.key_value END) as _product_video_feature_source_online_url"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_manufacturer' THEN product_extras.key_value END) as _product_enable_manufacturer"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_visibility_schedule' THEN product_extras.key_value END) as _product_enable_visibility_schedule"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_seo_title' THEN product_extras.key_value END) as _product_seo_title"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_seo_description' THEN product_extras.key_value END) as _product_seo_description"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_seo_keywords' THEN product_extras.key_value END) as _product_seo_keywords"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_compare_data' THEN product_extras.key_value END) as _product_compare_data"), DB::raw("max(CASE WHEN product_extras.key_name = '_is_role_based_pricing_enable' THEN product_extras.key_value END) as _is_role_based_pricing_enable"), DB::raw("max(CASE WHEN product_extras.key_name = '_role_based_pricing' THEN product_extras.key_value END) as _role_based_pricing"), DB::raw("max(CASE WHEN product_extras.key_name = '_downloadable_product_files' THEN product_extras.key_value END) as _downloadable_product_files"), DB::raw("max(CASE WHEN product_extras.key_name = '_downloadable_product_download_limit' THEN product_extras.key_value END) as _downloadable_product_download_limit"), DB::raw("max(CASE WHEN product_extras.key_name = '_downloadable_product_download_expiry' THEN product_extras.key_value END) as _downloadable_product_download_expiry"), DB::raw("max(CASE WHEN product_extras.key_name = '_upsell_products' THEN product_extras.key_value END) as _upsell_products"), DB::raw("max(CASE WHEN product_extras.key_name = '_crosssell_products' THEN product_extras.key_value END) as _crosssell_products"), DB::raw("max(CASE WHEN product_extras.key_name = '_selected_vendor' THEN product_extras.key_value END) as _selected_vendor"), DB::raw("max(CASE WHEN product_extras.key_name = '_product_enable_as_features' THEN product_extras.key_value END) as _product_enable_as_features"))
                       ->get()
                       ->toArray();

    if(is_array($get_product) && count($get_product) > 0){
      if(!empty($get_product[0]->_product_related_images_url)){
        $get_product[0]->_product_related_images_url = json_decode($get_product[0]->_product_related_images_url); 
      }

      if(!empty($get_product[0]->_product_custom_designer_panel_size)){
        $get_product[0]->_product_custom_designer_panel_size = unserialize($get_product[0]->_product_custom_designer_panel_size); 
      }

      if(!empty($get_product[0]->_product_video_feature_panel_size)){
        $get_product[0]->_product_video_feature_panel_size = unserialize($get_product[0]->_product_video_feature_panel_size); 
      }

      if(!empty($get_product[0]->_product_selected_categories)){
        $get_product[0]->_product_selected_categories = unserialize($get_product[0]->_product_selected_categories); 
      }

      if(!empty($get_product[0]->_product_selected_tags)){
        $get_product[0]->_product_selected_tags = unserialize($get_product[0]->_product_selected_tags); 
      }

      if(!empty($get_product[0]->_product_custom_designer_data)){
        $get_product[0]->_product_custom_designer_data = json_decode($get_product[0]->_product_custom_designer_data); 
      }

      if(!empty($get_product[0]->_product_custom_designer_settings)){
        $get_product[0]->_product_custom_designer_settings = unserialize($get_product[0]->_product_custom_designer_settings); 
      }

      if(!empty($get_product[0]->_product_compare_data)){
        $get_product[0]->_product_compare_data = unserialize($get_product[0]->_product_compare_data); 
      }

      if(!empty($get_product[0]->_product_color_filter_data)){
        $get_product[0]->_product_color_filter_data = unserialize($get_product[0]->_product_color_filter_data); 
      }

      if(!empty($get_product[0]->_role_based_pricing)){
        $get_product[0]->_role_based_pricing = unserialize($get_product[0]->_role_based_pricing); 
      }

      if(!empty($get_product[0]->_downloadable_product_files)){
        $get_product[0]->_downloadable_product_files = unserialize($get_product[0]->_downloadable_product_files); 
      }

      $get_product = (array) array_shift($get_product);
    }                   

    return $get_product;                 
  }
  
  /**
   * Get function for brands products
   *
   * @param $term slug
   * @return array
   */
  public function getBrandDataBySlug($term_slug){
    $brand_data =  array();
    $get_brand_term = Term :: where(['slug' => $term_slug, 'type' => 'product_brands', 'status' => 1])->first();
    
    if(!empty($get_brand_term)){
      $brand_term_details = $this->getTermDataById($get_brand_term->term_id);
      $brand_data['brand_details']  =  array_shift($brand_term_details);
      
      $brand_data['products'] =  null;
      $get_object_data =  DB::table('products')
                          ->where(['products.status' => 1, 'object_relationships.term_id' => $get_brand_term->term_id ])
                          ->join('object_relationships', 'object_relationships.object_id', '=', 'products.id')
                          ->select('products.*')
                          ->orderBy('products.id', 'desc')
                          ->paginate(10);
      
      if(count($get_object_data) > 0){
        $brand_data['products'] =  $get_object_data;
      }
    }
     
    return $brand_data;
  }
  
  /**
   * Get function for categories list by object id
   *
   * @param object id
   * @return array
   */
  public function getCatByObjectId($object_id){
    $get_cat_array = array('term_id' => array(), 'term_details' => array());
    $get_cat_list  =  DB::table('terms')
                      ->where(['object_relationships.object_id' => $object_id, 'terms.type' => 'product_cat' ])
                      ->join('object_relationships', 'object_relationships.term_id', '=', 'terms.term_id')
                      ->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                      ->select('terms.*', DB::raw("max(CASE WHEN term_extras.key_name = '_category_description' THEN term_extras.key_value END) as category_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_category_img_url' THEN term_extras.key_value END) as category_img_url"))  
                      ->groupBy('term_extras.term_id')      
                      ->get()
                      ->toArray();

    if(is_array($get_cat_list) && count($get_cat_list) > 0){
      $term_id   = array();
      $term_data = array();
      
      foreach($get_cat_list as $row){
        array_push($term_id, $row->term_id);
        array_push($term_data, (array) $row);
      }
      
      $get_cat_array['term_id'] = $term_id;
      $get_cat_array['term_details'] = $term_data;
    }
    
    return $get_cat_array;
  }
  
  /**
   * Get function for top parent
   *
   * @param cat id
   * @return parent id
   */
  public function getTopParentId($cat_id){
    $get_term = $this->getTermDataById($cat_id);
    
    if(count($get_term)>0){
      if($get_term[0]['parent'] > 0){
        $this->getTopParentId($get_term[0]['parent']);
      }
      else{
        $this->parent_id = $get_term[0]['term_id'];
      }
      
      if(!empty($this->parent_id) > 0){
        return $this->parent_id;
      }
    }
  }
  
  /**
   * Get function for tags list by object id
   *
   * @param object id
   * @return array
   */
  public function getTagsByObjectId($object_id){
    $get_tag_array = array('term_id' => array(), 'term_details' => array());
    $get_tag_list  =  DB::table('terms')
                      ->where(['object_relationships.object_id' => $object_id, 'terms.type' => 'product_tag' ])
                      ->join('object_relationships', 'object_relationships.term_id', '=', 'terms.term_id')
                      ->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                      ->select('terms.*', DB::raw("max(CASE WHEN term_extras.key_name = '_tag_description' THEN term_extras.key_value END) as tag_description"))  
                      ->groupBy('term_extras.term_id')         
                      ->get()
                      ->toArray();
     
    if(count($get_tag_list) > 0){
      $term_id = array();
      $term_data = array();
      
      foreach($get_tag_list as $row){
        array_push($term_id, $row->term_id);
        array_push($term_data, (array) $row);
      }
      
      $get_tag_array['term_id']      = $term_id;
      $get_tag_array['term_details'] = $term_data;
    }
    
    return $get_tag_array;
  }
  
  /**
   * Get function for colors list by object id
   *
   * @param object id
   * @return array
   */
  public function getColorsByObjectId($object_id){
    $get_colors_array = array('term_id' => array(), 'term_details' => array());
    $get_colors_list  =  DB::table('terms')
                         ->where(['object_relationships.object_id' => $object_id, 'terms.type' => 'product_colors' ])
                         ->join('object_relationships', 'object_relationships.term_id', '=', 'terms.term_id')
                         ->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                         ->select('terms.*', DB::raw("max(CASE WHEN term_extras.key_name = '_product_color_code' THEN term_extras.key_value END) as color_code"))  
                         ->groupBy('term_extras.term_id')      
                         ->get()
                         ->toArray();
     
    if(count($get_colors_list) > 0){
      $term_id = array();
      $term_data = array();
      
      foreach($get_colors_list as $row){
        array_push($term_id, $row->term_id);
        array_push($term_data, (array) $row);
      }
      
      $get_colors_array['term_id']      = $term_id;
      $get_colors_array['term_details'] = $term_data;
    }
    
    return $get_colors_array;
  }
  
  /**
   * Get function for sizes list by object id
   *
   * @param object id
   * @return array
   */
  public function getSizesByObjectId($object_id){
    $get_sizes_array = array('term_id' => array(), 'term_details' => array());
    $get_sizes_list  =  DB::table('terms')
                        ->where(['object_relationships.object_id' => $object_id, 'terms.type' => 'product_sizes' ])
                        ->join('object_relationships', 'object_relationships.term_id', '=', 'terms.term_id')
                        ->select('terms.*')
                        ->get()
                        ->toArray();
     
    if(count($get_sizes_list) > 0){
      $term_id = array();
      $term_data = array();
      
      foreach($get_sizes_list as $row){
        array_push($term_id, $row->term_id);
        array_push($term_data, (array) $row);
      }
      
      $get_sizes_array['term_id']      = $term_id;
      $get_sizes_array['term_details'] = $term_data;
    }
    
    return $get_sizes_array;
  }
  
  /**
   * Get function for manufacturer list by object id
   *
   * @param object id
   * @return array
   */
  public function getManufacturerByObjectId($object_id){
    $get_brands_array = array('term_id' => array(), 'term_details' => array());
    $get_brands_list  = DB::table('terms')
                        ->where(['object_relationships.object_id' => $object_id, 'terms.type' => 'product_brands' ])
                        ->join('object_relationships', 'object_relationships.term_id', '=', 'terms.term_id')
                        ->join('term_extras', 'terms.term_id', '=', 'term_extras.term_id')
                        ->select('terms.*', DB::raw("max(CASE WHEN term_extras.key_name = '_brand_country_name' THEN term_extras.key_value END) as brand_country_name"),  DB::raw("max(CASE WHEN term_extras.key_name = '_brand_short_description' THEN term_extras.key_value END) as brand_short_description"), DB::raw("max(CASE WHEN term_extras.key_name = '_brand_logo_img_url' THEN term_extras.key_value END) as brand_logo_img_url"))  
                        ->groupBy('term_extras.term_id')              
                        ->get()
                        ->toArray();
     
    if(count($get_brands_list) > 0){
      $term_id = array();
      $term_data = array();
      
      foreach($get_brands_list as $row){
        array_push($term_id, $row->term_id);
        array_push($term_data, (array) $row);
      }
      
      $get_brands_array['term_id']      = $term_id;
      $get_brands_array['term_details'] = $term_data;
    }

    return $get_brands_array;
  }
  
  /**
   * 
   * Get products list data
   *
   * @param pagination required. Boolean type TRUE or FALSE, by default false
   * @param search value optional
	 * @param status flag by default -1. -1 for all data, 1 for status enable and 0 for disable status
   * @return array
   */
  public function getProducts($pagination = false, $search_val = null, $status_flag = -1, $author_id, $is_vendor_login = false){
    $where = '';
    
    if(($is_vendor_login && Session::has('shopist_admin_user_id')) || (!empty($author_id) && $author_id > 0 && $author_id != 'all')){
      $post_author_id = 0;
      
      if(!empty($author_id) && $author_id > 0 && $author_id != 'all'){
        $post_author_id = $author_id;
      }
      else{
        $post_author_id = Session::get('shopist_admin_user_id');
      }
       
      if($status_flag != -1){
        $where = ['products.author_id' => $post_author_id, 'products.status' => $status_flag];
      }
      else{
        $where = ['products.author_id' => $post_author_id];
      }
    }
    else{
      if($status_flag != -1){
        $where = ['products.status' => $status_flag];
      }
    }
    
    
    if(!empty($search_val) && $search_val != '' && !empty($where)){
      $get_posts_for_product = DB::table('products')->where($where)
                               ->where('products.title', 'LIKE', '%'. $search_val .'%')
                               ->orderBy('products.id', 'desc');
    }
    elseif(!empty($search_val) && $search_val != '' && empty($where)){
      $get_posts_for_product = DB::table('products')
                                   ->where('products.title', 'LIKE', '%'. $search_val .'%')
                                   ->orderBy('products.id', 'desc');
    }
    elseif (empty($search_val)  && !empty($where)) {
      $get_posts_for_product = DB::table('products')
                                   ->where($where)
                                   ->orderBy('products.id', 'desc');
    }
    else{
      $get_posts_for_product = DB::table('products')
                                   ->orderBy('products.id', 'desc');
    }

    $get_posts_for_product = $get_posts_for_product->join('users', 'products.author_id', '=', 'users.id')
                                                   ->select('products.*', 'users.display_name')
                                                   ->paginate(30);

    return $get_posts_for_product;
  }
  
  /**
   * Get products by user id and name
   *
   * @param user_id, user_name
   * @return array
   */
  public function getProductsByUserId( $user_id, $user_name, $filter = array() ){
	  $data_array    =  array();
    $product_data  =  array();
    $filter_arr    =  array();
    $get_posts_for_product = null;
    $final_data = array();
    
    $product_data['min_price']          =   0;
    $product_data['max_price']          =   300;
    $product_data['selected_colors']    =  array();
    $product_data['selected_sizes']     =  array();
    $product_data['selected_colors_hf'] =  '';
    $product_data['selected_sizes_hf']  =  '';
    $product_data['sort_by']            =  '';
    
    //color filter
    if(isset($filter['selected_colors'])){
      $parse_colors = explode(',', $filter['selected_colors']);

      if(count($parse_colors) > 0){
        $product_data['selected_colors'] = $parse_colors;

        foreach($parse_colors as $color){
          $get_color_term = Term::where(['slug' => $color, 'type' => 'product_colors'])->first();

          if(!empty($get_color_term) && $get_color_term->term_id){
            array_push($filter_arr, array('id' => $get_color_term->term_id, 'name' => $get_color_term->name, 'slug' => $get_color_term->slug, 'search_type' => 'color-filter'));
          }
        }
      }
    }
        
    //size filter
    if(isset($filter['selected_sizes'])){
      $parse_sizes = explode(',', $filter['selected_sizes']);

      if(count($parse_sizes) > 0){
        $product_data['selected_sizes']	 =  $parse_sizes;

        foreach($parse_sizes as $size){
          $get_size_term = Term::where(['slug' => $size, 'type' => 'product_sizes'])->first();

          if(!empty($get_size_term) && $get_size_term->term_id){
            array_push($filter_arr, array('id' => $get_size_term->term_id, 'name' => $get_size_term->name, 'slug' => $get_size_term->slug, 'search_type' => 'size-filter'));
          }
        }
      }
    }
      
    if(count($filter_arr) > 0){
      foreach($filter_arr as $term_filter){
        $get_posts_for_product  = DB::table('products')
                                  ->where(['products.author_id' => $user_id, 'products.status' => 1]);
        
        if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
          $get_posts_for_product->where(['object_relationships.term_id' => $term_filter['id']]);
          $get_posts_for_product->whereRaw('products.price >=' . $filter['price_min']);
          $get_posts_for_product->whereRaw('products.price <=' . $filter['price_max']);
          $get_posts_for_product->join('object_relationships', 'object_relationships.object_id', '=', 'products.id');
        }

        $get_posts_for_product->select('products.*'); 
        $get_posts_for_product = $get_posts_for_product->get()->toArray();
        
        if(count($get_posts_for_product) > 0){
          foreach($get_posts_for_product as $post){
            $post_data = (array)$post;
            $data_array[$post->id] = $post_data;
          }
        }
      }
    }
    else{
      $get_posts_for_product  = DB::table('products')
                                ->where(['author_id' => $user_id, 'status' => 1]);
      
      if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0 ){
        $get_posts_for_product->whereRaw('price >=' . $filter['price_min']);
        $get_posts_for_product->whereRaw('price <=' . $filter['price_max']);
      }
      
      $get_posts_for_product->select('products.*'); 
      
      //sorting
      if(isset($filter['sort']) && $filter['sort'] == 'alpaz'){
        $get_posts_for_product->orderBy('title', 'ASC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'alpza') {
        $get_posts_for_product->orderBy('title', 'DESC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'low-high') {
        $get_posts_for_product->orderBy('price', 'ASC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'high-low') {
        $get_posts_for_product->orderBy('price', 'DESC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'old-new') {
        $get_posts_for_product->orderBy('created_at', 'ASC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'new-old') {
        $get_posts_for_product->orderBy('created_at', 'DESC');
      }
      
      $get_posts_for_product = $get_posts_for_product->paginate(10);
    }
    
    if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
      $product_data['min_price']  =   $filter['price_min'];
      $product_data['max_price']  =   $filter['price_max'];
    }

    if(isset($filter['selected_colors'])){
      $product_data['selected_colors_hf'] =  $filter['selected_colors'];
    }

    if(isset($filter['selected_sizes'])){
      $product_data['selected_sizes_hf']  =  $filter['selected_sizes'];
    }

    if(isset($filter['sort'])){
      $product_data['sort_by'] = $filter['sort'];
    }
     
    
    if(count($filter_arr) > 0){
      if(count($data_array) > 0){
        if(isset($filter['sort']) && $filter['sort'] != 'all'){
          if($filter['sort'] == 'alpaz'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'title', 'asc');
          }
          elseif($filter['sort'] == 'alpza'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'title', 'desc');
          }
          elseif($filter['sort'] == 'low-high'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'price', 'asc');
          }
          elseif($filter['sort'] == 'high-low'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'price', 'desc');
          }
          elseif($filter['sort'] == 'old-new'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'created_at', 'asc');
          }
          elseif($filter['sort'] == 'new-old'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'created_at', 'desc');
          }
        }
        else{
          $data_array = $this->classCommonFunction->sortBy($data_array, 'id', 'desc');
        }
      }
    
      if(count($data_array) > 0){
        foreach($data_array as $row){
          array_push($final_data, (object)$row);
        }
      }
      
      $currentPage = LengthAwarePaginator::resolveCurrentPage();
      $col = new Collection( $final_data );
      $perPage = 10;
      $currentPageSearchResults = $col->slice(($currentPage - 1) * $perPage, $perPage)->all();
      $posts_object = new LengthAwarePaginator($currentPageSearchResults, count($col), $perPage);
      $posts_object->setPath(route('store-products-page-content', $user_name) );

      $product_data['products'] = $posts_object;
    }
    else{
      $product_data['products'] = $get_posts_for_product;
    }
    
    return $product_data;
  }

  /**
  * Get product comments list
  *
  * @param null
  * @return array
  */
  
  public function getProductCommentsList(){
    $comments_data = array();
    
    if(is_vendor_login() && Session::has('shopist_admin_user_id')){
      $where = ['products.author_id' => Session::get('shopist_admin_user_id'), 'comments.target' => 'product'];
    }
    else{
      $where = ['comments.target' => 'product'];
    }
    
    $get_comments = DB::table('comments')->where($where)
                    ->join('products', 'comments.object_id', '=', 'products.id')
                    ->join('users', 'comments.user_id', '=', 'users.id')
                    ->select('comments.*', 'products.title', 'products.slug', 'users.display_name', 'users.user_photo_url')   
                    ->paginate(10);
      
    if(count($get_comments) > 0){
        $comments_data = $get_comments;
    }
    
    return $comments_data;
  }
  
  /**
   * Get function for advance products
   *
   * @param null
   * @return array
   */
  public function getAdvancedProducts(){
    $best_sales_arr      =  array();
    $todays_deal_arr     =  array();
    $advanced_arr        =  array();
    
    if(Request::is('/')){
      $get_recommended_items = DB::table('products')
                               ->select('products.*')
                               ->join(DB::raw("(SELECT product_id FROM product_extras WHERE key_name = '_product_enable_as_recommended' AND key_value = 'yes') T1") , 'products.id', '=', 'T1.product_id')
                               ->take(8)
                               ->get()
                               ->toArray();

      $get_features_items =    DB::table('products')
                               ->select('products.*')
                               ->join(DB::raw("(SELECT product_id FROM product_extras WHERE key_name = '_product_enable_as_features' AND key_value = 'yes') T1") , 'products.id', '=', 'T1.product_id')
                               ->take(8)
                               ->get()
                               ->toArray();
    }
    
    $get_latest_items =      DB::table('products')
                             ->select('products.*')
                             ->join(DB::raw("(SELECT product_id FROM product_extras WHERE key_name = '_product_enable_as_latest' AND key_value = 'yes') T1") , 'products.id', '=', 'T1.product_id')
                             ->take(5)
                             ->get()
                             ->toArray();
   
    if(Request::is('/')){ 
      $get_todays_items      =  DB::table('posts')
                                ->where('posts.post_type', 'shop_order')
                                ->whereDate('posts.created_at', '=', $this->carbonObject->today()->toDateString())
                                ->join('orders_items', 'orders_items.order_id', '=', 'posts.id')
                                ->orderBy('posts.id', 'desc')
                                ->select('orders_items.*')
                                ->take(5)
                                ->get()
                                ->toArray();
    }
    
    $get_best_sales        =  DB::table('product_extras')
                              ->select('product_id', DB::raw('max(cast(key_value as SIGNED INTEGER)) as max_number'))
                              ->where('key_name', '_total_sales')
                              ->groupBy('product_id')
                              ->orderBy('max_number', 'desc')
                              ->take(5)
                              ->get()
                              ->toArray();
     
    //best sales
    if(count($get_best_sales) > 0){
      foreach($get_best_sales as $items4){
        $get_post_for_best_sales = $this->getProductDataById($items4->product_id);
        
        if(count($get_post_for_best_sales)>0){
          array_push($best_sales_arr, $get_post_for_best_sales);
        }
      }
    }
    
    if(Request::is('/')){
      //todays deal
      if(count($get_todays_items) > 0){
        foreach($get_todays_items as $items5){
          if(!empty($items5->order_data)){
            $parse = json_decode($items5->order_data, true);

            if(count($parse) > 0){
              foreach($parse as $items6){
                if(isset($items6['id'])){
                  if(!$this->classCommonFunction->is_item_already_exists_in_array($items6['id'], $todays_deal_arr)){
                    $get_post_for_todays_deal = $this->getProductDataById($items6['id']);

                    if(count($get_post_for_todays_deal) > 0){
                      array_push($todays_deal_arr, $get_post_for_todays_deal);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    
    if(Request::is('/')){
      $advanced_arr['recommended_items']  =   $get_recommended_items;
      $advanced_arr['features_items']     =   $get_features_items;
      $advanced_arr['todays_deal']        =   $todays_deal_arr; 
    }
    $advanced_arr['latest_items']         =   $get_latest_items;
    $advanced_arr['best_sales']           =   $best_sales_arr; 
     
    return $advanced_arr;
  }
  
  /**
   * Get function for vendor advance products
   *
   * @param vendor_id
   * @return array
   */
  public function getVendorAdvancedProducts( $vendor_id ){
    $best_sales_arr      =  array();
    $todays_deal_arr     =  array();
    $advanced_arr        =  array();
    
    $get_recommended_items = DB::table('products')
                             ->where('products.author_id', $vendor_id) 
                             ->select('products.*')
                             ->join(DB::raw("(SELECT product_id FROM product_extras WHERE key_name = '_product_enable_as_recommended' AND key_value = 'yes') T1") , 'products.id', '=', 'T1.product_id')
                             ->take(8)
                             ->get()
                             ->toArray();

    $get_features_items =    DB::table('products')
                             ->where('products.author_id', $vendor_id) 
                             ->select('products.*')
                             ->join(DB::raw("(SELECT product_id FROM product_extras WHERE key_name = '_product_enable_as_features' AND key_value = 'yes') T1") , 'products.id', '=', 'T1.product_id')
                             ->take(8)
                             ->get()
                             ->toArray();
    
    $get_latest_items =      DB::table('products')
                             ->where('products.author_id', $vendor_id) 
                             ->select('products.*')
                             ->join(DB::raw("(SELECT product_id FROM product_extras WHERE key_name = '_product_enable_as_latest' AND key_value = 'yes') T1") , 'products.id', '=', 'T1.product_id')
                             ->take(5)
                             ->get()
                             ->toArray();
    
    $get_todays_items      =  DB::table('posts')
                              ->where('posts.post_type', 'shop_order')
                              ->whereDate('posts.created_at', '=', $this->carbonObject->today()->toDateString())
                              ->join('orders_items', 'orders_items.order_id', '=', 'posts.id')
                              ->orderBy('posts.id', 'desc')
                              ->select('orders_items.*')
                              ->limit(10)
                              ->get()
                              ->toArray();
    
    $get_best_sales        =  DB::table('product_extras')
                              ->select('product_id', DB::raw('max(cast(key_value as SIGNED INTEGER)) as max_number'))
                              ->where('key_name', '_total_sales')
                              ->groupBy('product_id')
                              ->orderBy('max_number', 'desc')
                              ->limit(10)
                              ->get()
                              ->toArray();
     
    //best sales
    if(count($get_best_sales) > 0){
      foreach($get_best_sales as $items4){
        $get_post_for_best_sales = $this->getProductDataById($items4->product_id);
        
        if(isset($get_post_for_best_sales['author_id']) && $get_post_for_best_sales['author_id'] == $vendor_id){
          if(count($get_post_for_best_sales)>0){
            array_push($best_sales_arr, $get_post_for_best_sales);
          }
        }
      }
    }
    
    //todays deal
    if(count($get_todays_items) > 0){
      foreach($get_todays_items as $items5){
        if(!empty($items5->order_data)){
          $parse = json_decode($items5->order_data, true);
          
          if(count($parse) > 0){
            foreach($parse as $items6){
              if(isset($items6['id'])){
                if(!$this->classCommonFunction->is_item_already_exists_in_array($items6['id'], $todays_deal_arr)){
                  $get_post_for_todays_deal = $this->getProductDataById($items6['id']);
                
                  if($get_post_for_todays_deal['author_id'] == $vendor_id){
                    if(count($get_post_for_todays_deal) > 0){
                      array_push($todays_deal_arr, $get_post_for_todays_deal);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    
    $advanced_arr['recommended_items']    =   $get_recommended_items;
    $advanced_arr['features_items']       =   $get_features_items;
    $advanced_arr['latest_items']         =   $get_latest_items;
    $advanced_arr['best_sales']           =   $best_sales_arr; 
    $advanced_arr['todays_deal']          =   $todays_deal_arr; 
     
    return $advanced_arr;
  }
  
  /**
   * Get function for product search filter with pagination
   *
   * @param search options
   * @return array
   */
  public function getFilterProductsDataWithPagination( $filter = array() ){
    $data_array    =  array();
    $product_data  =  array();
    $filter_arr    =  array();
    $get_posts_for_product = null;
    $final_data = array();
    
    $product_data['min_price']          =   0;
    $product_data['max_price']          =   300;
    $product_data['selected_colors']    =  array();
    $product_data['selected_sizes']     =  array();
    $product_data['selected_colors_hf'] =  '';
    $product_data['selected_sizes_hf']  =  '';
    $product_data['sort_by']            =  '';
    
    //color filter
    if(isset($filter['selected_colors'])){
      $parse_colors = explode(',', $filter['selected_colors']);

      if(count($parse_colors) > 0){
        $product_data['selected_colors'] = $parse_colors;

        foreach($parse_colors as $color){
          $get_color_term = Term::where(['slug' => $color, 'type' => 'product_colors'])->first();

          if(!empty($get_color_term) && $get_color_term->term_id){
            array_push($filter_arr, array('id' => $get_color_term->term_id, 'name' => $get_color_term->name, 'slug' => $get_color_term->slug, 'search_type' => 'color-filter'));
          }
        }
      }
    }
        
    //size filter
    if(isset($filter['selected_sizes'])){
      $parse_sizes = explode(',', $filter['selected_sizes']);

      if(count($parse_sizes) > 0){
        $product_data['selected_sizes']	 =  $parse_sizes;

        foreach($parse_sizes as $size){
          $get_size_term = Term::where(['slug' => $size, 'type' => 'product_sizes'])->first();

          if(!empty($get_size_term) && $get_size_term->term_id){
            array_push($filter_arr, array('id' => $get_size_term->term_id, 'name' => $get_size_term->name, 'slug' => $get_size_term->slug, 'search_type' => 'size-filter'));
          }
        }
      }
    }
      
    if(count($filter_arr) > 0){
      foreach($filter_arr as $term_filter){
        $get_posts_for_product  = DB::table('products')
                                  ->where(['products.status' => 1]);
        
        if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
          $get_posts_for_product->where(['object_relationships.term_id' => $term_filter['id']]);
          $get_posts_for_product->whereRaw('products.price >=' . $filter['price_min']);
          $get_posts_for_product->whereRaw('products.price <=' . $filter['price_max']);
          $get_posts_for_product->join('object_relationships', 'object_relationships.object_id', '=', 'products.id');
        }

        $get_posts_for_product->select('products.*'); 
        $get_posts_for_product = $get_posts_for_product->get()->toArray();
        
        if(count($get_posts_for_product) > 0){
          foreach($get_posts_for_product as $post){
            $post_data = (array)$post;
            $data_array[$post->id] = $post_data;
          }
        }
      }
    }
    else{
      $get_posts_for_product  = DB::table('products')
                                ->where(['status' => 1]);
      
      if(isset($filter['srch_term'])){
        $get_posts_for_product->where('title', 'like', '%'. $filter['srch_term'] .'%');
      }
      
      if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0 ){
        $get_posts_for_product->whereRaw('price >=' . $filter['price_min']);
        $get_posts_for_product->whereRaw('price <=' . $filter['price_max']);
      }
      
      $get_posts_for_product->select('products.*'); 
      
      //sorting
      if(isset($filter['sort']) && $filter['sort'] == 'alpaz'){
        $get_posts_for_product->orderBy('title', 'ASC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'alpza') {
        $get_posts_for_product->orderBy('title', 'DESC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'low-high') {
        $get_posts_for_product->orderBy('price', 'ASC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'high-low') {
        $get_posts_for_product->orderBy('price', 'DESC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'old-new') {
        $get_posts_for_product->orderBy('created_at', 'ASC');
      }
      elseif (isset($filter['sort']) && $filter['sort'] == 'new-old') {
        $get_posts_for_product->orderBy('created_at', 'DESC');
      }
      
      $get_posts_for_product = $get_posts_for_product->paginate(10);
    }
    
    if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
      $product_data['min_price']  =   $filter['price_min'];
      $product_data['max_price']  =   $filter['price_max'];
    }

    if(isset($filter['selected_colors'])){
      $product_data['selected_colors_hf'] =  $filter['selected_colors'];
    }

    if(isset($filter['selected_sizes'])){
      $product_data['selected_sizes_hf']  =  $filter['selected_sizes'];
    }

    if(isset($filter['sort'])){
      $product_data['sort_by'] = $filter['sort'];
    }
     
    
    if(count($filter_arr) > 0){
      if(count($data_array) > 0){
        if(isset($filter['sort']) && $filter['sort'] != 'all'){
          if($filter['sort'] == 'alpaz'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'title', 'asc');
          }
          elseif($filter['sort'] == 'alpza'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'title', 'desc');
          }
          elseif($filter['sort'] == 'low-high'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'price', 'asc');
          }
          elseif($filter['sort'] == 'high-low'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'price', 'desc');
          }
          elseif($filter['sort'] == 'old-new'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'created_at', 'asc');
          }
          elseif($filter['sort'] == 'new-old'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'created_at', 'desc');
          }
        }
        else{
          $data_array = $this->classCommonFunction->sortBy($data_array, 'id', 'desc');
        }
      }
    
      if(count($data_array) > 0){
        foreach($data_array as $row){
          array_push($final_data, (object)$row);
        }
      }
      
      $currentPage = LengthAwarePaginator::resolveCurrentPage();
      $col = new Collection( $final_data );
      $perPage = 10;
      $currentPageSearchResults = $col->slice(($currentPage - 1) * $perPage, $perPage)->all();
      $posts_object = new LengthAwarePaginator($currentPageSearchResults, count($col), $perPage);
      $posts_object->setPath( route('shop-page') );

      $product_data['products'] = $posts_object;
    }
    else{
      $product_data['products'] = $get_posts_for_product;
    }
    
    return $product_data;
  }
  
  /**
   * Get function for products by cat slug
   *
   * @param cat options
   * @return array
   */
  public function getProductByCatSlug($cat_slug, $filter = array()){
    $data_array         =   array();
    $post_array         =   array();
    $selected_cat       =   array();
    $color_size_obj_id  =   array(); 
    $filter_arr         =   array();
    
    $get_term = Term::where(['slug' => $cat_slug, 'type' => 'product_cat'])->first();
     
    if(!empty($get_term) && isset($get_term->term_id)){
      $str    = '';
      $cat_id = $get_term->term_id;
      
      $post_array['min_price']          =  0;
      $post_array['max_price']          =  300;
      $post_array['selected_colors']    =  array();
      $post_array['selected_sizes']     =  array();
      $post_array['selected_colors_hf'] =  '';
      $post_array['selected_sizes_hf']  =  '';
      $post_array['sort_by']  =  '';
      
      $get_term_data = $this->getTermDataById( $get_term->term_id );
      $get_child_cat = $this->get_categories($get_term->term_id, 'product_cat');
      $parent_id     = $this->getTopParentId( $get_term->term_id );

      $cat_data['id']    =  $get_term_data[0]['term_id'];
      $cat_data['name']  =  $get_term_data[0]['name'];
      $cat_data['slug']  =  $get_term_data[0]['slug'];
      
      if($get_term_data[0]['parent'] == 0){
        $cat_data['parent']  =  'Parent Categories';
      }
      else{
        $cat_data['parent']  =  'Sub Categories';
      }
      
      $cat_data['parent_id']  =  $get_term_data[0]['parent'] ;
      $cat_data['description']  =  $get_term_data[0]['category_description'];
      $cat_data['img_url']  =  $get_term_data[0]['category_img_url'];
      $cat_data['search_type']  =  'category-filter';
      
      
      //color filter
      if(isset($filter['selected_colors'])){
        $parse_colors = explode(',', $filter['selected_colors']);

        if(count($parse_colors) > 0){
          $post_array['selected_colors'] = $parse_colors;
            
          foreach($parse_colors as $color){
            $get_color_term = Term::where(['slug' => $color, 'type' => 'product_colors'])->first();

            if(!empty($get_color_term) && $get_color_term->term_id){
              array_push($filter_arr, array('id' => $get_color_term->term_id, 'name' => $get_color_term->name, 'slug' => $get_color_term->slug, 'search_type' => 'color-filter'));
            }
          }
        }
      }
        
      //size filter
      if(isset($filter['selected_sizes'])){
        $parse_sizes = explode(',', $filter['selected_sizes']);

        if(count($parse_sizes) > 0){
          $post_array['selected_sizes']	 =  $parse_sizes;
          
          foreach($parse_sizes as $size){
            $get_size_term = Term::where(['slug' => $size, 'type' => 'product_sizes'])->first();

            if(!empty($get_size_term) && $get_size_term->term_id){
              array_push($filter_arr, array('id' => $get_size_term->term_id, 'name' => $get_size_term->name, 'slug' => $get_size_term->slug, 'search_type' => 'size-filter'));
            }
          }
        }
      }
      
      if(count($get_child_cat) > 0){
				$all_cat = array();
        $cats_arr = $this->createCategoriesSimpleList( $get_child_cat );
                
				if(count($cats_arr) > 0){
          $cats_arr = array_map(function($cats_arr){
            return $cats_arr + ['search_type' => 'category-filter'];
          }, $cats_arr);
          
          array_push($cats_arr, $cat_data);
					$all_cat = $cats_arr;
        }
        				
        if(count($filter_arr) > 0 && count($cats_arr) > 0){
          $cats_arr = array_merge($filter_arr, $cats_arr);
        }
        
        foreach($cats_arr as $cat){
          if( (count($filter_arr) > 0 && ($cat['search_type'] == 'color-filter' || $cat['search_type'] == 'size-filter')) || (count($filter_arr) == 0 && $cat['search_type'] == 'category-filter')){
            $get_post_data =  DB::table('products');
            $get_post_data->where(['products.status' => 1, 'object_relationships.term_id' => $cat['id'] ]);

            if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
              $get_post_data->whereRaw('products.price >=' . $filter['price_min']);
              $get_post_data->whereRaw('products.price <=' . $filter['price_max']);
            }

            $get_post_data->join('object_relationships', 'object_relationships.object_id', '=', 'products.id');

            $get_post_data->select('products.*');
            $get_post_data = $get_post_data->get()->toArray();
            
            if(count($get_post_data) > 0){
              foreach($get_post_data as $post){
                $filter_cat = array();
                
                if($cat['search_type'] == 'color-filter' || $cat['search_type'] == 'size-filter'){
                  $filter_cat = $this->getCatByObjectId( $post->id );
                }

                if( (($cat['search_type'] == 'color-filter' || $cat['search_type'] == 'size-filter') && $this->classCommonFunction->is_product_cat_in_selected_cat($filter_cat, $all_cat)) || ($cat['search_type'] == 'category-filter') ){
                  $post_data = (array)$post;
                  $data_array[$post->id] = $post_data;
                }
              }
            }
          }		

          if($cat['search_type'] == 'category-filter'){
            array_push($selected_cat, $cat['id']);
          }
        }
      }
      else{
        $parent_cat_ary = array();
        $parent_cat_ary[] = $cat_data;
        $all_cat = $parent_cat_ary;
        
        if(count($filter_arr) > 0){
          $parent_cat_ary = array_merge($filter_arr, $parent_cat_ary);
        }
        
        if(count($parent_cat_ary) > 0){
          foreach($parent_cat_ary as $cat){
            if( (count($filter_arr) > 0 && ($cat['search_type'] == 'color-filter' || $cat['search_type'] == 'size-filter')) || (count($filter_arr) == 0 && $cat['search_type'] == 'category-filter')){
              
              $get_post_data =  DB::table('products');
              $get_post_data->where(['products.status' => 1, 'object_relationships.term_id' => $cat['id'] ]);

              if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
                $get_post_data->whereRaw('products.price >=' . $filter['price_min']);
                $get_post_data->whereRaw('products.price <=' . $filter['price_max']);
              }

              $get_post_data->join('object_relationships', 'object_relationships.object_id', '=', 'products.id');

              $get_post_data->select('products.*');
              $get_post_data = $get_post_data->get()->toArray();

              if(count($get_post_data) > 0){
                foreach($get_post_data as $post){
                  $filter_cat = array();

                  if($cat['search_type'] == 'color-filter' || $cat['search_type'] == 'size-filter'){
                    $filter_cat = $this->getCatByObjectId( $post->id );
                  }

                  if( (($cat['search_type'] == 'color-filter' || $cat['search_type'] == 'size-filter') && $this->classCommonFunction->is_product_cat_in_selected_cat($filter_cat, $all_cat)) || ($cat['search_type'] == 'category-filter') ){
                    $post_data = (array)$post;
                    $data_array[$post->id] = $post_data;
                  }
                }
              }
            }  

            if($cat['search_type'] == 'category-filter'){
              array_push($selected_cat, $cat['id']);
            }
          }
        }
      }
      
      if( isset($filter['price_min']) && isset($filter['price_max']) && $filter['price_min'] >= 0 && $filter['price_max'] >=0){
        $post_array['min_price']  =   $filter['price_min'];
        $post_array['max_price']  =   $filter['price_max'];
      }

      if(isset($filter['selected_colors'])){
        $post_array['selected_colors_hf'] =  $filter['selected_colors'];
      }

      if(isset($filter['selected_sizes'])){
        $post_array['selected_sizes_hf']  =  $filter['selected_sizes'];
      }
      
      if(isset($filter['sort'])){
        $post_array['sort_by'] = $filter['sort'];
      }
			
      if(count($data_array) > 0){
        if(isset($filter['sort']) && $filter['sort'] != 'all'){
          if($filter['sort'] == 'alpaz'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'title', 'asc');
          }
          elseif($filter['sort'] == 'alpza'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'title', 'desc');
          }
          elseif($filter['sort'] == 'low-high'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'price', 'asc');
          }
          elseif($filter['sort'] == 'high-low'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'price', 'desc');
          }
          elseif($filter['sort'] == 'old-new'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'created_at', 'asc');
          }
          elseif($filter['sort'] == 'new-old'){
            $data_array = $this->classCommonFunction->sortBy($data_array, 'created_at', 'desc');
          }
        }
        else{
          $data_array = $this->classCommonFunction->sortBy($data_array, 'id', 'desc');
        }
      }
      
      $currentPage = LengthAwarePaginator::resolveCurrentPage();
      $col = new Collection( $data_array );
      $perPage = 10;
      $currentPageSearchResults = $col->slice(($currentPage - 1) * $perPage, $perPage)->all();
      $posts_object = new LengthAwarePaginator($currentPageSearchResults, count($col), $perPage);
      $posts_object->setPath( route('categories-page', $cat_data['slug']) );
      
      
      if($cat_data['parent_id'] > 0){
        $parent_cat = $this->getTermDataById( $cat_data['parent_id'] );
        
        $str = '<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="'. route('home-page') .'"><i class="fa fa-home"></i></a></li><li class="breadcrumb-item"><a href="'. route('shop-page') .'">'. Lang::get('frontend.all_products_label' ) .'</a></li><li class="breadcrumb-item"><a href="'. route('categories-page', $parent_cat[0]['slug']) .'">'. $parent_cat[0]['name'] .'</a></li><li class="breadcrumb-item active" aria-current="page">'. $cat_data['name'] .'</li></ol></nav>';
      }
      else{
        $str = '<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="'. route('home-page') .'"><i class="fa fa-home"></i></a></li><li class="breadcrumb-item"><a href="'. route('shop-page') .'">'. Lang::get('frontend.all_products_label' ) .'</a></li><li class="breadcrumb-item active" aria-current="page">'. $cat_data['name'] .'</li></ol></nav>';
      }

      $post_array['products']        =  $posts_object;
      $post_array['breadcrumb_html'] =  $str;
      $post_array['selected_cat']    =  $selected_cat;
      $post_array['parent_id']       =  $parent_id;
      $post_array['parent_slug']     =  $cat_data['slug'];
    }
 
    return $post_array;
  }
    
  /**
   * Get function for attributes
   *
   * @param product id
   * @return array
   */
  public function getAllAttributes($product_id){
    $get_available_attribute = array();
    $get_attr_from_global	 =  $this->getTermData( 'product_attr', false, null, 1 );
     
    if(count($get_attr_from_global) > 0){
      foreach($get_attr_from_global as $term){
        $ary = array();
        $ary['attr_id']     = $term->term_id;
        $ary['attr_name']   = $term->name;
        $ary['attr_values'] = $term->product_attr_values;
        $ary['attr_status'] = $term->status;
        $ary['created_at']  = $term->created_at;
        $ary['updated_at']  = $term->updated_at;

        array_push($get_available_attribute, $ary);
      }
    }
    
    $get_attr_by_products  =  PostExtra::where(['post_id' => $product_id, 'key_name' => '_attribute_post_data'])->get()->toArray();
    
    if(count($get_attr_by_products)>0){
      $parseJsonToArray = json_decode($get_attr_by_products[0]['key_value']);
      
      if(!empty($parseJsonToArray)){
        foreach($parseJsonToArray as $row){
          $ary = array();
          $ary['attr_id']     = $row->id;
          $ary['attr_name']   = $row->attr_name;
          $ary['attr_values'] = $row->attr_val;
          $ary['attr_status'] = 1;
          $ary['created_at']  = $get_attr_by_products[0]['created_at'];
          $ary['updated_at']  = $get_attr_by_products[0]['updated_at'];

          array_push($get_available_attribute, $ary);
        }
      }
    }
    
    return $get_available_attribute;
  }
  
  /**
   * Get products by cat id
   *
   * @param cat id
   * @return array
   */
  public function getProductsByTermId($term_id){
    $object_array = array();
    $get_post_data =  DB::table('products')
                      ->where(['products.status' => 1, 'object_relationships.term_id' => $term_id ])
                      ->join('object_relationships', 'object_relationships.object_id', '=', 'products.id')
                      ->select('products.*')
                      ->orderBy('products.id', 'desc')
                      ->get()
                      ->toArray();
    
    if(count($get_post_data) > 0){
      $object_array = $get_post_data;
    }
    
    return $object_array;
  }
  
  /**
   * Get related items
   *
   * @param product id
   * @return array
   */
  
  public function getRelatedItems($product_id){
    $related_items  =  array();
    $related_products  =  array();
    
    //categories product search
    $cat_lists = $this->getCatByObjectId($product_id);
    
    if(count($cat_lists) > 0 && isset($cat_lists['term_id']) && count($cat_lists['term_id']) > 0){
      foreach($cat_lists['term_id'] as $cat_id){
        $cat_term = $this->getTermDataById($cat_id);
        
        if(count($cat_term) > 0){
          $get_cat_product_list  =  $this->getProductsByTermId($cat_term[0]['term_id']);
          
          if(count($get_cat_product_list) >0){
            foreach($get_cat_product_list as $product){
              if($product->id != $product_id){
                array_push($related_items, $product->id);
              }
            }
          }
        }
      }
    }
   
    //tags product search
    $tag_lists = $this->getTagsByObjectId($product_id);
    
    if(count($tag_lists) > 0 && isset($tag_lists['term_id']) && count($tag_lists['term_id']) > 0){
      foreach($tag_lists['term_id'] as $tag_id){
        $tag_term = $this->getTermDataById($tag_id);
        
        if(count($tag_term) > 0){
          $get_tag_product_list  =  $this->getProductsByTermId($tag_term[0]['term_id']);
          
          if(count($get_tag_product_list) > 0 ){
            foreach($get_tag_product_list as $tag_product){
             if($tag_product->id != $product_id){
               array_push($related_items, $tag_product->id);
             }
            }
          }
        }
      }
    }
     
    //brand product search
    $brand_lists = $this->getManufacturerByObjectId($product_id);
    
    if(count($brand_lists) > 0 && isset($brand_lists['term_id']) && count($brand_lists['term_id']) > 0){
      foreach($brand_lists['term_id'] as $brand_id){
        $brand_term = $this->getTermDataById($brand_id);
        
        if(count($brand_term) > 0){
          $get_brands_product_list  =  $this->getProductsByTermId($brand_term[0]['term_id']);
          
          if(count($get_brands_product_list) > 0){
            foreach($get_brands_product_list as $brand_product){
              if($brand_product->id != $product_id){
                array_push($related_items, $brand_product->id);
              }
            }
          }
        }
      }
    }
    
    
    if(count($related_items) > 0){
      $products_id_array = array_unique($related_items);
   
      if(count($products_id_array) > 0){
        foreach($products_id_array as $related_products_id){
          $get_post_meta  =  ProductExtra :: where(['product_id' => $related_products_id, 'key_name' => '_product_enable_as_related'])->first();

          if(!empty($get_post_meta) && $get_post_meta->key_value == 'yes'){
            array_push($related_products, $this->getProductDataById($related_products_id));
          }  
        }
      }
    }
    
    return $related_products;
  }
  
  /**
   * 
   *Export products 
   *
   * @param null
   * @return void
   */
  public function manageExportProducts(){
    $export_data  = array();
    $get_products = array();
    
    if(is_vendor_login() && Session::has('shopist_admin_user_id')){
      $get_products = Product :: where(['author_id' => Session::get('shopist_admin_user_id')])->orderBy('id', 'DESC')->get()->toArray();
    }
    else{
      $get_products = Product :: where([])->orderBy('id', 'DESC')->get()->toArray();
    }
    
    
    if(count($get_products) > 0){
      foreach($get_products as $rows){
        $regular_price = '';
        $sku = '';
        $short_features = '';
        $recommended = FALSE;
        $features = FALSE;
        $latest = FALSE;
        $related = FALSE;
        $home_page = FALSE;
        $reviews = FALSE;
        $p_reviews = FALSE;
        $d_reviews = FALSE;
        $seo_title = '';
        $seo_desc = '';
        $seo_keywords = '';
        $visibility = FALSE;
        
        $regular_price = $rows['regular_price'];
        $sku = $rows['sku'];
        
        if($rows['status'] == 1){
          $visibility = TRUE;
        }

        $get_post_extras = ProductExtra::where(['product_id' => $rows['id']])->get()->toArray();
        if(count($get_post_extras) > 0){
          foreach($get_post_extras as $post_extras){
            if($post_extras['key_name'] == '_product_extra_features'){
              $short_features = $post_extras['key_value'];
            }
            if($post_extras['key_name'] == '_product_enable_as_recommended'){
              if($post_extras['key_value'] == 'yes'){
                $recommended = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_as_features'){
              if($post_extras['key_value'] == 'yes'){
                $features = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_as_latest'){
              if($post_extras['key_value'] == 'yes'){
                $latest = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_as_related'){
              if($post_extras['key_value'] == 'yes'){
                $related = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_as_selected_cat'){
              if($post_extras['key_value'] == 'yes'){
                $home_page = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_reviews'){
              if($post_extras['key_value'] == 'yes'){
                $reviews = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_reviews_add_link_to_product_page'){
              if($post_extras['key_value'] == 'yes'){
                $p_reviews = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_enable_reviews_add_link_to_details_page'){
              if($post_extras['key_value'] == 'yes'){
                $d_reviews = TRUE;
              }
            }
            if($post_extras['key_name'] == '_product_seo_title'){
              $seo_title = $post_extras['key_value'];
            }
            if($post_extras['key_name'] == '_product_seo_description'){
              $seo_desc = $post_extras['key_value'];
            }
            if($post_extras['key_name'] == '_product_seo_keywords'){
              $seo_keywords = $post_extras['key_value'];
            }
          }
        }

        array_push($export_data, array('title' => $rows['title'], 'description' => $rows['content'], 'regular_price' => $regular_price, 'sku' => $sku, 'extra_features' => $short_features, 'enable_recommended' => ($recommended) ? 'TRUE' : 'FALSE', 'enable_features' => ($features) ? 'TRUE' : 'FALSE', 'enable_latest' => ($latest) ? 'TRUE' : 'FALSE', 'enable_related' => ($related) ? 'TRUE' : 'FALSE', 'enable_home' => ($home_page) ? 'TRUE' : 'FALSE', 'visibility' => ($visibility) ? 'TRUE' : 'FALSE', 'reviews' => ($reviews) ? 'TRUE' : 'FALSE', 'p_reviews' => ($p_reviews) ? 'TRUE' : 'FALSE', 'd_reviews' => ($d_reviews) ? 'TRUE' : 'FALSE', 'seo_title' => $seo_title, 'seo_desc' => $seo_desc, 'seo_keywords' => $seo_keywords));
      }
    }

    $filename = 'products_export_'.time().'-'.mt_rand().'.csv';
    $headers = array(
      "Content-type" => "text/csv",
      "Content-Disposition" => "attachment; filename=". $filename,
      "Pragma" => "no-cache",
      "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
      "Expires" => "0"
    );

    $columns = array('Title', 'Description(HTML)', 'Regular Price', 'SKU', 'Features(HTML)', 'Recommended Product', 'Features Product', 'Latest Product', 'Related Product', 'Home Page Product', 'Visibility', 'Enable Reviews', 'Enable Review Product Page', 'Enable Review Details Page', 'SEO Title', 'SEO Description', 'SEO Keywords(Comma Separator)');

    $callback = function() use ($export_data, $columns){
      $file = fopen('php://output', 'w');
      fputcsv($file, $columns);

      if(count($export_data) > 0){
        foreach($export_data as $data) {
          fputcsv($file, array($data['title'], $data['description'], $data['regular_price'], $data['sku'], $data['extra_features'], $data['enable_recommended'], $data['enable_features'], $data['enable_latest'], $data['enable_related'], $data['enable_home'], $data['visibility'], $data['reviews'], $data['p_reviews'], $data['d_reviews'], $data['seo_title'], $data['seo_desc'], $data['seo_keywords']));
        }
      }

      fclose($file);
    };

    return Response::stream($callback, 200, $headers);
  }
  
  /**
   * 
   * Get designer shape list
   *
   * @param null
   * @return void
   */
  public function getShapeList($pagination = false, $search_val = null, $status_flag = -1){

    if($status_flag == -1){
      $where = ['post_type' => 'designer_shape'];
    }
    else{
      $where = ['post_type' => 'designer_shape', 'post_status' => $status_flag];
    }
    
    if($search_val && $search_val != null){
      $get_post = DB::table('posts')
                      ->where($where)
                      ->where('post_title', 'LIKE', '%'. $search_val .'%')
                      ->orderBy('id', 'desc');    
    }
    else{
      $get_post = DB::table('posts')
                      ->where($where)
                      ->orderBy('id', 'desc');      
    }
    
    if($pagination){
      $data = $get_post->paginate(10);  
    }
    else{
      $data = $get_post->get()->toArray();
    }

    return $data;
  }
  
  /**
   * 
   * Get designer fonts list
   *
   * @param null
   * @return void
   */
  public function getFontsList($pagination = false, $search_val = null, $status_flag = -1){    
    if($status_flag == -1){
      $where = ['posts.post_type' => 'custom_font'];
    }
    else{
      $where = ['posts.post_type' => 'custom_font', 'posts.post_status' => $status_flag];
    }
    
    if($search_val && $search_val !=null){
      $get_post = DB::table('posts')
                      ->where($where)
                      ->where('posts.post_title', 'LIKE', '%'. $search_val .'%')
                      ->orderBy('posts.id', 'desc');       
    }
    else{
      $get_post = DB::table('posts')
                      ->where($where)
                      ->orderBy('posts.id', 'desc');
    }

    $get_post = $get_post->join('post_extras', 'posts.id', '=', 'post_extras.post_id')
                         ->select('posts.*', DB::raw("max(CASE WHEN post_extras.key_name = '_font_uploaded_url' THEN post_extras.key_value END) as url"));
    
    
    if($pagination){
      $data = $get_post->groupBy('post_extras.post_id')->paginate(10); 
    }
    else{
      $data = $get_post->groupBy('post_extras.post_id')->get()->toArray();
    }

    return $data; 
  }
  
  /**
   * 
   * Get post meta
   *
   * @param post_id, key_name
   * @return object
   */
  public function get_post_meta($post_id, $key_name){
    $get_post_meta = null;
    $post_meta  = PostExtra::where(['post_id' => $post_id, 'key_name' => $key_name])->first();
    if(!empty($post_meta)){
      $get_post_meta = $post_meta;
    }
    
    return $get_post_meta;
  }
  
  /**
   * 
   * Create product content data
   *
   * @param array
   * @return array
   */
  function createProductContentData($data){
    $data['tabSettings']['btnCustomize']   =   'style=display:none;';
    $data['attrs_list_data']               =   $this->getTermData( 'product_attr', false, null, 1 );
    $data['categories_lists']              =   $this->get_categories( 0, 'product_cat');
    $data['tags_lists']                    =   $this->getTermData( 'product_tag', false, null, 1 );
    $data['colors_lists']                  =   $this->getTermData( 'product_colors', false, null, 1 );
    $data['sizes_lists']                   =   $this->getTermData( 'product_sizes', false, null, 1 );
    $data['manufacturer_lists']            =   $this->getTermData( 'product_brands', false, null, 1 );
    $data['available_user_roles']          =   get_available_user_roles();
    $data['vendors_list']                  =   $this->vendors->getAllVendors( false, null, 1 );
    $data['fields_name']                   =   $this->option->getProductCompareData();
    $data['settings_data']                 =   $this->option->getSettingsData();
    $data['currency_symbol']               =   $this->classCommonFunction->get_currency_symbol( $data['settings_data']['general_settings']['currency_options']['currency_name'] );
    
    $data['product_all_images_json']			=		json_encode(  
                                                  array(                                                                                     'product_image'            => '',
                                                  'product_gallery_images'   => array(),
                                                  'shop_banner_image'        => ''
                                                  )
                                              );
    $is_vendor = is_vendor_login(); 
    $sidebar['is_vendor_login'] = $is_vendor;
    $data['sidebar_data'] = $sidebar;                                        
    
    return $data;
  }
}