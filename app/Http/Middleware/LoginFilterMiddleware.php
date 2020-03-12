<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Route;

class LoginFilterMiddleware
{
    protected $route;

    public function __construct(Route $route){
      $this->route = $route;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      $is_installed = is_installed();
      $has_admin = has_admin();
       
      if(!$is_installed && !$has_admin){
        
        $route = array('installation-process', 'installation-int', 'admin-data-save');
        
        if(!in_array($this->route->getName(), $route)){
          return redirect()->route('installation-int');
        }
      }
      
      return $next($request);
    }
}