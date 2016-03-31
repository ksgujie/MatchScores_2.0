<?php namespace App\Http\Controllers;

use App\User;

class ActionController extends Controller {

	public function run($do)
	{
		$this->$do();
		return redirect('main/index')->with('message', date("Y-m-d H:i:s")."完成：$do \n耗时：". $this->runnedTime());
	}

	public function 计算成绩($项目名称)
	{
		
	}

}