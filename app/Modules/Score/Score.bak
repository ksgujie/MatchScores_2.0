<?php namespace App\Modules;

use App\Models\ExcellentTeacher;
use App\Models\User;

class Scorekkkkkkkkkkkkkkkkkkkkkk
{
	public function 优秀辅导员()
	{

		//清空
		\DB::delete("TRUNCATE excellentteachers");

		//前六名
		$users = User::where('排名', '<', 7)->get();
		foreach ($users as $user) {
			ExcellentTeacher::create([
				'user_id'=>$user->id,
			]);
		}

	}

	public function 计算成绩()
	{
		$this->清空成绩();

		$this->a1();
		$this->a2();
		$this->b1();
		$this->c1();
		$this->全部排名();
//return;
		$items = [
			'P1B-1橡筋动力模型飞机留空计时赛',
			'带降模型火箭留空计时赛',
			'遥控电动模型直升机特技赛',
			'手掷飞机三人接力团体赛',
		];
		foreach ($items as $item) {
			$groups = SysConfig::itemGroups($item);
			foreach ($groups as $group) {
				$this->奖项_按比例分配($item, $group, [ '一等奖'=>'0.1', '二等奖'=>'0.2', '三等奖'=>'0.3' ]);
			}
		}

//		$this->生成成绩册();
		$this->综合团体();
	}
	
	public function a1()
	{
		$item = 'P1B-1橡筋动力模型飞机留空计时赛';
		$users = User::where('项目', $item)->where('原始成绩', '!=', '')->get();
		foreach ($users as $user) {
				$arrRawScore = unserialize($user->原始成绩);
				$user->成绩排序 = Show::补零($arrRawScore[0]);
				$user->save();
//			pd($user);
		}
	}

	public function a2()
	{
		$item = '带降模型火箭留空计时赛';
		$users = User::where('项目', $item)->where('原始成绩', '!=', '')->get();
		foreach ($users as $user) {
				$arrRawScore = unserialize($user->原始成绩);
				$user->成绩显示 = $user->原始成绩;
				$user->成绩排序 = Show::补零($arrRawScore[0]);
				$user->save();
		}
	}

	public function b1()
	{
		$item = '遥控电动模型直升机特技赛';
		$users = User::where('项目', $item)->where('原始成绩', '!=', '')->get();
		foreach ($users as $user) {
				$arrRawScore = unserialize($user->原始成绩);
				$user->成绩排序 = $this->排序_得分大用时短(Show::补零($arrRawScore[0], 4), Show::补零($arrRawScore[1]), 4);
				$user->save();
		}
	}

	public function c1()
	{
		$item = '手掷飞机三人接力团体赛';
		$users = User::where('项目', $item)->where('原始成绩', '!=', '')->get();
		foreach ($users as $user) {
				$arrRawScore = unserialize($user->原始成绩);
				$user->成绩排序 = Show::补零($arrRawScore[0]);
				$user->save();
		}
	}




}