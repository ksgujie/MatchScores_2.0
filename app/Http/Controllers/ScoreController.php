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
		return redirect('main/index')->with('message', date("Y-m-d H:i:s")."完成：$do \n耗时：". $this->runnedTime());
	}
//	public function 计算成绩()
//	{
//		$cf
//		$a1=\PHPExcel_IOFactory::load('g:/a1.xls');
//		$a2=\PHPExcel_IOFactory::load('g:/a2.xls');
//		$excel=new \PHPExcel();
//
//		$s1=clone $a1->getSheetByName('A1');
//		$s2=clone $a2->getSheetByName('A2');
//		$excel->addSheet($s1);
//		$excel->addSheet($s2);
//		$excel->removeSheetByIndex(0);
//		$w=new \PHPExcel_Writer_Excel5($excel);
//		$w->save('g:/a.xls');
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
	 * 2016昆山车模。A/B两组中各取排名最好一个相加
	 */
	public function 计算综合团体()
	{
		$groupA = ['1/10遥控电动越野竞速赛','1/10遥控电动房车竞速赛','1/16遥控电动越野、大脚车竞速赛','1/16遥控电动房车竞速赛','1/18遥控电动车竞速赛','1/28遥控电动房车竞速赛'];
		$groupB = ['“闪电冲线教育特供”竞速赛','电动四驱车拼装赛','风动车直线竞速赛','车辆模型电脑模拟赛'];
		$schools = User::所有参赛队();
		$result = [];
		foreach ($schools as $school) {
			//A组最好排名
			$whereIn = "('" . join("','", $groupA) . "')";
			$user = User::whereRaw("参赛队=? and 项目 in $whereIn and 排名!='' order by abs(排名)", [$school])->first();
			if ($user) {
				$result[$school]['A'] = $user;
			}
			//Ｂ组最好排名
			$whereIn = "('" . join("','", $groupB) . "')";
			$user = User::whereRaw("参赛队=? and 项目 in $whereIn and 排名!='' order by abs(排名)", [$school])->first();
			if ($user) {
				$result[$school]['B'] = $user;
			}
		}

		foreach ($result as $school => $schoolScores) {
			$r=[];
			if (isset($schoolScores['A']) && isset($schoolScores['B'])) {
				$r[]=  $school;
				$r[]=  $schoolScores['A']->项目;
				$r[]=  $schoolScores['A']->排名;
				$r[]=  $schoolScores['B']->项目;
				$r[]=  $schoolScores['B']->排名;
				$rr[]=$r;
			}
		}
		arrayToExcel($rr, 'g:/tt.xls',9);
	}

	public function 生成成绩册()
	{
		//生成模板
		Template::生成成绩册模板();
		//定义要使用到的变量、对象
		$tplFile = gbk(matchConfig('全局.工作目录').'/模板/成绩册模板.xls');
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
					'firstDataRowNum' => 3,
					'data' => $users,
				];
				$objFillData->setConfig($config);
				$objFillData->make();
				//读取、填充项目名称（A1）、级别（An）
				$groupColIndex = $objExcel->getSheetByName($newSheetName)->getCell('A1')->getValue();
				$objExcel->getSheetByName($newSheetName)->getCellByColumnAndRow($groupColIndex, 1)->setValue($group);
				$objExcel->getSheetByName($newSheetName)->getCell('A1')->setValue($itemName);

				//
				$n++;
			}//foreach

			//隐藏模板表
			$objExcel->getSheetByName($objItem->表名)->setSheetState(\PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
		}
		//显示封面
		$objExcel->getSheetByName('成绩册封面')->setSheetState();
		$objFillData->save(gbk(matchConfig('全局.工作目录').'/成绩册.xls'));
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
			'templateFile' => gbk(base_path('通用模板/获奖名单.xls')),
			'sheetName' => '个人',
			'firstDataRowNum' => 2,
			'data' => $users,
		];
		$objExcel = new FillData();
		$objExcel->setConfig($config);
		$objExcel->make();
		$objExcel->save(gbk(matchConfig('全局.工作目录')."/获奖名单.xls"));
	}
	
	public function 优秀辅导员名单()
	{
		$schools='昆山市周庄中学
昆山市玉峰实验学校
昆山市娄江实验学校（初中部）
昆山市葛江中学
昆山市城北高科园中心小学
昆山市大市中心小学校
昆山市费俊龙中学（初中部）
昆山国际学校
昆山市陆家镇菉溪小学
昆山开发区青阳港学校（初中部）
昆山市兵希中学
昆山市花桥中心小学校
昆山市娄江实验学校（小学部）
昆山市柏庐实验小学
昆山市南港中心小学校
昆山高新区吴淞江学校（初中部）
昆山市周市镇永平小学
昆山市培本实验小学
昆山市周市华城美地小学
昆山市正仪中心小学校
昆山市淀山湖中心小学校
昆山开发区青阳港学校（小学部）
昆山市玉山镇司徒街小学
昆山市锦溪中心小学校
昆山高新区吴淞江学校（小学部）
昆山市张浦中心小学校
昆山开发区世茂蝶湖湾小学
昆山市实验小学
昆山开发区晨曦小学';
		$schools = explode("\n",$schools);
		echo "参赛队\t教练\t项目\t组别\t学生\t排名\t奖项\n";
		foreach ($schools as $school) {
			$school=trim($school);
			$users = User::where('参赛队', $school)->get();
			foreach ($users as $user) {
				$teachers = trim($user->教练);
				$teachers = preg_split('/[\s]+/', $teachers);
				foreach ($teachers as $teacher) {
					echo "$school\t$teacher\n";
//					$r[$teacher]='';
//					$r[$teacher][]=$school;
//					$r[$teacher][]=$teacher;
//					$r[$teacher]=$user->项目;
//					$r[$teacher]=$user->组别;
//					$r[$teacher]=$user->姓名;
//					$r[$teacher]=$user->排名;
//					$r[$teacher]=$user->奖项;
				}
//				echo "\n";
			}
		}
		die;
	}

}