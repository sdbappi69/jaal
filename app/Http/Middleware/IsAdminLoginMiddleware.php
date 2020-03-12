<?php
namespace App\Http\Middleware;

use Closure;
use Session;

class IsAdminLoginMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if (Session::has('shopist_admin_user_id')){
			return redirect()->route('admin.dashboard');
    }
    
    return $next($request);
  }
}