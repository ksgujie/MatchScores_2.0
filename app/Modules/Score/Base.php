<?php namespace App\Modules\Score;

use App\Models\GroupAward;
use App\Models\GroupAwardDetail;
use App\Models\School;
use App\Models\User;
use Monolog\Handler\GroupHandlerTest;

abstract class Base
{
	/**
	 * 清空已经计算好的成绩
	 */
	public function 清空成绩()
	{
		\DB::update("update users set 成绩显示='',最好成绩显示='',成绩排序='',排名='',奖项='',积分=''");
	}


	/**
	 * 提取各单位单项积分、计算团体积分、奖项一并完成
	 */
	public function 综合团体()
	{
		//清空数据
		\DB::delete("TRUNCATE groupaward");
		\DB::delete("TRUNCATE groupawarddetail");

		$users = User::all();
		$schools = User::schools();
//		pd($schools);
		/////////各项积分明细////////
		foreach ($schools as $school) {
			$items = SysConfig::items();
			foreach ($items as $item) {
				$user = User::whereRaw("单位=? and 项目=? order by if(排名='',1,0), abs(排名)",[$school, $item])->first();
				//只保存参加该项目的单位数据
				if ($user) {
					$data = [
						'单位'=>$school,
						'项目'=>$item,
						'积分'=>'1'.Show::补零($user->排名,4),
					];
					GroupAwardDetail::create($data);
				}
			}
		}

		/////////计算总积分////////
		//计算积分和
		foreach ($schools as $school) {
			$details = GroupAwardDetail::where('单位', $school)->get();
			if ($details) {
				$total = 0;
				foreach ($details as $detail) {
					$total += $detail->积分;
				}
				Groupaward::create([
					'单位'=>$school,
					'总积分'=>$total,
					'成绩排序'=>self::排序_得分大用时短(Show::补零($total), Show::补零(''), 4),
				]);
			}
		}

		//计算排名
		self::综合团体排名();
		self::综合团体_奖项_按比例分配();
	}

	/**
	 * 根据比例设定各综合团体的奖项，要先计算出排名才能使用该方法
	 */
	public function 综合团体_奖项_按比例分配()
	{
//				\DB::connection()->enableQueryLog();/////////////////////////////
		//奖项及比例 按从高到底排列 例（一、二、三等奖比例分别为10%、20%、30% ）：array('一等奖'=>'0.1', '二等奖'=>'0.2', '三等奖'=>'0.3')
		$jiangxiangAndBili = SysConfig::get('计算成绩.综合团体.奖项比例');
//		dd($jiangxiangAndBili);///////////

		//清空本项目、本组别的奖项值
		\DB::update("update groupaward set 奖项=''");
		//本项目、本组别总人数
		$schoolCount = count(User::schools());
		foreach ($jiangxiangAndBili as $jiangxiang=>$bili) { //$bili比例，$jiangxiang奖项
//			$awards = GroupAward::orderBy('排名')->get();
			$awards = GroupAward::whereRaw("奖项='' order by if(排名='',1,0), abs(排名)")->get();
			$thisAwardCount = round($bili * $schoolCount);//本奖项的人数 四舍五入

			for ($i = 0; $i < $thisAwardCount; $i++) {
				$award = $awards[$i];
				$award->奖项 = $jiangxiang;
				$award->save();

				$thisAwardSort = $award->排名;//本项目、组别、奖项的最后一个用户的排名
			}

//			dd(\DB::getQueryLog());
//			dd($users);//////////////////////////
//			dd($thisUserCount);
//			dd([$jiangxiang, $item, $group, $thisItemGroupUserSort]);
			//处理与本奖项最后一人排名相同人的奖项，应该与之相同（并列）。
			\DB::update("update groupaward set 奖项=? where 排名=?",
				[$jiangxiang, $thisAwardSort]);
		}
	}


	/**
	 * 根据比例设定各个组的奖项，要先计算出排名才能使用该方法
	 * @param $item 项目
	 * @param $group 组别
	 * @param $jiangxiangAndBili 奖项及比例 按从高到底排列 例（一、二、三等奖比例分别为10%、20%、30% ）：array('一等奖'=>'0.1', '二等奖'=>'0.2', '三等奖'=>'0.3')
	 */
	public function 奖项_按比例分配($item, $group, $jiangxiangAndBili)
	{
//				\DB::connection()->enableQueryLog();/////////////////////////////

		//清空本项目、本组别的奖项值
		\DB::update("update users set 奖项='' WHERE 项目=? and 组别=?", [$item, $group]);
		//本项目、本组别总人数
		$userCount = User::where('项目', $item)->where('组别', $group)->count();
		foreach ($jiangxiangAndBili as $jiangxiang=>$bili) { //$bili比例，$jiangxiang奖项
			$users = User::whereRaw("项目=? and 组别=? and 奖项='' order by if(排名='',1,0), abs(排名)", [$item, $group])->get();
//			$users = User::where('项目', $item)
//				->where('组别', $group)
//				->where('奖项','')
//				->orderBy('排名')
//				->get();
			$thisUserCount = round($bili * $userCount);//本奖项的人数 四舍五入

			for ($i = 0; $i < $thisUserCount; $i++) {
				$user = $users[$i];
				$user->奖项 = $jiangxiang;
				$user->save();

				$thisItemGroupUserSort = $user->排名;//本项目、组别、奖项的最后一个用户的排名
			}

//			dd(\DB::getQueryLog());
//			dd($users);//////////////////////////
//			dd($thisUserCount);
//			dd([$jiangxiang, $item, $group, $thisItemGroupUserSort]);
			//处理与本奖项最后一人排名相同人的奖项，应该与之相同（并列）。
			\DB::update("update users set 奖项=? WHERE 项目=? and  组别=? and 排名=?",
				[$jiangxiang, $item, $group, $thisItemGroupUserSort]);
		}
	}


	/**
	 * 先比得分，得分相同的再看最大得分数用时长的、再相同看得分小的一轮成绩
	 * @param $inputScore1 成绩1 已补零
	 * @param $inputScore2 成绩2 已补零
	 * @param $timeLen 用时字符串的长度，一般为6或者4；也可为其它长度，比如：用在团体总积分中的排序时长度就为3
	 * @return string
	 */
	public function 排序_得分大用时长($inputScore1, $inputScore2, $timeLen = 6)
	{
		$strReg = '/^(\d+)(\d{'. $timeLen .'})$/';
		preg_match($strReg, $inputScore1, $arr);
		$score1 = $arr[1];
		$time1 = $arr[2];
		preg_match($strReg, $inputScore2, $arr);
		$score2 = $arr[1];
		$time2 = $arr[2];

		$maxScore = max($score1, $score2);//最大得分
		$minScore = min($score1, $score2);//最小得分

		//判断出最大得分的用时
		if ($score1 == $maxScore) {
			if ($score1==$score2) {
				$maxScoreTime = max($time1, $time2);
			} else {
				$maxScoreTime = $time1;
			}
		} else {
			$maxScoreTime = $time2;
		}
		//最小得分的用时
		$minScoreTime = $maxScoreTime == $time1 ? $time2 : $time1;

		return join(',',
			[
				Show::补零($maxScore),
				Show::补零($minScore),
				Show::补零($maxScoreTime),
				Show::补零($minScoreTime)
			]
		);
	}

	/**
	 * 赛车成绩：圈数.用时 典型的应用，先比圈数，圈数相同的再看最大圈数用时少的
	 * @param $inputScore1 成绩1 已补零
	 * @param $inputScore2 成绩2 已补零
	 * @param $timeLen 用时字符串的长度，一般为6或者4；也可表示积分的长度，可为其它长度
	 * @return string
	 */
	public function 排序_得分大用时短($inputScore1, $inputScore2, $timeLen = 6)
	{
		$strReg = '/^(\d+)(\d{'. $timeLen .'})$/';
		preg_match($strReg, $inputScore1, $arr);
		$score1 = $arr[1];
		$time1 = $arr[2];
		preg_match($strReg, $inputScore2, $arr);
		$score2 = $arr[1];
		$time2 = $arr[2];

		$maxScore = max($score1, $score2);//最大得分
		$minScore = min($score1, $score2);//最小得分

		//判断出最大得分的用时
		if ($score1 == $maxScore) {
			if ($score1==$score2) {
				$maxScoreTime = min($time1, $time2);
				} else {
				$maxScoreTime = $time1;
				}
		} else {
			$maxScoreTime = $time2;
		}

		//最小得分的用时
		$minScoreTime = $maxScoreTime == $time1 ? $time2 : $time1;;

		return join(',',
			[
				Show::补零($maxScore),
				Show::补零($minScore),
				Show::补零(1000000000 - $maxScoreTime),
				Show::补零(1000000000 - $minScoreTime),
			]
		);
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
//		pd($users);////////////////////////////////////////////
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
//		\DB::update("update users set 排名='999999' where 项目=? and 组别=? and 排名='0'", [$item, $group]);
	} //排名

	/**
	 * 从“单项排名”修改过来
	 * @param string $orderType 排序方式：升序、降序
	 */
	public function 综合团体排名($orderType = '降序')
	{
//		\DB::enableQueryLog();//////////////////////////
		//先清空原来排名
		\DB::update("update groupaward set 排名=''");

		$orderBy = $orderType == '降序' ? 'desc' : 'asc';

		$users = GroupAward::where('成绩排序', '!=', '')
			->orderBy('成绩排序', $orderBy)
			->get();
//		pd($users);////////////////////////////////////////////
		//上一个排名
		$lastRank = 0;
		//上一个成绩（最后生成用于排名的）
		$lastScore = '';
		foreach ($users as $user) {
			if ($lastRank == 0) { //第一条数据
				$rank = 1;
				$user->排名 = $rank;
				$user->save();
//pp(\DB::getquerylog());
//pp($user);//////////////////////////
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
//pp(\DB::getquerylog());
//pd($user);
				$lastRank = $rank;
				$lastScore = $user->成绩排序;
			}

		} // foreach users as user

		//将排名为0（没有成绩）的数据的排名设置为999999,以便排序时排在最后，在显示时需处理好后再显示
		//\DB::update("update users set 排名='999999' where 项目=? and 组别=? and 排名='0'", [$item, $group]);
	} //团体排名


	public function 生成成绩册()
	{
		$arrItems = SysConfig::items();
		$objExcel = new Excel();
		foreach ($arrItems as $item) {
			SysConfig::setItem($item);
			$arrGroups = SysConfig::itemGroups($item);
			$orderByGroup = "'" . join("','", $arrGroups) . "'";
			$users = User:: whereRaw(
				"项目=? order by  FIELD(组别, $orderByGroup), if(排名='',1,0), abs(排名), 编号 ",
				[$item]
			)->get();
			//添加：原始成绩、显示成绩数组
			foreach ($users as $k => $u) {
				//原始成绩
				if (strlen($u->原始成绩)) {
					$originalScores = unserialize($u->原始成绩);
					foreach ($originalScores as $kk => $s) {
						$m = "原始成绩$kk";
						$u->$m = $s;
					}
				}
				//显示成绩
				if (strlen($u->成绩显示)) {
					$showScores = unserialize($u->成绩显示);
					foreach ($showScores as $kk=>$s) {
						$m = "成绩显示$kk";
						$u->$m = $s;
					}
				} else if (strlen($u->成绩备注)) {
					$arrMarks = unserialize($u->成绩备注);
					foreach ($arrMarks as $_k=>$_s) {
						$m = "成绩显示$_k";
						$u->$m = $_s;
					}
				} else {
					for ($i = 0; $i < 100; $i++) {
						$m = "成绩显示$i";
						$u->$m = '';
					}
				}

				$users[$k] = $u;
			}


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
			$objExcel->sheet->getHeaderFooter()->setOddFooter( '- &P/&N -&R生成时间：&D');

			//打印到一页
			$objExcel->printInOnePage();
		}//foreach items as item

		///////////综合团体表///////
		//判断成绩册中综合团体表是否存在 ，存在就写入
		$sheetName = SysConfig::get('计算成绩.综合团体.表名');
		$_objExcel = \PHPExcel_IOFactory::load(SysConfig::template('成绩册'));
		$objSheet= $_objExcel->getSheetByName($sheetName);
		if ($objSheet) {
			$config = [
				'templateFile' => SysConfig::template('成绩册'),
				'sheetName' => $sheetName,
				'firstDataRowNum' => 2,
				'data' => GroupAward::whereRaw("奖项!='' order by if(排名='',1,0), abs(排名) ")->get(),
			];
			$objExcel->setConfig($config);
			$objExcel->make();
		}

		//页眉、页脚
		$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 ' . '综合团体奖' . "&\"宋体,常规\"&14 成绩册");
		$objExcel->sheet->getHeaderFooter()->setOddFooter('&C&P/&N页');
		$objExcel->sheet->getHeaderFooter()->setOddFooter( '- &P/&N -&R生成时间：&D');

		//打印到一页
		$objExcel->printInOnePage();

		///////////优秀辅导员///////
		//判断成绩册中优秀辅导员表是否存在 ，存在就写入
		$sheetName = SysConfig::get('计算成绩.优秀辅导员.表名');
		$_objExcel = \PHPExcel_IOFactory::load(SysConfig::template('成绩册'));
		$objSheet= $_objExcel->getSheetByName($sheetName);
		if ($objSheet) {
			$config = [
				'templateFile' => SysConfig::template('成绩册'),
				'sheetName' => $sheetName,
				'firstDataRowNum' => 2,
				'data' => User::whereRaw("id in (select user_id from excellentteachers) order by 单位,组别")->get(),
			];
			$objExcel->setConfig($config);
			$objExcel->make();
		}

		//页眉、页脚
		$objExcel->sheet->getHeaderFooter()->setOddHeader('&C&"黑体,常规"&16 ' . '优秀辅导员' . "&\"宋体,常规\"&14 成绩册");
		$objExcel->sheet->getHeaderFooter()->setOddFooter('&C&P/&N页');
		$objExcel->sheet->getHeaderFooter()->setOddFooter( '- &P/&N -&R生成时间：&D');

		//打印到一页
		$objExcel->printInOnePage();

		/////////////生成/////////////
		$objExcel->save(SysConfig::saveExcelDir() . utf8ToGbk("/成绩册.xlsx"));
	}//生成成绩册

	/**
	 * 处理“生成成绩册()”里的数据
	 * @param $users
	 * @return mixed
	 */
	protected function 处理成绩册数据($users)
	{
		return $users;
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
		$users = User::whereRaw("奖项!='' order by 单位, 项目, 组别, if(排名='',1,0), abs(排名)", [])->get();
//		$users = User::where('奖项','<>',"")
//					->orderBy('单位')
//					->orderBy('项目')
//					->orderBy('组别')
//					->orderBy('排名')
//					->get();

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