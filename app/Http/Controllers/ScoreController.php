<?php namespace App\Http\Controllers;

use App\Modules\Excel\FillData;
use App\Modules\Excel\Template;
use App\Modules\MatchConfig\Item;
use App\Modules\MatchConfig\Score;
use App\Modules\Score\Sort;
use App\Modules\Score\Calc;
use App\User;

class ScoreController extends Controller {

	public function run($do)
	{
		$this->$do();
		return redirect('main/index')
			->with('action', $do)
			->with('timeout', $this->runnedTime());
	}
//	public function 计算成绩()
//	{
//		$cf
//		$a1=\PHPExcel_IOFactory::load('g:/a1.xlsx');
//		$a2=\PHPExcel_IOFactory::load('g:/a2.xlsx');
//		$excel=new \PHPExcel();
//
//		$s1=clone $a1->getSheetByName('A1');
//		$s2=clone $a2->getSheetByName('A2');
//		$excel->addSheet($s1);
//		$excel->addSheet($s2);
//		$excel->removeSheetByIndex(0);
//		$w=new \PHPExcel_Writer_Excel2007($excel);
//		$w->save('g:/a.xlsx');
//
////		$this->_计算成绩('1/10遥控电动越野竞速赛');
//	}

	/**
	 * 个人成绩
	 */
	public function 计算成绩()
	{
		$items = array_keys(matchConfig('项目'));
		foreach ($items as $项目名称) {
			$this->计算单项成绩($项目名称);
		}
	}

	public function 计算成绩bak2016年5月9日()
	{
		$items = array_keys(matchConfig('项目'));
		foreach ($items as $项目名称) {
//			$项目名称='1/10遥控电动房车竞速赛';///////用于测试单项成绩//////
			$cfgItem	= new Item($项目名称);
			$cfgScore	= new Score($项目名称);

			$calc = new Calc();
			$calc->填充排序字段($项目名称);

			foreach ($cfgItem->组别 as $group) {
//				$group = '中学';//////////////////////用于测试单项成绩////////
				$calc->单项排名($项目名称, $group, $cfgScore->排序方式);
				$calc->奖项($项目名称, $group, $cfgScore->获奖比例);
//				die;//////////////////////////////////用于测试单项成绩////////
			}
		}
//		return redirect('main/index')->with('message', $项目名称 . ' 计算完成');
	}

	/**
	 * 计算单个项目的成绩
	 * @param $项目名称
	 * @param null $组别 如果$组别==null就循环计算该项目下所有“组别”，否则只计算指定组别
	 */
	public function 计算单项成绩($项目名称, $组别=null)
	{
		if ($组别 == null) {
			\DB::update("update users set  成绩排序='', 排名='', 奖项='' where 项目=?", [$项目名称]);
		} else {
			\DB::update("update users set  成绩排序='', 排名='', 奖项='' where 项目=? and 组别=?", [$项目名称, $组别]);
		}

		$cfgItem	= new Item($项目名称);
		$cfgScore	= new Score($项目名称);

		$calc = new Calc();
		$calc->填充排序字段($项目名称);

		if ($组别 != null) {
			$calc->单项排名($项目名称, $组别, $cfgScore->排序方式);
			$calc->奖项($项目名称, $组别, $cfgScore->获奖比例);
		} else {
			foreach ($cfgItem->组别 as $group) {
				$calc->单项排名($项目名称, $group, $cfgScore->排序方式);
				$calc->奖项($项目名称, $group, $cfgScore->获奖比例);
			}
		}
	}

	/**
	 * 2016昆山车模。A/B两组中各取排名最好一个相加
	 */
	public function 计算综合团体()
	{
		$groupA = '“七彩风车小屋”拼装彩绘个人赛';
		$groupB = '“缤纷童年”场景规划制作团体赛';
		$schools = User::所有参赛队();
		$result = [];
		foreach ($schools as $school) {
			//b组最好排名
			$user = User::whereRaw("参赛队=? and 项目=? and 排名!='' order by abs(排名)", [$school,$groupB])->first();
			//a
			if ($user) {
				$users = User::whereRaw("参赛队=? and 项目=? and 排名!='' order by abs(排名)", [$school,$groupA])->take(3)->get();
				if ($users) {
					$n=0;
					foreach ($users as $_u) {
						$n+=$_u->排名;
					}
					$result[]=[$school,$user->组别, $n, $user->排名];
				}

			}
		}

		arrayToExcel($result, 'g:/tt.xlsx',9);
	}

	public function 生成成绩册()
	{
		$this->checkFileExist(gbk(matchConfig('全局.工作目录') . '/成绩册.xlsx'));

		//生成模板
		Template::生成成绩册模板();
		//定义要使用到的变量、对象
		$tplFile = gbk(matchConfig('全局.工作目录').'/模板/成绩册模板.xlsx');
		$objExcel = \PHPExcel_IOFactory::load($tplFile);//模板文件对象
		$arrItems = matchConfig('项目');
		$arrManual = matchConfig('成绩册');
		$objFillData = new FillData();

		//开始按项目循环处理
		foreach ($arrItems as $itemName => $itemConfig) {
			$objItem = new Item($itemName);
			$n=0;//计数
			//按组别循环
			foreach ($objItem->组别 as $group) {
				//算出表名，克隆、添加新表
				$groupLetter = $this->letters[$n];
				$newSheetName = $objItem->表名 .  $groupLetter;
				$objNewSheet = clone $objExcel->getSheetByName($objItem->表名);
				$objNewSheet->setTitle($newSheetName);
				$objExcel->addSheet($objNewSheet);

				//读取并填充数据入新表
//				$users = User::where('项目', $itemName)->where('组别', $group)->orderby('排名')->orderby('编号')->get();
				$users = User::whereRaw("项目=? and 组别=? order by if(排名='',1,0), abs(排名), 编号", [$itemName, $group])->get();
				//循环，处理“成绩1、成绩2、备注1、备注2……”
				foreach ($users as $_k => $_u) {
					$rawScores = [];
					if (strlen($_u->原始成绩)) {
						$rawScores = unserialize($_u->原始成绩);
					}
					$marks = [];
					if (strlen($_u->成绩备注)) {
						$marks = unserialize($_u->成绩备注);
					}
					for ($i = 0; $i < 20; $i++) {
						//成绩
						$scoreField = "成绩" . ($i+1);
						$_u->$scoreField = isset($rawScores[$i]) ? $rawScores[$i] : null;
						//成绩备注
						$markField = '备注' . ($i+1);
						$_u->$markField = isset($marks[$i]) ? $marks[$i] : null;

						$users[$_k] = $_u;
					}
				}
//					$users = User:: whereRaw("项目=?, 组别=? order by 排名, 编号 ", [$itemName, $group])->get();
				$config = [
					'objExcel' => $objExcel,
					'sheetName' => $newSheetName,
					'firstDataRowNum' => 4,
					'data' => $users,
				];
				$objFillData->setConfig($config);
				$objFillData->make();
				//读取、填充项目名称（A2）、级别（An）
				$groupColIndex = $objExcel->getSheetByName($newSheetName)->getCell('A2')->getValue();
				$objExcel->getSheetByName($newSheetName)->getCellByColumnAndRow($groupColIndex, 2)->setValue($group);
				$objExcel->getSheetByName($newSheetName)->getCell('A2')->setValue($itemName);

				//
				$n++;
			}//foreach

			//隐藏模板表
			$objExcel->getSheetByName($objItem->表名)->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		}
		//显示封面
		$objExcel->getSheetByName('成绩册封面')->setSheetState();
		$objFillData->save(gbk(matchConfig('全局.工作目录').'/成绩册.xlsx'));
	}//生成成绩册


	/**
	 * 产生一个供打印证书使用的获奖名单：个人、单项团体、综合团体
	 */
	public function 生成获奖名单()
	{
		$users = User::where('奖项','!=',"")
			->orderBy('参赛队')
			->orderBy('项目')
			->orderBy('组别')
			->orderBy('排名')
			->get();
		$users = User::whereRaw("奖项!='' order by 参赛队,项目,组别,abs(排名)")->get();
		$config = [
			'templateFile' => gbk(base_path('通用模板/获奖名单.xlsx')),
			'sheetName' => '个人',
			'firstDataRowNum' => 2,
			'data' => $users,
		];
		$objExcel = new FillData();
		$objExcel->setConfig($config);
		$objExcel->make();
		$objExcel->save(gbk(matchConfig('全局.工作目录')."/获奖名单.xlsx"));
	}
	
	public function 优秀辅导员名单()
	{
		$arrItems = matchConfig('项目');
		//开始按项目循环处理
		$r=[];
		foreach ($arrItems as $itemName => $itemConfig) {
			$objItem = new Item($itemName);
			foreach ($objItem->组别 as $group) {
				$user=User::whereRaw("项目=? and 组别=? and 奖项=? order by abs(排名) desc",[$itemName, $group, '一等奖'])->first();
//				dd($user);
				$maxPm = $user->排名;
				$maxPm=round($maxPm/2);
				$users = User::whereraw("项目=? and 组别=? and 奖项=? and abs(排名) <= $maxPm",[$itemName, $group, '一等奖'])->get();
				foreach ($users as $user) {
					$r[$user->参赛队.$user->教练]=$user->toArray();
				}
			}
		}
		arrayToExcel($r, 'g:/fdy.xlsx',true);
	}
}