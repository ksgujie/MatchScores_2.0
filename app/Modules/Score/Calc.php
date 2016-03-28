<?php namespace App\Modules;

use App\Models\User;

abstract class Calc
{
	/**
	 * 清空已经计算好的成绩
	 */
	public function 清空成绩()
	{
		\DB::update("update users set 最终成绩1='',最终成绩2='',最终成绩3='',最终成绩4='',最终成绩5='',
				显示_最终成绩1='',显示_最终成绩2='',显示_最终成绩3='',显示_最终成绩4='',显示_最终成绩5='',
				最好成绩='',显示_最好成绩='',成绩排序='',排名='',奖项='',积分=''");
	}


	/**
	 * 一般不使用，除非所有项目排序方式相同
	 * @param string $orderType
	 */
	public function 全部排名($orderType = '降序')
	{
		$arrItems = SysConfig::items();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$arrGroups = SysConfig::item('组别');
			foreach ($arrGroups as $group) {
				$this->单项排名($item, $group, $orderType);
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

		$users = User::where('项目', $item)
			->where('组别', $group)
			->where('成绩排序', '!=', '')
			->orderBy('成绩排序', $orderBy)
			->get();
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

		//将排名为0（没有成绩）的数据的排名设置为999999,以便排序时排在最后，在显示时需处理好后再显示
		\DB::update("update users set 排名='999999' where 项目=? and 组别=? and 排名='0'", [$item, $group]);
	} //排名


	public function 生成成绩册()
	{
		$arrItems = SysConfig::items();
		$objExcel = new Excel();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$arrGroups = SysConfig::itemGroups($item);
			$orderByGroup = "'" . join("','", $arrGroups) . "'";
			$users = User:: whereRaw(
				"项目=? order by  FIELD(组别, $orderByGroup), 排名, 编号 ",
				[$item]
			)->get();

				$config = [
					'templateFile' => SysConfig::template('成绩册'),
					'sheetName' => str_replace(',', '', SysConfig::item('表名')),
					'firstDataRowNum' => SysConfig::item('首条数据行号'),
					'data' => $this->处理成绩册数据($users),
				];
				$objExcel->setConfig($config);
				$objExcel->make();

				//页眉、页脚
				$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 ' . config('my.比赛名称') . "&\"宋体,常规\"&14 成绩册");
				$objExcel->sheet->getHeaderFooter()->setOddFooter('&C&P/&N页');

				//打印到一页
				$objExcel->printInOnePage();
			}//foreach items as item

			$objExcel->save(SysConfig::saveExcelDir() . utf8ToGbk("/成绩册.xlsx"));
	}//生成成绩册

	/**
	 * 处理“生成成绩册()”里的数据
	 * @param $users
	 * @return mixed
	 */
	protected function 处理成绩册数据($users)
	{
		//循环
		foreach ($users as $k => $user) {
			//处理没有排名的数据
			if ($user->排名 == 999999) {
				$user->排名 = '';
			}
			$users[$k]=$user;
		}
		return $users;
	}


	/**
	 * 产生一个供打印证书使用的获奖名单：个人、单项团体、综合团体
	 */
	public function 生成获奖名单()
	{
		$users = User::where('奖项','<>',"")
					->orderBy('单位')
					->orderBy('项目')
					->orderBy('组别')
					->orderBy('排名')
					->get();

		$config = [
			'templateFile' => SysConfig::template('获奖名单_打印'),
			'sheetName' => '个人',
			'firstDataRowNum' => 2,
			'data' => $users,
		];
		$objExcel = new Excel();
		$objExcel->setConfig($config);
		$objExcel->make();
		$objExcel->save(SysConfig::saveExcelDir() . utf8ToGbk("/获奖名单_打印.xlsx"));
	}

}//class