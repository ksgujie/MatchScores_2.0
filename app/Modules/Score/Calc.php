<?php namespace App\Modules\Score;

use App\User;
use App\Modules\MatchConfig\Score;

class Calc
{
	/**
	 * 将数据库中的“成绩排名”字段计算、填充
	 * @param $项目名称
	 */
	public function 填充排序字段($项目名称)
	{
		$cfgScore	= new Score($项目名称);
		////////////计算填充成绩排序字段的内容////////////
		//清空原有数据
		\DB::update("update users set 成绩排序='' where 项目=?", [$项目名称]);
		$sortFun = $cfgScore->成绩排序;
		$rs = User::where('项目', $项目名称)->get();
		foreach ($rs as $row) {
			if (strlen($row->原始成绩)) {
				$rawScores = unserialize($row->原始成绩);
				//将原始成绩、用时长度放入一数组，传递给“成绩排序”方法
				$arrInput = [];
				for ($i=0; $i < count($rawScores); $i++) { 
					$n = $i+1;
					$key = "inputScore$n";
					$arrInput[$key] = $rawScores[$i];
				}
				//加上“用时长度”，尽管有些成绩中没有用时
				$arrInput['timeLen'] = $cfgScore->用时长度;
				//开始排序
				$row->成绩排序 = Sort::$sortFun($arrInput);
				
				$row->save();
			}
		}
	}


	/**
	 * @param $item 项目
	 * @param $group 组别
	 * @param string $orderType 排序方式：升序、降序
	 */
	public function 单项排名($item, $group, $orderType = '降序')
	{
		//先清空原来排名
		\DB::update("update users set 排名='' where 项目=? and 组别=?", [$item, $group]);

		$orderBy = $orderType == '降序' ? 'desc' : 'asc';

		$users = User::whereRaw("项目=? and 组别=? and (成绩排序!='' or 成绩备注!='') order by if(成绩排序='',1,0) asc,  成绩排序 $orderBy", [$item, $group])->get();
//		$users = User::where('项目', $item)
//			->where('组别', $group)
//			->where('成绩排序', '!=', '')
//			->orderBy('成绩排序', $orderBy)
//			->get();
		//上一个排名
		$lastRank = 0;
		//上一个成绩（最后生成用于排名的）
		$lastScore = '';
		foreach ($users as $user) {
			if ($lastRank == 0) { //第一条数据
				$rank = 1;
				$user->排名 = $rank;
				$user->save();

				$lastRank = $rank;
				$lastScore = $user->成绩排序;
			} else { //第二条到最后一条数据
				if ($lastScore == $user->成绩排序) { //并列
					$rank = $lastRank;
				} else {
					$rank = $lastRank + 1;
				}
				$user->排名 = $rank;
				$user->save();

				$lastRank = $rank;
				$lastScore = $user->成绩排序;
			}
		} // foreach users as user

	} //排名
	
	/**
	 * 根据比例设定各个组的奖项，要先计算出排名才能使用该方法
	 * @param $item 项目
	 * @param $group 组别
	 * @param $jiangxiangAndBili 奖项及比例 按从高到底排列 例（一、二、三等奖比例分别为10%、20%、30% ）：array('一等奖'=>'0.1', '二等奖'=>'0.2', '三等奖'=>'0.3')
	 */
	public function 奖项($item, $group, $jiangxiangAndBili)
	{
		//清空本项目、本组别的奖项值
		\DB::update("update users set 奖项='' WHERE 项目=? and 组别=?", [$item, $group]);
		//本项目、本组别总人数
		$userCount = User::where('项目', $item)
			->where('组别', $group)
			->where('成绩排序','!=','')
			->count();
		//当某个项目还没有导入任何成绩时，$userCount的值为0,而$thisUserCount至少为1,下面的for循环中读取$users[$i]会超出下标造成错误
		if ($userCount == 0) return;

		foreach ($jiangxiangAndBili as $jiangxiang=>$bili) { //$bili比例，$jiangxiang奖项
			$users = User::whereRaw("项目=? and 组别=? and 奖项='' order by if(排名='',1,0), abs(排名)", [$item, $group])->get();
			//本奖项的人数 四舍五入
			$thisUserCount = round($bili * $userCount);
			//如果该组总人数特别少（如：3人），一等奖（10%）计算出来的人数可能等于0，这时就要调整为1人
			$thisUserCount = $thisUserCount > 0 ? $thisUserCount : 1;

			for ($i = 0; $i < $thisUserCount; $i++) {
				//判断一下，当某些项目获奖率为100%时，且一等奖人数“五入”进一位后，二、三等奖获奖人数会超过剩下来的人数，造成错误
				if (!isset($users[$i])) {
					break;
				}
				$user = $users[$i];
				$user->奖项 = $jiangxiang;
				//（判断一下，有排名的才有奖项）暂时取消该功能
				$user->save();
				$thisItemGroupUserSort = $user->排名;//本项目、组别、奖项的最后一个用户的排名
			}

			//处理与本奖项最后一人排名相同人的奖项，应该与之相同（并列）。
			\DB::update("update users set 奖项=? WHERE 项目=? and  组别=? and 排名=?",
				[$jiangxiang, $item, $group, $thisItemGroupUserSort]);

			//计算已有获奖人数，不得超过总获奖人数（主要针对人数特别少的项目，如3－6人的）
			$应获奖比例 = (int)(array_sum($jiangxiangAndBili) * 10);	//这里是：应获奖比例*10，再取整，忽略小数位
			$已获奖人数 = User::whereRaw("项目=? and 组别=? and 奖项!=''", [$item, $group])->count();
			$已获奖比例 = (int)($已获奖人数/$userCount * 10);

//			echo $已获奖比例.'/'.$应获奖比例 .'=' . $已获奖比例/$应获奖比例 . '<br>';//////////////////////
			if ($已获奖比例 >= $应获奖比例) {
//				die('结束');
				break;
			}
		}
		//将原始成绩为空的奖项清除，防止大量未完成（排名都是并列的）的人也能获奖，虽然这样的情况很少出现
		\DB::update("update users set 奖项='' where 原始成绩=''");
	}
	


}//class