<?php namespace App;

use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel {

	public $timestamps = false;

	public static function enableQueryLog()
	{
		\DB::connection()->enableQueryLog();
	}

	public static function getQueryLog()
	{
		return \DB::getQueryLog();
	}
}