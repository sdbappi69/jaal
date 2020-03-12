<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
   
  protected $table = 'users';
  //protected $fillable = ['*'];
  //protected $primaryKey = 'id';
  
  public function roles()
  {
    return $this->belongsToMany('App\Models\Role');
  }
  
}
