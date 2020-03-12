<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use App\Library\CommonFunction;


class InstallationController extends Controller
{
  public function __construct(){
    $this->middleware('verifyLoginPage');
  }
  
  /**
   * 
   * Installation process start. Create all database table
   *
   * @param null
   * @return response
   */
  public function installationCheck(){
    $is_installed = is_installed();
    $has_admin = has_admin();
    
    if(!$is_installed && !$has_admin){
      if(is_all_tables_created()){
        return view('errors.has_table');
      }
      else{
        Artisan::call('migrate', ["--force" => true]);
      }
      
      return redirect()->route('installation-process');
    }
  }
  
 
   /**
   * 
   * Check admin user data is exist or not
   *
   * @param null
   * @return boolean
   */
  public function isAdminRoleDataExist(){
    $classCommonFunction = new CommonFunction(); 
    
    if($classCommonFunction->is_shopist_admin_installed()){
      return true;
    }
    else{
      return false;
    }
  }
}