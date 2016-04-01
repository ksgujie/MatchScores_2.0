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
	
	public function 计算成绩()
	{
		$项目名称=urldecode($_SERVER['QUERY_STRING']);

		$cfgItem	= new Item($项目名称);
		$cfgScore	= new Score($项目名称);
		
		$calc = new Calc();
		$calc->填充排序字段($项目名称);
		
		foreach ($cfgItem->组别 as $group) {
			$calc->单项排名($项目名称, $group, $cfgScore->排序方式);
			$calc->奖项($项目名称, $group, $cfgScore->获奖比例);
		}

		return redirect('main/index')->with('message', $项目名称 . ' 计算完成');
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


/**

		}//foreach items as item
**/
		$objFillData->save(gbk(matchConfig('全局.工作目录').'/成绩册.xls'));
	}//生成成绩册


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

}