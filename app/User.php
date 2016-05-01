<?php

namespace App;

//use Illuminate\Foundation\Auth\User as Authenticatable;

//class User extends Authenticatable
class User extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = [
//        'name', 'email', 'password',
//    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
	protected $guarded = [];

    public static function 所有参赛队()
    {
        $users = User::all();
        $r=[];
        foreach ($users as $user) {
            $r[$user->参赛队]='';
        }
        return array_keys($r);
    }
}
